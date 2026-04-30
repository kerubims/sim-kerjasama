<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editor — {{ $doc['title'] ?? 'Dokumen' }} — SIM-KERMA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <!-- Local Assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-800 h-screen overflow-hidden flex flex-col font-[Inter]">

@php
    $user = Auth::user();
    $statusLabel = match($doc->status) {
        'signed' => 'AKTIF',
        'draft' => 'DRAFT',
        'review_client' => 'REVIEW CLIENT',
        'review_unit' => 'REVIEW UNIT',
        default => strtoupper(str_replace('_', ' ', $doc->status)),
    };

    // Check if current user is a party and has signed
    $currentParty = $doc->parties->where('user_id', $user->id)->first();
    $userHasSigned = $currentParty && $currentParty->signature_path;

    // canEdit logic matching reference exactly
    $canEdit = !$userHasSigned && (
        ($user->hasRole('super_admin') && $doc->status !== 'signed') ||
        ($user->hasRole('client') && $doc->status === 'review_client') ||
        ($user->hasRole('unit_pengusul') && $doc->status === 'review_unit')
    );

    // Import DOCX permission
    $canImportDocx = $user->hasRole('super_admin') || ($user->hasRole('client') && $doc->allow_client_upload);
@endphp

<div class="flex h-screen flex-col bg-white" x-data="editorPage()">
    <!-- Editor Header -->
    <header class="border-b border-slate-200 px-6 py-3 flex justify-between items-center bg-white z-10 shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('documents.index') }}" class="text-slate-500 hover:text-slate-800 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-900">{{ $doc->title }}</h1>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200">
                        {{ $statusLabel }}
                    </span>
                    <span>Last saved: {{ $doc->updated_at->format('H:i:s') }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Save Button --}}
            @if($canEdit)
            <button @click="saveContent()" class="text-slate-600 hover:text-slate-900 px-3 py-2 text-sm font-medium transition">
                <i class="fa-regular fa-floppy-disk mr-1"></i> Simpan
            </button>
            @endif

            {{-- Workflow Buttons --}}
            @if($user->hasRole('super_admin') && $doc->status === 'draft')
            <button @click="showSendModal=true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
                Kirim ke Client <i class="fa-solid fa-paper-plane ml-1"></i>
            </button>
            @endif

            @if($user->hasRole('client') && $doc->status === 'review_client' && !$userHasSigned)
            <button @click="showSignModal=true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
                <i class="fa-solid fa-signature mr-1"></i> Tanda Tangan
            </button>
            @endif

            @if($user->hasRole('unit_pengusul') && $doc->status === 'review_unit' && !$userHasSigned)
            <button @click="showSignModal=true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
                <i class="fa-solid fa-signature mr-1"></i> Tanda Tangan Final
            </button>
            @endif

            @if($userHasSigned)
            <div class="text-sm text-green-600 px-3 py-2 bg-green-50 rounded-md border border-green-200">
                <i class="fa-solid fa-check-circle mr-1"></i> Anda telah menandatangani
            </div>
            @endif

            @if($doc->status === 'signed')
            <button @click="exportPdf()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm ml-2 transition">
                <i class="fa-solid fa-file-pdf mr-1"></i> Export PDF
            </button>
            @endif
        </div>
    </header>

    <!-- Main Editor Area -->
    <div class="flex-1 flex overflow-hidden">
        <!-- WYSIWYG Container -->
        <div class="flex-1 bg-slate-100 overflow-y-auto p-8 flex justify-center">
            <div class="w-full max-w-[816px] bg-white shadow-sm min-h-[1056px] p-0">
                <textarea id="editor-area" class="w-full h-full opacity-0">{!! $doc->content !!}</textarea>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="w-80 border-l border-slate-200 bg-white flex flex-col shrink-0">
            <div class="flex border-b border-slate-200 shrink-0">
                <button @click="activeTab='history'" :class="activeTab==='history' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : 'text-slate-600 hover:bg-slate-50'" class="flex-1 px-4 py-3 text-sm font-semibold transition">
                    <i class="fa-solid fa-clock-rotate-left mr-1"></i> Riwayat
                </button>
                <button @click="activeTab='comments'" :class="activeTab==='comments' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : 'text-slate-600 hover:bg-slate-50'" class="flex-1 px-4 py-3 text-sm font-semibold transition">
                    <i class="fa-solid fa-comments mr-1"></i> Komentar
                    @if($doc->comments->count() > 0)
                    <span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full">{{ $doc->comments->count() }}</span>
                    @endif
                </button>
            </div>

            <!-- History -->
            <div x-show="activeTab==='history'" class="flex-1 overflow-y-auto p-4 space-y-6">
                @foreach($doc->histories->sortByDesc('created_at') as $h)
                <div class="relative pl-4 border-l-2 border-slate-200 pb-2">
                    <div class="absolute -left-[5px] top-0 w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                    <div class="text-xs text-slate-400 mb-1">{{ $h->created_at->format('d M Y H:i') }}</div>
                    <div class="text-sm font-medium text-slate-800">{{ $h->user->name }}</div>
                    <div class="text-xs text-slate-600 mt-1">{{ $h->message }}</div>
                </div>
                @endforeach
            </div>

            <!-- Comments -->
            <div x-show="activeTab==='comments'" class="flex-1 flex flex-col" style="display:none;">
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    @forelse($doc->comments->sortByDesc('created_at') as $c)
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                        <div class="flex items-center gap-2 mb-2">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($c->user->name) }}&background=0D8ABC&color=fff" class="w-6 h-6 rounded-full" alt="">
                            <div class="flex-1">
                                <div class="text-xs font-semibold text-slate-700">{{ $c->user->name }}</div>
                                <div class="text-xs text-slate-400">{{ $c->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        @if(!empty($c->quoted_text))
                        <div class="mb-2 pl-2 border-l-2 border-yellow-400 bg-yellow-50 text-xs text-slate-600 italic py-1">"{{ $c->quoted_text }}"</div>
                        @endif
                        <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ $c->text }}</div>
                    </div>
                    @empty
                    <div class="text-center text-slate-400 text-sm py-8">Belum ada komentar</div>
                    @endforelse
                </div>
                <div class="p-4 border-t border-slate-200 bg-slate-50 shrink-0">
                    <textarea id="comment-input" placeholder="Tulis komentar atau catatan..." class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" rows="3"></textarea>
                    <button @click="postComment()" class="mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        <i class="fa-solid fa-paper-plane mr-1"></i> Kirim Komentar
                    </button>
                </div>
            </div>

            <!-- Signatures Preview -->
            <div class="p-4 border-t border-slate-200 bg-slate-50 shrink-0">
                <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">Tanda Tangan</h4>
                <div class="space-y-3">
                    @foreach($doc->parties as $party)
                    <div class="border border-slate-200 rounded p-2 bg-white">
                        <div class="text-xs text-slate-400 mb-1">{{ $party->user->name }} ({{ $party->role_type === 'client' ? 'Client' : 'Unit Pengusul' }})</div>
                        @if($party->signature_path)
                        <img src="{{ Storage::url($party->signature_path) }}" class="h-12" alt="Signature">
                        @else
                        <div class="text-xs text-slate-300 italic">Belum ditandatangani</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('documents.partials.editor-modals')
