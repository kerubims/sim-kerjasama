<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editor — {{ $doc['title'] ?? 'Dokumen' }} — SIM-KERMA</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-800 h-screen overflow-hidden flex flex-col font-[Inter]">

@php
    $user = Auth::user();
    
    // Fallback logic for new document
    $statusLabel = 'DRAFT BARU';
    if ($doc) {
        $statusLabel = match($doc->status) {
            'signed' => 'AKTIF',
            'draft' => 'DRAFT',
            'review_client' => 'REVIEW CLIENT',
            'review_unit' => 'REVIEW UNIT',
            default => strtoupper(str_replace('_', ' ', $doc->status)),
        };
    }
@endphp

<div class="flex h-screen flex-col bg-white" x-data="editorPage()">
    <!-- Editor Header -->
    <header class="border-b border-slate-200 px-6 py-3 flex justify-between items-center bg-white z-10 shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('documents.index') }}" class="text-slate-500 hover:text-slate-800 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-900">{{ $doc ? $doc->title : 'Dokumen Baru' }}</h1>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200">
                        {{ $statusLabel }}
                    </span>
                    @if($doc)
                    <span>Last saved: {{ $doc->updated_at->format('H:i:s') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($doc)
                @if($doc->status === 'draft')
                <button @click="updateStatus('review_client')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                    <i class="fa-solid fa-paper-plane mr-2"></i> Kirim ke Review Client
                </button>
                @elseif(in_array($doc->status, ['review_client', 'review_unit']))
                <button @click="updateStatus('draft')" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                    <i class="fa-solid fa-rotate-left mr-2"></i> Kembalikan Draft
                </button>
                <button @click="showSignModal = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                    <i class="fa-solid fa-check mr-2"></i> Approve & Sign
                </button>
                @endif
                <button @click="saveContent()" class="border border-slate-300 text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-md text-sm font-medium transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan
                </button>
            @else
                <button disabled class="opacity-50 border border-slate-300 text-slate-600 px-4 py-2 rounded-md text-sm font-medium">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan
                </button>
            @endif
        </div>
    </header>

    <!-- Main Editor Area -->
    <div class="flex-1 flex overflow-hidden">
        <!-- WYSIWYG Container -->
        <div class="flex-1 bg-slate-100 overflow-y-auto p-8 flex justify-center">
            <div class="w-full max-w-[816px] bg-white shadow-sm min-h-[1056px] p-0">
                <textarea id="editor-area" class="w-full h-full opacity-0">{!! $doc ? $doc->content : '' !!}</textarea>
            </div>
        </div>

        <!-- Sidebar: History & Comments -->
        <div class="w-80 border-l border-slate-200 bg-white flex flex-col shrink-0">
            <!-- Tab Headers -->
            <div class="flex border-b border-slate-200 shrink-0">
                <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : 'text-slate-600 hover:bg-slate-50'"
                        class="flex-1 px-4 py-3 text-sm font-semibold transition">
                    <i class="fa-solid fa-clock-rotate-left mr-1"></i> Riwayat
                </button>
                <button @click="activeTab = 'comments'"
                        :class="activeTab === 'comments' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : 'text-slate-600 hover:bg-slate-50'"
                        class="flex-1 px-4 py-3 text-sm font-semibold transition">
                    <i class="fa-solid fa-comments mr-1"></i> Komentar
                    @if($doc && $doc->comments->count() > 0)
                    <span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full">{{ $doc->comments->count() }}</span>
                    @endif
                </button>
            </div>

            <!-- History Tab Content -->
            <div x-show="activeTab === 'history'" class="flex-1 overflow-y-auto p-4 space-y-6">
                @foreach($doc ? $doc->histories->sortByDesc('created_at') : [] as $h)
                <div class="relative pl-4 border-l-2 border-slate-200 pb-2">
                    <div class="absolute -left-[5px] top-0 w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                    <div class="text-xs text-slate-400 mb-1">{{ $h->created_at->format('d M Y H:i') }}</div>
                    <div class="text-sm font-medium text-slate-800">{{ $h->user->name }}</div>
                    <div class="text-xs text-slate-600 mt-1">{{ $h->message }}</div>
                </div>
                @endforeach
            </div>

            <!-- Comments Tab Content -->
            <div x-show="activeTab === 'comments'" class="flex-1 flex flex-col" style="display: none;">
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    @forelse($doc ? $doc->comments->sortByDesc('created_at') : [] as $c)
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

                <!-- Comment Input -->
                <div class="p-4 border-t border-slate-200 bg-slate-50 shrink-0">
                    <textarea id="comment-text" placeholder="Tulis komentar atau catatan..." class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" rows="3"></textarea>
                    <button @click="postComment()" class="mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        <i class="fa-solid fa-paper-plane mr-1"></i> Kirim Komentar
                    </button>
                </div>
            </div>

            <!-- Signatures Preview -->
            <div class="p-4 border-t border-slate-200 bg-slate-50 shrink-0">
                <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">Tanda Tangan Pihak Terlibat</h4>
                <div class="space-y-3">
                    @if($doc)
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
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sign Modal -->
    <div x-show="showSignModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.outside="showSignModal = false">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Upload Tanda Tangan</h3>
                <button @click="showSignModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="p-6">
                <p class="text-sm text-slate-600 mb-4">Silakan unggah gambar tanda tangan Anda untuk menyetujui dokumen ini. (Maks. 2MB, format gambar)</p>
                <input type="file" id="signature-file" accept="image/*" class="w-full border border-slate-300 rounded p-2 text-sm focus:outline-none focus:border-blue-500 mb-4">
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2 rounded-b-lg">
                <button @click="showSignModal = false" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200 rounded-md transition">Batal</button>
                <button @click="submitSignature()" class="px-4 py-2 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-md transition"><i class="fa-solid fa-upload mr-1"></i> Upload & Setujui</button>
            </div>
        </div>
    </div>
</div>

<script src="/vendor/tinymce/tinymce.min.js"></script>
<script>
function editorPage() {
    return {
        activeTab: 'history',
        docId: {{ $doc ? $doc->id : 'null' }},
        showSignModal: false,
        init() {
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#editor-area',
                    height: 1056,
                    license_key: 'gpl',
                    menubar: 'file edit view insert format tools table',
                    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
                    toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | removeformat | help',
                    content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; padding: 40px 60px; }',
                    branding: false,
                    promotion: false,
                    skin: 'oxide',
                    statusbar: true,
                    resize: false,
                    setup: function(editor) {
                        editor.on('init', function() {
                            document.querySelector('#editor-area').style.opacity = '1';
                        });
                    }
                });
            }
        },
        async saveContent() {
            if(!this.docId) return;
            const content = tinymce.get('editor-area').getContent();
            try {
                const res = await fetch(`/documents/${this.docId}/content`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ content })
                });
                if(res.ok) {
                    window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', message: 'Dokumen berhasil disimpan.'}}));
                    setTimeout(() => window.location.reload(), 1000);
                }
            } catch (e) {
                console.error(e);
            }
        },
        async updateStatus(status) {
            if(!this.docId) return;
            try {
                const res = await fetch(`/documents/${this.docId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: status, allowUpload: true })
                });
                if(res.ok) {
                    window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', message: 'Status berhasil diperbarui.'}}));
                    setTimeout(() => window.location.reload(), 1000);
                }
            } catch (e) {
                console.error(e);
            }
        },
        async postComment() {
            if(!this.docId) return;
            const text = document.getElementById('comment-text').value;
            if(!text) return;
            try {
                const res = await fetch(`/documents/${this.docId}/comments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ text })
                });
                if(res.ok) {
                    document.getElementById('comment-text').value = '';
                    window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', message: 'Komentar ditambahkan.'}}));
                    setTimeout(() => window.location.reload(), 1000);
                }
            } catch (e) {
                console.error(e);
            }
        },
        async submitSignature() {
            if(!this.docId) return;
            const fileInput = document.getElementById('signature-file');
            if(fileInput.files.length === 0) {
                alert('Pilih file tanda tangan terlebih dahulu.');
                return;
            }
            
            const formData = new FormData();
            formData.append('signature_file', fileInput.files[0]);
            
            try {
                const res = await fetch(`/documents/${this.docId}/sign`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                if(res.ok) {
                    this.showSignModal = false;
                    window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', message: 'Tanda tangan berhasil diunggah.'}}));
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    const data = await res.json();
                    alert(data.message || 'Gagal mengunggah tanda tangan');
                }
            } catch (e) {
                console.error(e);
                alert('Terjadi kesalahan.');
            }
        }
    }
}
</script>
</body>
</html>
