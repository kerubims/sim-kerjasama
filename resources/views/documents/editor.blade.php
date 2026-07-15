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
        'review_client' => 'REVIEW MITRA',
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
            <a href="{{ route('documents.index') }}" @click.prevent="navigateBack('{{ route('documents.index') }}')" class="text-slate-500 hover:text-slate-800 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-900">{{ $doc->document_number ? $doc->document_number . ' - ' : '' }}{{ $doc->title }}</h1>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200">
                        {{ $statusLabel }}
                    </span>
                    <span>Terakhir disimpan: {{ $doc->updated_at->format('H:i:s') }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Save Button --}}
            @if($canEdit && !$doc->file_path)
            <button @click="saveContent()" class="text-slate-600 hover:text-slate-900 px-3 py-2 text-sm font-medium transition">
                <i class="fa-regular fa-floppy-disk mr-1"></i> Simpan
            </button>
            @endif

            {{-- Workflow Buttons --}}
            @if($user->hasRole('super_admin') && $doc->status === 'draft')
            <button @click="showSendModal=true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
                Kirim ke Unit Pengusul <i class="fa-solid fa-paper-plane ml-1"></i>
            </button>
            @endif

            @if($user->hasRole('unit_pengusul') && $doc->status === 'review_unit' && !$userHasSigned)
            <button @click="rejectDocument()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
                <i class="fa-solid fa-xmark mr-1"></i> Tolak / Kembalikan
            </button>
            <button @click="showSignModal=true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
                <i class="fa-solid fa-signature mr-1"></i> Tanda Tangan
            </button>
            @endif

            @if($user->hasRole('client') && $doc->status === 'review_client' && !$userHasSigned)
            <button @click="rejectDocument()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
                <i class="fa-solid fa-xmark mr-1"></i> Tolak / Kembalikan
            </button>
            <button @click="showSignModal=true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
                <i class="fa-solid fa-signature mr-1"></i> Tanda Tangan
            </button>
            @endif

            @if($userHasSigned)
            <div class="text-sm text-green-600 px-3 py-2 bg-green-50 rounded-md border border-green-200">
                <i class="fa-solid fa-check-circle mr-1"></i> Anda telah menandatangani
            </div>
            @endif

            @if($doc->status === 'signed')
                @if($doc->file_path)
                <a href="{{ route('documents.preview', ['id' => $doc->id]) }}" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm ml-2 transition inline-flex items-center">
                    <i class="fa-solid fa-file-pdf mr-1"></i> Unduh PDF
                </a>
                @else
                <button @click="exportPdf()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm ml-2 transition">
                    <i class="fa-solid fa-file-pdf mr-1"></i> Ekspor PDF
                </button>
                @endif
            @endif
        </div>
    </header>

    <!-- Main Editor Area -->
    <div class="flex-1 flex overflow-hidden">
        <!-- WYSIWYG / PDF Container -->
        <div class="flex-1 flex flex-col bg-slate-100 border-none relative overflow-hidden">
            @if($doc->file_path)
                @php $pdfUrl = route('documents.preview', ['id' => $doc->id]); @endphp
                {{-- PDF Toolbar --}}
                <div class="flex items-center justify-between px-4 py-2 bg-slate-800 text-white shrink-0">
                    <div class="flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-file-pdf text-red-400"></i>
                        <span class="text-slate-300 text-xs">Dokumen PDF — ditandatangani offline</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ $pdfUrl }}" target="_blank"
                           class="text-xs px-3 py-1.5 bg-slate-700 hover:bg-slate-600 rounded-md transition flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i> Buka di Tab Baru
                        </a>
                        <a href="{{ $pdfUrl }}" download
                           class="text-xs px-3 py-1.5 bg-red-600 hover:bg-red-700 rounded-md transition flex items-center gap-1.5">
                            <i class="fa-solid fa-download text-[11px]"></i> Unduh
                        </a>
                    </div>
                </div>
                {{-- PDF Iframe Viewer --}}
                <iframe
                    id="pdf-viewer-iframe"
                    src="{{ $pdfUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH"
                    class="w-full border-none bg-white"
                    style="flex: 1 1 0%; min-height: 0; height: 100%;"
                    loading="lazy"
                    title="PDF Viewer">
                </iframe>
                {{-- Fallback for browsers that block inline PDF --}}
                <div id="pdf-fallback" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-white text-center p-8">
                    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-file-pdf text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-slate-800 mb-2">Browser tidak dapat menampilkan PDF secara langsung</h3>
                    <p class="text-sm text-slate-500 mb-5">Silakan gunakan salah satu tombol di bawah untuk melihat atau mengunduh dokumen.</p>
                    <div class="flex gap-3">
                        <a href="{{ $pdfUrl }}" target="_blank" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm rounded-lg font-medium transition">
                            <i class="fa-solid fa-arrow-up-right-from-square mr-2"></i> Buka di Tab Baru
                        </a>
                        <a href="{{ $pdfUrl }}" download class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg font-medium transition">
                            <i class="fa-solid fa-download mr-2"></i> Unduh PDF
                        </a>
                    </div>
                </div>
                <script>
                    // Detect if iframe failed to load (e.g. browser blocks inline PDF)
                    document.getElementById('pdf-viewer-iframe').addEventListener('load', function() {
                        try {
                            // If contentDocument is accessible but empty or errored, show fallback
                            const doc = this.contentDocument || this.contentWindow.document;
                            if (!doc || doc.body.innerHTML === '') {
                                showPdfFallback();
                            }
                        } catch(e) {
                            // Cross-origin or other error — PDF likely loaded fine in iframe, ignore
                        }
                    });
                    document.getElementById('pdf-viewer-iframe').addEventListener('error', showPdfFallback);
                    function showPdfFallback() {
                        document.getElementById('pdf-viewer-iframe').classList.add('hidden');
                        document.getElementById('pdf-fallback').classList.remove('hidden');
                    }
                </script>
            @else
                <textarea id="editor-area" class="w-full h-full opacity-0 border-none">{!! $doc->content !!}</textarea>
            @endif
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
            <div x-show="activeTab==='comments'" class="flex-1 flex flex-col min-h-0" style="display:none;">
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    @forelse($doc->comments->sortByDesc('created_at') as $c)
                    <div class="comment-block bg-slate-50 rounded-lg p-3 border border-slate-200 cursor-pointer hover:bg-slate-100 transition {{ $c->is_resolved ? 'opacity-60' : '' }}" @click="scrollToAnchor('{{ $c->anchor_id }}')">
                        <div class="flex items-start gap-2 mb-2">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($c->user->name) }}&background=0D8ABC&color=fff" class="w-6 h-6 rounded-full mt-0.5" alt="">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="text-xs font-semibold text-slate-700">{{ $c->user->name }}</div>
                                    @if($c->is_resolved)
                                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700 ml-2"><i class="fa-solid fa-check mr-1"></i>Selesai</span>
                                    @else
                                        @if($canEdit)
                                        <button @click.stop="resolveComment({{ $c->id }}, '{{ $c->anchor_id }}', $event.currentTarget.closest('.comment-block'))" class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-200 hover:bg-green-500 hover:text-white text-slate-600 transition ml-2"><i class="fa-solid fa-check mr-1"></i>Selesaikan</button>
                                        @endif
                                    @endif
                                </div>
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
                        <div class="text-xs text-slate-400 mb-1">{{ $party->user->name }} ({{ $party->role_type === 'client' ? 'Mitra' : 'Unit Pengusul' }})</div>
                        @if($party->signature_path === 'offline_signed')
                        <div class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded border border-blue-200 inline-block mt-1">
                            <i class="fa-solid fa-file-signature mr-1"></i> Ditandatangani Offline
                        </div>
                        @elseif($party->signature_path)
                        <div class="relative inline-block">
                            <img src="{{ Storage::url($party->signature_path) }}" class="h-12" alt="Signature">
                            @if($party->stamp_path)
                            <img src="{{ Storage::url($party->stamp_path) }}" class="absolute -bottom-1 -right-1 h-10 w-10 object-contain opacity-80" alt="Stempel" title="Stempel Keabsahan">
                            @endif
                        </div>
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

    {{-- Modal Konfirmasi Perubahan Belum Tersimpan --}}
    <div x-show="showUnsavedModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden" @click.away="cancelLeave()" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="px-6 pt-6 pb-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Perubahan Belum Tersimpan</h3>
                        <p class="text-sm text-slate-500">Anda memiliki perubahan yang belum disimpan.</p>
                    </div>
                </div>
                <p class="text-sm text-slate-600 mt-2">Apakah Anda ingin menyimpan perubahan sebelum keluar dari halaman ini?</p>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex justify-end gap-2">
                <button @click="cancelLeave()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                    Batal
                </button>
                <button @click="discardAndLeave()" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                    <i class="fa-solid fa-trash-can mr-1"></i> Buang Perubahan
                </button>
                <button @click="confirmSaveAndLeave()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition">
                    <i class="fa-regular fa-floppy-disk mr-1"></i> Simpan & Keluar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/vendor/tinymce/tinymce.min.js"></script>