</div>

<script src="/vendor/tinymce/tinymce.min.js"></script>
<script>
function editorPage() {
    return {
        activeTab: 'history',
        docId: {{ $doc->id }},
        showSignModal: false,
        showSendModal: false,
        allowUpload: false,
        signaturePreview: null,
        signatureFile: null,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        init() {
            const canEdit = @json($canEdit);
            const canImportDocx = @json($canImportDocx);

            const customSetup = (editor) => {
                editor.ui.registry.addButton('importdocx', {
                    text: 'Import DOCX',
                    icon: 'upload',
                    onAction: () => {
                        const input = document.createElement('input');
                        input.type = 'file';
                        input.accept = '.docx';
                        input.onchange = (e) => {
                            const file = e.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = function(loadEvent) {
                                    const arrayBuffer = loadEvent.target.result;
                                    
                                    const options = {
                                        styleMap: [
                                            "p[style-name='Heading 1'] => h1:fresh",
                                            "p[style-name='Heading 2'] => h2:fresh",
                                            "p[style-name='Heading 3'] => h3:fresh",
                                            "p[style-name='Heading 4'] => h4:fresh",
                                            "p[style-name='Title'] => h1.title",
                                            "p[style-name='Subtitle'] => h2.subtitle",
                                            "p[style-name='align-center'] => p.align-center",
                                            "p[style-name='align-right'] => p.align-right",
                                            "p[style-name='align-justify'] => p.align-justify",
                                            "u => u",
                                            "strike => s"
                                        ]
                                    };

                                    if (mammoth.transforms && mammoth.transforms.paragraph) {
                                        options.transformDocument = mammoth.transforms.paragraph(function(paragraph) {
                                            if (paragraph.alignment === "center") {
                                                return {...paragraph, styleName: "align-center"};
                                            } else if (paragraph.alignment === "right") {
                                                return {...paragraph, styleName: "align-right"};
                                            } else if (paragraph.alignment === "both" || paragraph.alignment === "justify") {
                                                return {...paragraph, styleName: "align-justify"};
                                            }
                                            return paragraph;
                                        });
                                    }

                                    mammoth.convertToHtml({arrayBuffer: arrayBuffer}, options)
                                        .then(function(result){
                                            let html = result.value;
                                            html = html.replace(/class="align-center"/g, 'style="text-align: center;"');
                                            html = html.replace(/class="align-right"/g, 'style="text-align: right;"');
                                            html = html.replace(/class="align-justify"/g, 'style="text-align: justify;"');
                                            editor.setContent(html);
                                            if (result.messages.length > 0) {
                                                console.warn('Mammoth messages:', result.messages);
                                            }
                                        })
                                        .catch(function(err) {
                                            alert('Gagal membaca file DOCX: ' + err.message);
                                        });
                                };
                                reader.readAsArrayBuffer(file);
                            }
                        };
                        input.click();
                    }
                });

                // Context menu for comments
                editor.ui.registry.addMenuItem('addcomment', {
                    text: 'Berikan Komentar',
                    icon: 'comment',
                    onAction: () => {
                        const selection = editor.selection.getContent({format:'text'});
                        if (!selection) return;
                        const commentId = 'mark_' + Date.now();
                        const content = `<span id="${commentId}" style="background-color:#fef08a;padding:2px 0;" data-comment-id="${commentId}">${editor.selection.getContent()}</span>`;
                        editor.selection.setContent(content);
                        document.getElementById('comment-input')?.focus();
                    }
                });
                editor.ui.registry.addContextMenu('selection', {
                    update: () => !editor.selection.isCollapsed() ? 'addcomment' : ''
                });
            };

            let toolbarConfig = false;
            if (canEdit) {
                const importBtn = (canImportDocx ? ' importdocx |' : '');
                toolbarConfig = `undo redo |${importBtn} blocks fontfamily fontsize | bold italic underline strikethrough | align numlist bullist | link image | table media | lineheight outdent indent | forecolor backcolor removeformat | charmap emoticons | fullscreen preview | help`;
            }

            tinymce.init({
                selector: '#editor-area',
                height: '100%',
                license_key: 'gpl',
                menubar: canEdit ? 'edit view insert format tools table help' : false,
                plugins: 'preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media table charmap insertdatetime advlist lists wordcount help emoticons',
                toolbar: toolbarConfig,
                readonly: !canEdit,
                content_style: `
                    body {
                        background-color: white;
                        width: 21cm;
                        min-height: 29.7cm;
                        padding: 2.5cm 2cm;
                        margin: 2rem auto;
                        box-shadow: 0 0 10px rgba(0,0,0,0.15);
                        box-sizing: border-box;
                        font-family: Tahoma, "Times New Roman", Times, serif;
                        font-size: 11pt;
                        line-height: 1.5;
                    }
                    html {
                        background-color: #f1f5f9;
                    }
                    p { margin-top: 0; margin-bottom: 10px; }
                    table { border-collapse: collapse; width: 100%; }
                    table, th, td { border: 1px solid black; }
                    th, td { padding: 5px; }
                `,
                branding: false,
                promotion: false,
                skin: 'oxide',
                statusbar: true,
                resize: false,
                contextmenu: 'addcomment link image table',
                setup: customSetup
            });
        },

        async saveContent(showAlertAndReload = true) {
            const content = tinymce.get('editor-area').getContent();
            const res = await fetch(`/documents/${this.docId}/content`, {
                method: 'PUT',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken},
                body: JSON.stringify({content})
            });
            if (res.ok && showAlertAndReload) {
                alert('Disimpan!');
                window.location.reload();
            }
        },

        async confirmSendToClient() {
            // Auto-save content terlebih dahulu sebelum merubah status
            await this.saveContent(false);

            const res = await fetch(`/documents/${this.docId}/status`, {
                method: 'PUT',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken},
                body: JSON.stringify({status:'review_client', allowUpload: this.allowUpload})
            });
            if (res.ok) {
                this.showSendModal = false;
                window.location.href = '{{ route("documents.index") }}';
            }
        },

        async postComment() {
            const input = document.getElementById('comment-input');
            const text = input.value;
            if (!text) return;
            const res = await fetch(`/documents/${this.docId}/comments`, {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken},
                body: JSON.stringify({text})
            });
            if (res.ok) { input.value = ''; window.location.reload(); }
        },

        previewSignature(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) { alert('Mohon upload file gambar'); return; }
            if (file.size > 2*1024*1024) { alert('Ukuran file maksimal 2MB'); return; }
            this.signatureFile = file;
            const reader = new FileReader();
            reader.onload = (e) => { this.signaturePreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        handleSignatureDrop(event) {
            const file = event.dataTransfer.files[0];
            if (file) {
                this.signatureFile = file;
                if (!file.type.startsWith('image/')) { alert('Mohon upload file gambar'); return; }
                if (file.size > 2*1024*1024) { alert('Maksimal 2MB'); return; }
                const reader = new FileReader();
                reader.onload = (e) => { this.signaturePreview = e.target.result; };
                reader.readAsDataURL(file);
            }
        },

        async submitSignature() {
            if (!this.signatureFile) { alert('Pilih file tanda tangan terlebih dahulu.'); return; }
            const formData = new FormData();
            formData.append('signature_file', this.signatureFile);
            const res = await fetch(`/documents/${this.docId}/sign`, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN':this.csrfToken},
                body: formData
            });
            if (res.ok) {
                this.showSignModal = false;
                window.location.reload();
            } else {
                const data = await res.json();
                alert(data.message || 'Gagal mengunggah tanda tangan');
            }
        },

        exportPdf() {
            alert('Sedang menyiapkan PDF, mohon tunggu sebentar...');
            const content = tinymce.get('editor-area').getContent();
            const el = document.createElement('div');
            el.style.padding = '40px';
            el.style.fontFamily = 'Times New Roman, serif';
            el.innerHTML = content;

            // Menggunakan path relatif (/storage/...) agar html2canvas tidak terblokir CORS
            const parties = @json($doc->parties->map(fn($p) => ['name'=>$p->user->name,'role'=>$p->role_type,'sig'=>$p->signature_path ? '/storage/' . $p->signature_path : null]));
            let sigBlock = '<div style="margin-top:60px;display:flex;justify-content:space-between;page-break-inside:avoid;">';
            parties.forEach((p,i) => {
                sigBlock += `<div style="text-align:center;width:45%;">
                    <p style="margin-bottom:20px;font-weight:bold;">Pihak ${i===0?'Pertama':'Kedua'}</p>
                    ${p.sig ? `<img src="${p.sig}" style="height:80px;display:block;margin:0 auto;" />` : '<div style="height:80px;"></div>'}
                    <p style="margin-top:20px;font-weight:bold;text-decoration:underline;">${p.name}</p>
                </div>`;
            });
            sigBlock += '</div>';
            el.insertAdjacentHTML('beforeend', sigBlock);

            html2pdf().set({
                margin: 1,
                filename: '{{ $doc->title }}.pdf',
                image: {type:'jpeg',quality:0.98},
                html2canvas: {
                    scale: 2, 
                    useCORS: true,
                    // Trik jitu ke-3: Mencegah error 'oklch' dari Tailwind dengan cara 
                    // mengabaikan semua tag <style> dan <link> saat html2canvas melakukan cloning.
                    // Ini jauh lebih aman dan tidak menyebabkan tampilan utama berkedip.
                    ignoreElements: function(element) {
                        const tag = element.tagName ? element.tagName.toLowerCase() : '';
                        return tag === 'style' || tag === 'link';
                    }
                },
                jsPDF: {unit:'in',format:'letter',orientation:'portrait'}
            }).from(el).save().catch(err => {
                console.error('PDF Export Error:', err);
                alert('Gagal membuat PDF: ' + (err.message || err));
            });
        }
    }
}
</script>
</body>
</html>
