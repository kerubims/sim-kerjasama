@extends('layouts.app')

@section('title', 'Dokumen Kerjasama')
@section('page-title', 'Dokumen Kerjasama')

@section('header-actions')
<button onclick="document.getElementById('modal-create-doc').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
    <i class="fa-solid fa-plus mr-2"></i> Buat Draft Baru
</button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500">
                <tr>
                    <th class="px-6 py-4">No</th>
                    <th class="px-6 py-4">Judul</th>
                    <th class="px-6 py-4">Jenis</th>
                    <th class="px-6 py-4">Client</th>
                    <th class="px-6 py-4">Unit</th>
                    <th class="px-6 py-4">Tanggal Dibuat</th>
                    <th class="px-6 py-4">Tanggal Selesai</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($documents as $idx => $doc)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 text-slate-500">{{ $documents->firstItem() + $idx }}</td>
                    <td class="px-6 py-4 font-medium text-slate-900">
                        @if($doc->parent_id)
                            <i class="fa-solid fa-turn-up fa-rotate-90 text-slate-400 mr-2"></i>
                        @endif
                        {{ $doc->title }}
                        @if($doc->parent_id)
                            <div class="text-xs text-slate-500 mt-1">Rujukan: {{ $doc->parent->title }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $typeClass = match(strtolower($doc->type)) {
                                'mou' => 'bg-blue-100 text-blue-700',
                                'moa' => 'bg-green-100 text-green-700',
                                default => 'bg-purple-100 text-purple-700',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded text-xs font-medium {{ $typeClass }}">{{ strtoupper($doc->type) }}</span>
                    </td>
                    <td class="px-6 py-4">{{ $doc->parties->where('role_type', 'client')->first()->user->name ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $doc->parties->where('role_type', 'unit_pengusul')->first()->user->name ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $doc->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4 font-medium text-slate-600">{{ $doc->end_date ? \Carbon\Carbon::parse($doc->end_date)->format('d M Y') : '-' }}</td>
                    <td class="px-6 py-4">
                        @php
                            $statusClass = match($doc->status) {
                                'signed' => 'bg-green-100 text-green-700',
                                'draft' => 'bg-slate-100 text-slate-600',
                                default => 'bg-yellow-100 text-yellow-700',
                            };
                            $statusLabel = match($doc->status) {
                                'signed' => 'Aktif',
                                'draft' => 'DRAFT',
                                'review_client' => 'REVIEW CLIENT',
                                'review_unit' => 'REVIEW UNIT',
                                default => strtoupper(str_replace('_', ' ', $doc->status)),
                            };
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('documents.editor', ['id' => $doc->id]) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                            <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-8 text-center text-slate-400">Belum ada dokumen</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50">
        <div class="w-full">
            {{ $documents->links() }}
        </div>
    </div>
</div>

<!-- Modal Create Document -->
<div id="modal-create-doc" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden transform transition-all">
        <form action="{{ route('documents.store') }}" method="POST">
            @csrf
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">
                <h3 class="text-lg font-semibold leading-6 text-slate-900 mb-4">Buat Draft Kerjasama Baru</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Judul Kerjasama</label>
                        <input type="text" name="title" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan judul...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Dokumen</label>
                        <select name="type" id="input-type" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="mou">MoU (Memorandum of Understanding)</option>
                            <option value="moa">MoA (Memorandum of Agreement)</option>
                            <option value="ia">IA (Implementation Arrangement)</option>
                        </select>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium text-slate-700">Pihak-pihak dalam Kerjasama</label>
                            <button type="button" onclick="addParty()" class="text-xs text-blue-600 hover:text-blue-800 font-medium"><i class="fa-solid fa-plus mr-1"></i> Tambah Pihak</button>
                        </div>
                        <div id="parties-container" class="space-y-3">
                            <div class="party-item flex items-center gap-2">
                                <select name="parties[]" required class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih Pihak Pertama --</option>
                                    <optgroup label="Unit Pengusul (Internal)">
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Client / Mitra (Eksternal)">
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <button type="button" class="text-slate-300 cursor-not-allowed px-2 py-2" disabled><i class="fa-solid fa-trash"></i></button>
                            </div>
                            <div class="party-item flex items-center gap-2">
                                <select name="parties[]" required class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih Pihak Kedua --</option>
                                    <optgroup label="Unit Pengusul (Internal)">
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Client / Mitra (Eksternal)">
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <button type="button" class="text-slate-300 cursor-not-allowed px-2 py-2" disabled><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2"><i class="fa-solid fa-info-circle mr-1"></i> Minimal 2 pihak. Anda dapat menambahkan lebih banyak pihak jika diperlukan.</p>
                    </div>
                    
                    <div id="parent-doc-container" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            <span id="parent-doc-label">Dokumen Rujukan</span>
                            <span class="text-slate-400 text-xs font-normal ml-1">(Opsional)</span>
                        </label>
                        <select name="parent_id" id="input-parent" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Tidak ada rujukan --</option>
                            @foreach($allDocs as $d)
                                <option value="{{ $d->id }}" data-type="{{ $d->type }}" data-status="{{ $d->status }}" class="parent-option hidden">{{ $d->title }} ({{ $d->created_at->format('d M Y') }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            Opsional: Pilih dokumen induk jika dokumen ini merupakan turunan dari dokumen lain.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai</label>
                            <input type="date" name="start_date" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Selesai</label>
                            <input type="date" name="end_date" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button type="submit" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Buat Draft</button>
                <button type="button" onclick="document.getElementById('modal-create-doc').classList.add('hidden')" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('input-type');
        const parentContainer = document.getElementById('parent-doc-container');
        const parentSelect = document.getElementById('input-parent');
        const parentLabel = document.getElementById('parent-doc-label');
        const parentOptions = document.querySelectorAll('.parent-option');

        function updateParentOptions() {
            const type = typeSelect.value;
            
            if (type === 'mou') {
                parentContainer.classList.add('hidden');
                parentSelect.value = '';
            } else {
                parentContainer.classList.remove('hidden');
                if (type === 'moa') {
                    parentLabel.textContent = 'Rujukan MoU';
                } else if (type === 'ia') {
                    parentLabel.textContent = 'Rujukan MoA';
                }

                // Show only signed parents of the correct type
                const targetParentType = type === 'moa' ? 'mou' : 'moa';
                
                parentOptions.forEach(opt => {
                    if (opt.dataset.type === targetParentType && opt.dataset.status === 'signed') {
                        opt.classList.remove('hidden');
                    } else {
                        opt.classList.add('hidden');
                    }
                });
                parentSelect.value = '';
            }
        }

        typeSelect.addEventListener('change', updateParentOptions);
        updateParentOptions();
    });
    function addParty() {
        const container = document.getElementById('parties-container');
        const firstSelectHTML = container.querySelector('select').innerHTML;
        
        const div = document.createElement('div');
        div.className = 'party-item flex items-center gap-2';
        div.innerHTML = `
            <select name="parties[]" required class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                ${firstSelectHTML.replace('-- Pilih Pihak Pertama --', '-- Pilih Pihak Tambahan --')}
            </select>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 px-2 py-2"><i class="fa-solid fa-trash"></i></button>
        `;
        container.appendChild(div);
    }
</script>
@endsection