<script>
function editorPage() {
    return {
        activeTab: 'history',
        docId: {{ $doc->id }},
        showSignModal: false,
        showSendModal: false,
        showUnsavedModal: false,
        pendingNavigationUrl: null,
        isDirty: false,
        allowUpload: false,
        signaturePreview: null,
        signatureFile: null,
        stampPreview: null,
        stampFile: null,
        activeCommentAnchor: null,
        activeCommentQuote: null,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        init() {
            const canEdit = @json($canEdit);
            const canImportDocx = @json($canImportDocx ?? false);
            const editorArea = document.getElementById('editor-area');

            if (editorArea) {
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

                                        if (window.mammoth && mammoth.transforms && mammoth.transforms.paragraph) {
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

                                        if (window.mammoth) {
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
                                        } else {
                                            alert('Library mammoth belum dimuat. Tidak dapat mengimport DOCX.');
                                        }
                                    };
                                    reader.readAsArrayBuffer(file);
                                }
                            };
                            input.click();
                        }
                    });

                    // Menu Dropdown untuk merubah ukuran kertas / canvas
                    editor.ui.registry.addMenuButton('papersize', {
                        text: 'Ukuran Kertas',
                        icon: 'document-properties',
                        fetch: function (callback) {
                            var items = [
                                {
                                    type: 'menuitem',
                                    text: 'A4 Portrait (21cm x 29.7cm)',
                                    onAction: function () {
                                        editor.dom.setStyle(editor.getBody(), 'width', '21cm');
                                        editor.dom.setStyle(editor.getBody(), 'min-height', '29.7cm');
                                        editor.dom.setStyle(editor.getBody(), 'padding', '2.5cm 2cm');
                                    }
                                },
                                {
                                    type: 'menuitem',
                                    text: 'A4 Landscape (29.7cm x 21cm)',
                                    onAction: function () {
                                        editor.dom.setStyle(editor.getBody(), 'width', '29.7cm');
                                        editor.dom.setStyle(editor.getBody(), 'min-height', '21cm');
                                        editor.dom.setStyle(editor.getBody(), 'padding', '2cm 2.5cm');
                                    }
                                },
                                {
                                    type: 'menuitem',
                                    text: 'F4 / Folio (21.5cm x 33cm)',
                                    onAction: function () {
                                        editor.dom.setStyle(editor.getBody(), 'width', '21.5cm');
                                        editor.dom.setStyle(editor.getBody(), 'min-height', '33cm');
                                        editor.dom.setStyle(editor.getBody(), 'padding', '2.5cm 2cm');
                                    }
                                },
                                {
                                    type: 'menuitem',
                                    text: 'Auto Adjust (Fluid Canvas)',
                                    onAction: function () {
                                        editor.dom.setStyle(editor.getBody(), 'width', 'calc(100% - 4rem)');
                                        editor.dom.setStyle(editor.getBody(), 'min-height', '100vh');
                                        editor.dom.setStyle(editor.getBody(), 'padding', '2rem');
                                    }
                                }
                            ];
                            callback(items);
                        }
                    });

                    // Context menu for comments
                    editor.ui.registry.addMenuItem('addcomment', {
                        text: 'Berikan Komentar',
                        icon: 'comment',
                        onAction: () => {
                            const selectionText = editor.selection.getContent({format:'text'});
                            if (!selectionText) return;
                            const commentId = 'mark_' + Date.now();
                            const content = `<span id="${commentId}" style="background-color:#fef08a;padding:2px 0;" data-comment-id="${commentId}">${editor.selection.getContent()}</span>`;
                            editor.selection.setContent(content);
                            
                            window.dispatchEvent(new CustomEvent('init-comment', {detail: {id: commentId, text: selectionText}}));
                        }
                    });
                    editor.ui.registry.addContextMenu('selection', {
                        update: () => !editor.selection.isCollapsed() ? 'addcomment' : ''
                    });

                    // Tambahkan shortcut ctrl+s ke dalam editor iframe
                    editor.addShortcut('meta+s', 'Simpan dokumen', () => {
                        this.saveContent(true);
                    });
                };

                let toolbarConfig = false;
                if (canEdit) {
                    const importBtn = (canImportDocx ? ' importdocx |' : '');
                    toolbarConfig = `undo redo | papersize${importBtn} blocks fontfamily fontsize | bold italic underline strikethrough | align numlist bullist | link image | table media | lineheight outdent indent | forecolor backcolor removeformat | charmap emoticons | fullscreen preview | help`;
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
                            transition: width 0.3s ease, min-height 0.3s ease, padding 0.3s ease;
                        }
                        html {
                            background-color: #f1f5f9;
                            scroll-behavior: smooth;
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

                // Track dirty state via TinyMCE change events
                if (canEdit) {
                    const self = this;
                    const checkDirty = () => {
                        const ed = tinymce.get('editor-area');
                        if (ed) {
                            ed.on('change input keyup', () => { self.isDirty = true; });
                        } else {
                            setTimeout(checkDirty, 200);
                        }
                    };
                    checkDirty();
                }

                // Browser tab close / reload guard
                window.addEventListener('beforeunload', (e) => {
                    if (this.isDirty) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });

                // Ctrl+S shortcut untuk simpan dokumen
                document.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                        e.preventDefault();
                        this.saveContent(true);
                    }
                });

                window.addEventListener('init-comment', (e) => {
                    this.activeCommentAnchor = e.detail.id;
                    this.activeCommentQuote = e.detail.text;
                    this.activeTab = 'comments';
                    setTimeout(() => document.getElementById('comment-input')?.focus(), 100);
                });

                window.addEventListener('scroll-to-anchor', (e) => {
                    this.scrollToAnchor(e.detail);
                });
            }
        },

        showToast(message, type = 'success') {
            // Remove existing toast
            document.getElementById('save-toast')?.remove();

            const iconMap = {
                success: 'fa-circle-check text-green-500',
                info: 'fa-circle-info text-blue-500',
                warning: 'fa-triangle-exclamation text-amber-500',
            };
            const bgMap = {
                success: 'bg-green-50 border-green-200',
                info: 'bg-blue-50 border-blue-200',
                warning: 'bg-amber-50 border-amber-200',
            };

            const toast = document.createElement('div');
            toast.id = 'save-toast';
            toast.className = `fixed top-5 right-5 z-[200] flex items-center gap-3 px-5 py-3 rounded-xl border shadow-lg ${bgMap[type] || bgMap.success} transition-all duration-300 opacity-0 translate-y-[-10px]`;
            toast.innerHTML = `<i class="fa-solid ${iconMap[type] || iconMap.success} text-lg"></i><span class="text-sm font-medium text-slate-800">${message}</span>`;
            document.body.appendChild(toast);

            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('opacity-0', 'translate-y-[-10px]');
                toast.classList.add('opacity-100', 'translate-y-0');
            });

            // Animate out after 2.5s
            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-[-10px]');
                setTimeout(() => toast.remove(), 300);
            }, 2500);
        },

        navigateBack(url) {
            if (this.isDirty) {
                this.pendingNavigationUrl = url;
                this.showUnsavedModal = true;
            } else {
                window.location.href = url;
            }
        },

        async confirmSaveAndLeave() {
            await this.saveContent(false);
            this.isDirty = false;
            this.showUnsavedModal = false;
            window.location.href = this.pendingNavigationUrl;
        },

        discardAndLeave() {
            this.isDirty = false;
            this.showUnsavedModal = false;
            window.location.href = this.pendingNavigationUrl;
        },

        cancelLeave() {
            this.pendingNavigationUrl = null;
            this.showUnsavedModal = false;
        },

        async saveContent(showAlertAndReload = true) {
            if (!document.getElementById('editor-area')) return;
            const editor = tinymce.get('editor-area');
            if (!editor) return;
            const content = editor.getContent();
            const res = await fetch(`/documents/${this.docId}/content`, {
                method: 'PUT',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken},
                body: JSON.stringify({content})
            });
            if (res.ok) {
                this.isDirty = false;
                const data = await res.json();

                if (showAlertAndReload) {
                    if (data.changed) {
                        this.showToast('Dokumen berhasil disimpan', 'success');

                        // Tambah entry history di sidebar hanya jika konten berubah
                        const user_name = @json(Auth::user()->name ?? 'User');
                        const historyContainer = document.querySelector(`[x-show="activeTab==='history'"]`);
                        if(historyContainer) {
                            const hHtml = `
                            <div class="relative pl-4 border-l-2 border-slate-200 pb-2">
                                <div class="absolute -left-[5px] top-0 w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                <div class="text-xs text-slate-400 mb-1">Baru saja</div>
                                <div class="text-sm font-medium text-slate-800">${user_name}</div>
                                <div class="text-xs text-slate-600 mt-1">Konten diperbarui</div>
                            </div>`;
                            historyContainer.insertAdjacentHTML('afterbegin', hHtml);
                        }
                    } else {
                        this.showToast('Tidak ada perubahan untuk disimpan', 'info');
                    }
                }
            }
        },

        async confirmSendToUnit() {
            // Auto-save sebelum mengubah status/mengirim
            if (document.getElementById('editor-area')) {
                await this.saveContent(false);
            }

            const res = await fetch(`/documents/${this.docId}/status`, {
                method: 'PUT',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken},
                body: JSON.stringify({status:'review_unit'})
            });
            if (res.ok) {
                this.showSendModal = false;
                window.location.href = '{{ route("documents.index") }}';
            }
        },

        async rejectDocument() {
            if(!confirm('Apakah Anda yakin ingin menolak dokumen ini? Dokumen akan dikembalikan untuk direvisi dan tanda tangan direset.')) return;
            
            // Auto-save sebelum menolak jika editor aktif agar editan tersimpan
            if (document.getElementById('editor-area')) {
                await this.saveContent(false);
            }

            const res = await fetch(`/documents/${this.docId}/reject`, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN':this.csrfToken}
            });
            if (res.ok) {
                window.location.href = '{{ route("documents.index") }}';
            }
        },

        scrollToAnchor(anchorId) {
            if (document.getElementById('editor-area')) {
                const editor = tinymce.get('editor-area');
                if (!editor || !anchorId) return;
                const el = editor.getWin().document.getElementById(anchorId);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // blink effect
                    const oldBg = el.style.backgroundColor;
                    el.style.backgroundColor = '#fca5a5'; // red-300
                    setTimeout(() => el.style.backgroundColor = oldBg, 1500);
                }
            }
        },

        async postComment() {
            const input = document.getElementById('comment-input');
            const text = input.value;
            if (!text) return;

            if (this.activeCommentAnchor && document.getElementById('editor-area')) {
                await this.saveContent(false);
            }

            const currentQuote = document.getElementById('editor-area') ? this.activeCommentQuote : null;
            const currentAnchor = document.getElementById('editor-area') ? this.activeCommentAnchor : null;

            const res = await fetch(`/documents/${this.docId}/comments`, {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken},
                body: JSON.stringify({
                    text: text,
                    quote: currentQuote,
                    anchor_id: currentAnchor
                })
            });
            if (res.ok) { 
                const data = await res.json();
                input.value = ''; 
                this.activeCommentAnchor = null;
                this.activeCommentQuote = null;
                
                const user_name = @json(Auth::user()->name ?? 'User');
                const avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(user_name)}&background=0D8ABC&color=fff`;
                
                let quoteHtml = '';
                if (currentQuote) {
                    quoteHtml = `<div class="mb-2 pl-2 border-l-2 border-yellow-400 bg-yellow-50 text-xs text-slate-600 italic py-1">"${currentQuote}"</div>`;
                }

                const clickAction = currentAnchor ? `onclick="window.dispatchEvent(new CustomEvent('scroll-to-anchor', {detail: '${currentAnchor}'}))"` : '';
                
                const newHtml = `
                    <div class="comment-block bg-slate-50 rounded-lg p-3 border border-slate-200 cursor-pointer hover:bg-slate-100 transition" ${clickAction}>
                        <div class="flex items-start gap-2 mb-2">
                            <img src="${avatar}" class="w-6 h-6 rounded-full mt-0.5" alt="">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="text-xs font-semibold text-slate-700">${user_name}</div>
                                    <button onclick="event.stopPropagation(); document.querySelector('[x-data]').__x.$data.resolveComment(${data.comment.id}, '${currentAnchor || ''}', this.closest('.comment-block'))" class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-200 hover:bg-green-500 hover:text-white text-slate-600 transition ml-2"><i class="fa-solid fa-check mr-1"></i>Selesaikan</button>
                                </div>
                                <div class="text-xs text-slate-400">Baru saja</div>
                            </div>
                        </div>
                        ${quoteHtml}
                        <div class="text-sm text-slate-700 whitespace-pre-wrap">${text}</div>
                    </div>
                `;
                
                const container = document.querySelector(`[x-show="activeTab==='comments'"] .overflow-y-auto`);
                if(container) {
                    const emptyMsg = container.querySelector('.text-center');
                    if(emptyMsg) emptyMsg.remove();
                    container.insertAdjacentHTML('afterbegin', newHtml);
                }
                
                const historyContainer = document.querySelector(`[x-show="activeTab==='history'"]`);
                if(historyContainer) {
                    const hHtml = `
                    <div class="relative pl-4 border-l-2 border-slate-200 pb-2">
                        <div class="absolute -left-[5px] top-0 w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                        <div class="text-xs text-slate-400 mb-1">Baru saja</div>
                        <div class="text-sm font-medium text-slate-800">${user_name}</div>
                        <div class="text-xs text-slate-600 mt-1">Menambahkan komentar</div>
                    </div>`;
                    historyContainer.insertAdjacentHTML('afterbegin', hHtml);
                }
            }
        },

        async resolveComment(commentId, anchorId, el) {
            if(!confirm('Tandai komentar ini sebagai selesai?')) return;
            const res = await fetch(`/documents/${this.docId}/comments/${commentId}/resolve`, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN':this.csrfToken}
            });
            if (res.ok) {
                if (el) {
                    const btn = el.querySelector('button');
                    if (btn) {
                        const badge = document.createElement('span');
                        badge.className = 'text-[10px] font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700 ml-2';
                        badge.innerHTML = '<i class="fa-solid fa-check mr-1"></i>Selesai';
                        btn.replaceWith(badge);
                    }
                    el.classList.add('opacity-60');
                }

                if (anchorId && document.getElementById('editor-area')) {
                    const editor = tinymce.get('editor-area');
                    if (editor) {
                        const span = editor.getWin().document.getElementById(anchorId);
                        if (span) {
                            const textContent = span.innerHTML;
                            span.insertAdjacentHTML('beforebegin', textContent);
                            span.remove();
                            this.saveContent(false);
                        }
                    }
                }

                const user_name = @json(Auth::user()->name ?? 'User');
                const historyContainer = document.querySelector(`[x-show="activeTab==='history'"]`);
                if(historyContainer) {
                    const hHtml = `
                    <div class="relative pl-4 border-l-2 border-slate-200 pb-2">
                        <div class="absolute -left-[5px] top-0 w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                        <div class="text-xs text-slate-400 mb-1">Baru saja</div>
                        <div class="text-sm font-medium text-slate-800">${user_name}</div>
                        <div class="text-xs text-slate-600 mt-1">Menyelesaikan komentar</div>
                    </div>`;
                    historyContainer.insertAdjacentHTML('afterbegin', hHtml);
                }
            }
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
                if (!file.type.startsWith('image/')) { alert('Mohon upload file gambar'); return; }
                if (file.size > 2*1024*1024) { alert('Maksimal 2MB'); return; }
                this.signatureFile = file;
                const reader = new FileReader();
                reader.onload = (e) => { this.signaturePreview = e.target.result; };
                reader.readAsDataURL(file);
            }
        },

        previewStamp(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) { alert('Mohon upload file gambar'); return; }
            if (file.size > 2*1024*1024) { alert('Ukuran file maksimal 2MB'); return; }
            this.stampFile = file;
            const reader = new FileReader();
            reader.onload = (e) => { this.stampPreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        handleStampDrop(event) {
            const file = event.dataTransfer.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) { alert('Mohon upload file gambar'); return; }
                if (file.size > 2*1024*1024) { alert('Maksimal 2MB'); return; }
                this.stampFile = file;
                const reader = new FileReader();
                reader.onload = (e) => { this.stampPreview = e.target.result; };
                reader.readAsDataURL(file);
            }
        },

        async submitSignature() {
            if (!this.signatureFile) { alert('Pilih file tanda tangan terlebih dahulu.'); return; }

            // Auto-save sebelum tanda tangan jika editor aktif
            if (document.getElementById('editor-area')) {
                await this.saveContent(false);
            }

            const formData = new FormData();
            formData.append('signature_file', this.signatureFile);
            if (this.stampFile) {
                formData.append('stamp_file', this.stampFile);
            }
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
            // Menyamakan style font dengan editor TinyMCE
            el.style.fontFamily = 'Tahoma, "Times New Roman", Times, serif';
            el.style.fontSize = '11pt';
            el.style.lineHeight = '1.5';
            el.style.color = '#000000';
            el.innerHTML = content;

            // MENGHAPUS HIGHLIGHT KUNING: 
            // Kita cari semua elemen <span> yang punya data-comment-id dan kita "unwrap" isinya
            const marks = el.querySelectorAll('span[data-comment-id]');
            marks.forEach(span => {
                const textContent = span.innerHTML;
                span.insertAdjacentHTML('beforebegin', textContent);
                span.remove();
            });

            // Menggunakan path relatif (/storage/...) agar html2canvas tidak terblokir CORS
            const parties = {!! json_encode($doc->parties->map(fn($p) => [
                'name' => $p->user->name,
                'role' => $p->role_type,
                'mitra_name' => $p->user->nama_mitra,
                'sig' => $p->signature_path ? '/storage/' . $p->signature_path : null,
                'stamp' => $p->stamp_path ? '/storage/' . $p->stamp_path : null
            ])) !!};
            let sigBlock = '<div style="margin-top:60px;display:flex;justify-content:space-between;page-break-inside:avoid;">';
            parties.forEach((p,i) => {
                let imagesHtml = '<div style="height:90px; position:relative; width:100%; display:flex; justify-content:center; align-items:center;">';
                if (p.sig) {
                    // Tanda tangan di layer 1 (bawah)
                    imagesHtml += `<img src="${p.sig}" style="height:80px; position:relative; z-index:10;" />`;
                }
                if (p.stamp) {
                    // Stempel di layer 2 (atas), menimpa 1/4 bagian kanan tanda tangan
                    imagesHtml += `<img src="${p.stamp}" style="height:80px; width:80px; object-fit:contain; position:absolute; z-index:50; margin-left:50px; margin-top:10px;" />`;
                }
                imagesHtml += '</div>';

                let titleText = p.role === 'client' ? (p.mitra_name || p.name) : 'STIMATA';

                sigBlock += `<div style="text-align:center;width:45%;">
                    <p style="margin-bottom:20px;font-weight:bold;text-transform:uppercase;">${titleText}</p>
                    ${imagesHtml}
                    <p style="margin-top:20px;font-weight:bold;text-decoration:underline;">${p.name}</p>
                </div>`;
            });
            sigBlock += '</div>';
            el.insertAdjacentHTML('beforeend', sigBlock);

            if (typeof html2pdf !== 'undefined') {
                html2pdf().set({
                    margin: 0.75, // Margin 0.75 inci untuk seluruh sisi (kiri, kanan, atas, bawah)
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
            } else {
                alert('Library html2pdf belum dimuat.');
            }
        }
    }
}
</script>
</body>
</html>
