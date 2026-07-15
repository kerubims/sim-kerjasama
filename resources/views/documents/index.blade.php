@extends('layouts.app')

@section('title', 'Dokumen Kerjasama')
@section('page-title', 'Dokumen Kerjasama')

@section('header-actions')
@if(Auth::user()->hasRole('super_admin'))
<button onclick="document.getElementById('modal-create-doc').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
    <i class="fa-solid fa-plus mr-2"></i> Buat Draft Baru
</button>
@endif
@endsection

@section('content')
<!-- Filter Toolbar -->
<div class="bg-white px-4 py-3 rounded-xl shadow-sm border border-slate-100 mb-4 flex items-center gap-3 flex-wrap">

    {{-- Filter Icon Button --}}
    <button id="btn-open-filter" type="button"
        onclick="document.getElementById('modal-filter').classList.remove('hidden')"
        class="relative flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-300 text-slate-600 hover:border-blue-500 hover:text-blue-600 text-sm font-medium transition shrink-0">
        <i class="fa-solid fa-sliders"></i>
        <span>Filter</span>
        @php $activeFilters = array_filter(request()->only(['q','type','status','unit','client','created_from','created_to'])); @endphp
        @if(count($activeFilters) > 0)
            <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-blue-600 text-white rounded-full text-[10px] flex items-center justify-center font-bold">{{ count($activeFilters) }}</span>
        @endif
    </button>

    {{-- Active Filter Chips --}}
    <div class="flex items-center gap-2 flex-wrap">
        @if(request('q'))
            <a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => null]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-200 text-blue-700 text-xs font-medium rounded-full hover:bg-blue-100 transition">
                <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                "{{ Str::limit(request('q'), 20) }}"
                <i class="fa-solid fa-xmark text-[10px] ml-0.5"></i>
            </a>
        @endif
        @if(request('type'))
            <a href="{{ request()->fullUrlWithQuery(['type' => null, 'page' => null]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 border border-purple-200 text-purple-700 text-xs font-medium rounded-full hover:bg-purple-100 transition">
                <i class="fa-solid fa-file-contract text-[10px]"></i>
                {{ strtoupper(request('type')) }}
                <i class="fa-solid fa-xmark text-[10px] ml-0.5"></i>
            </a>
        @endif
        @if(request('status'))
            @php
                $statusChipLabel = match(request('status')) {
                    'draft' => 'Draft',
                    'review_client' => 'Review Mitra',
                    'review_unit' => 'Review Unit',
                    'signed' => 'Aktif',
                    'expiring' => 'Masa Tenggang',
                    'kadaluarsa' => 'Kadaluarsa',
                    default => request('status'),
                };
            @endphp
            <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 border border-green-200 text-green-700 text-xs font-medium rounded-full hover:bg-green-100 transition">
                <i class="fa-solid fa-circle-dot text-[10px]"></i>
                {{ $statusChipLabel }}
                <i class="fa-solid fa-xmark text-[10px] ml-0.5"></i>
            </a>
        @endif
        @if(request('unit'))
            @php $unitLabel = $units->firstWhere('id', request('unit'))?->name ?? 'Unit'; @endphp
            <a href="{{ request()->fullUrlWithQuery(['unit' => null, 'page' => null]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 border border-orange-200 text-orange-700 text-xs font-medium rounded-full hover:bg-orange-100 transition">
                <i class="fa-solid fa-building text-[10px]"></i>
                {{ Str::limit($unitLabel, 20) }}
                <i class="fa-solid fa-xmark text-[10px] ml-0.5"></i>
            </a>
        @endif
        @if(request('client'))
            @php $clientLabel = $clients->firstWhere('id', request('client'))?->name ?? 'Mitra'; @endphp
            <a href="{{ request()->fullUrlWithQuery(['client' => null, 'page' => null]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-50 border border-teal-200 text-teal-700 text-xs font-medium rounded-full hover:bg-teal-100 transition">
                <i class="fa-solid fa-handshake text-[10px]"></i>
                {{ Str::limit($clientLabel, 20) }}
                <i class="fa-solid fa-xmark text-[10px] ml-0.5"></i>
            </a>
        @endif
        @if(request('created_from'))
            <a href="{{ request()->fullUrlWithQuery(['created_from' => null, 'page' => null]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 border border-slate-300 text-slate-600 text-xs font-medium rounded-full hover:bg-slate-200 transition">
                <i class="fa-solid fa-calendar-plus text-[10px]"></i>
                Dari {{ \Carbon\Carbon::parse(request('created_from'))->format('d M Y') }}
                <i class="fa-solid fa-xmark text-[10px] ml-0.5"></i>
            </a>
        @endif
        @if(request('created_to'))
            <a href="{{ request()->fullUrlWithQuery(['created_to' => null, 'page' => null]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 border border-slate-300 text-slate-600 text-xs font-medium rounded-full hover:bg-slate-200 transition">
                <i class="fa-solid fa-calendar-minus text-[10px]"></i>
                Hingga {{ \Carbon\Carbon::parse(request('created_to'))->format('d M Y') }}
                <i class="fa-solid fa-xmark text-[10px] ml-0.5"></i>
            </a>
        @endif

        @if(count($activeFilters) > 0)
            <a href="{{ route('documents.index') }}" class="text-xs text-slate-400 hover:text-red-500 transition ml-1">
                <i class="fa-solid fa-rotate-left mr-1"></i> Reset semua
            </a>
        @endif
    </div>
</div>

<!-- Modal Filter Panel -->
<div id="modal-filter" class="fixed inset-0 bg-slate-900/40 hidden items-end sm:items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-2xl w-full sm:max-w-lg overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800 text-sm flex items-center gap-2">
                <i class="fa-solid fa-sliders text-blue-500"></i> Filter Dokumen
            </h3>
            <button type="button" onclick="document.getElementById('modal-filter').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="{{ route('documents.index') }}" method="GET">
            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Cari Judul / Nomor Dokumen</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Masukkan kata kunci..." class="w-full pl-8 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Jenis Dokumen</label>
                        <select name="type" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Semua Jenis</option>
                            <option value="mou" {{ request('type') == 'mou' ? 'selected' : '' }}>MoU</option>
                            <option value="moa" {{ request('type') == 'moa' ? 'selected' : '' }}>MoA</option>
                            <option value="ia" {{ request('type') == 'ia' ? 'selected' : '' }}>IA</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Status</label>
                        <select name="status" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Semua Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="review_client" {{ request('status') == 'review_client' ? 'selected' : '' }}>Review Mitra</option>
                            <option value="review_unit" {{ request('status') == 'review_unit' ? 'selected' : '' }}>Review Unit</option>
                            <option value="signed" {{ request('status') == 'signed' ? 'selected' : '' }}>Aktif</option>
                            <option value="expiring" {{ request('status') == 'expiring' ? 'selected' : '' }}>Masa Tenggang</option>
                            <option value="kadaluarsa" {{ request('status') == 'kadaluarsa' ? 'selected' : '' }}>Kadaluarsa</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Unit Pengusul</label>
                    <select name="unit" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Unit</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ request('unit') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Mitra</label>
                    <select name="client" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Mitra</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ request('client') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Dibuat Mulai</label>
                        <input type="date" name="created_from" value="{{ request('created_from') }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Dibuat Hingga</label>
                        <input type="date" name="created_to" value="{{ request('created_to') }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition">
                    <i class="fa-solid fa-filter mr-2"></i> Terapkan Filter
                </button>
                <a href="{{ route('documents.index') }}" class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-600 py-2 rounded-lg text-sm font-semibold transition">
                    <i class="fa-solid fa-rotate-left mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500">
                <tr>
                    <th class="px-6 py-4">No</th>
                    <th class="px-6 py-4">Judul</th>
                    <th class="px-6 py-4">Jenis</th>
                    <th class="px-6 py-4">Mitra</th>
                    <th class="px-6 py-4">Nama PIC</th>                    
                    <th class="px-6 py-4">Unit</th>
                    <th class="px-6 py-4">Tanggal Dibuat</th>
                    <th class="px-6 py-4">Tanggal Mulai</th>
                    <th class="px-6 py-4">Tanggal Selesai</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
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
                            <div class="text-xs text-slate-500 mt-1">Rujukan: {{ $doc->parent->document_number ?? $doc->parent->title }}</div>
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
                    <td class="px-6 py-4">{{ $doc->parties->where('role_type', 'client')->map(fn($p) => $p->user->nama_mitra ?? '-')->join(', ') ?: '-' }}</td>
                    <td class="px-6 py-4">{{ $doc->parties->where('role_type', 'client')->map(fn($p) => $p->user->name ?? '-')->join(', ') ?: '-' }}</td>
                    <td class="px-6 py-4">{{ $doc->parties->where('role_type', 'unit_pengusul')->map(fn($p) => $p->user->jabatan ?? '-')->join(', ') ?: '-' }}</td>
                    <td class="px-6 py-4">{{ $doc->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4">{{ $doc->start_date ? \Carbon\Carbon::parse($doc->start_date)->format('d M Y') : '-' }}</td>
                    <td class="px-6 py-4 font-medium text-slate-600">{{ $doc->end_date ? \Carbon\Carbon::parse($doc->end_date)->format('d M Y') : '-' }}</td>
                    <td class="px-6 py-4">
                        @php
                            $isExpired = $doc->end_date && \Carbon\Carbon::parse($doc->end_date)->isPast() && $doc->status == 'signed';
                            if ($isExpired) {
                                $statusClass = 'bg-red-100 text-red-700';
                                $statusLabel = 'KADALUARSA';
                            } else {
                                $statusClass = match($doc->status) {
                                    'signed' => 'bg-green-100 text-green-700',
                                    'draft' => 'bg-slate-100 text-slate-600',
                                    default => 'bg-yellow-100 text-yellow-700',
                                };
                                $statusLabel = match($doc->status) {
                                    'signed' => 'Aktif',
                                    'draft' => 'DRAFT',
                                    'review_client' => 'REVIEW MITRA',
                                    'review_unit' => 'REVIEW UNIT',
                                    default => strtoupper(str_replace('_', ' ', $doc->status)),
                                };
                            }
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        @if($doc->file_path)
                            <a href="{{ route('documents.preview', ['id' => $doc->id]) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-medium mr-3">
                                <i class="fa-solid fa-eye mr-1"></i>
                            </a>
                        @else
                            <a href="{{ route('documents.editor', ['id' => $doc->id]) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium mr-3">
                                @if($doc->status == 'signed' || $isExpired)
                                    <i class="fa-solid fa-eye mr-1"></i> 
                                @else
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> 
                                @endif
                            </a>
                        @endif
                        @role('super_admin')
                        <button type="button"
                            onclick="openEditDateModal({{ $doc->id }}, '{{ addslashes($doc->document_number) }}', '{{ $doc->start_date }}', '{{ $doc->end_date }}', '{{ addslashes($doc->title) }}')"
                            class="text-slate-500 hover:text-slate-700 text-xs font-medium">
                            <i class="fa-solid fa-pen mr-1"></i> 
                        </button>
                        <button type="button"
                            onclick="openDeleteModal({{ $doc->id }}, '{{ addslashes($doc->title) }}')"
                            class="text-red-500 hover:text-red-700 text-xs font-medium ml-3">
                            <i class="fa-solid fa-trash mr-1"></i> 
                        </button>
                        @endrole
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
            {{ $documents->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<!-- Modal Create Document -->
<div id="modal-create-doc" class="fixed inset-0 bg-slate-900/50 {{ $errors->any() ? '' : 'hidden' }} items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden transform transition-all">
        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" x-data="{ tab: '{{ old('submission_type', 'draft') }}' }">
            @csrf
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-slate-200 mb-5">
                    <div class="flex space-x-6">
                        <button type="button" @click="tab = 'draft'" :class="tab === 'draft' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-2 border-b-2 font-medium text-sm transition">Buat Draft Baru</button>
                        <button type="button" @click="tab = 'upload'" :class="tab === 'upload' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-2 border-b-2 font-medium text-sm transition">Upload Dokumen Final</button>
                    </div>
                </div>
                
                <input type="hidden" name="submission_type" :value="tab">
                
                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Judul Kerjasama</label>
                        <input type="text" name="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan judul...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Dokumen</label>
                        <select name="type" id="input-type" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="mou" {{ old('type') == 'mou' ? 'selected' : '' }}>MoU (Memorandum of Understanding)</option>
                            <option value="moa" {{ old('type') == 'moa' ? 'selected' : '' }}>MoA (Memorandum of Agreement)</option>
                            <option value="ia" {{ old('type') == 'ia' ? 'selected' : '' }}>IA (Implementation Arrangement)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nomor Dokumen <span class="text-slate-400 text-xs font-normal ml-1">(Opsional saat draft)</span></label>
                        <input type="text" name="document_number" value="{{ old('document_number') }}" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan nomor dokumen...">
                        <p id="last-doc-number-info" class="text-xs text-blue-600 mt-1 hidden"><i class="fa-solid fa-circle-info mr-1"></i> Nomor dokumen terakhir: <strong id="last-doc-number-text"></strong></p>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium text-slate-700">Pihak-pihak dalam Kerjasama</label>
                            <button type="button" onclick="addParty()" class="text-xs text-blue-600 hover:text-blue-800 font-medium"><i class="fa-solid fa-plus mr-1"></i> Tambah Pihak</button>
                        </div>
                        <div id="parties-container" class="space-y-3">
                            @php
                                $oldParties = old('parties', [null, null]);
                                if(count($oldParties) < 2) $oldParties = array_pad($oldParties, 2, null);
                            @endphp
                            @foreach($oldParties as $idx => $selectedParty)
                            <div class="party-item flex items-center gap-2">
                                <select name="parties[]" required class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih Pihak {{ $idx == 0 ? 'Pertama' : ($idx == 1 ? 'Kedua' : 'Tambahan') }} --</option>
                                    <optgroup label="Unit Pengusul (Internal)">
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" {{ $selectedParty == $unit->id ? 'selected' : '' }}>{{ $unit->jabatan ?: $unit->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Mitra (Eksternal)">
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ $selectedParty == $client->id ? 'selected' : '' }}>{{ $client->nama_mitra ?: $client->name }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <button type="button" {!! $idx >= 2 ? 'onclick="removeParty(this)"' : 'disabled' !!} class="{{ $idx >= 2 ? 'text-red-500 hover:text-red-700' : 'text-slate-300 cursor-not-allowed' }} px-2 py-2"><i class="fa-solid fa-trash"></i></button>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-slate-500 mt-2"><i class="fa-solid fa-info-circle mr-1"></i> Minimal 2 pihak. Anda dapat menambahkan lebih banyak pihak jika diperlukan.</p>
                    </div>

                    <div x-show="tab === 'draft'" x-transition class="mt-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="allow_client_upload" id="allow_client_upload" value="1" {{ old('allow_client_upload') ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                            <label for="allow_client_upload" class="ml-2 block text-sm text-slate-700">
                                Izinkan Mitra mengunggah file template .docx
                            </label>
                        </div>
                    </div>
                    
                    <div id="parent-doc-container" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            <span id="parent-doc-label">Dokumen Rujukan</span>
                            <span class="text-slate-400 text-xs font-normal ml-1">(Opsional)</span>
                        </label>
                        <select name="parent_id" id="input-parent" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="-- Tidak ada rujukan --">
                            <option value="">-- Tidak ada rujukan --</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            Opsional: Pilih dokumen induk jika dokumen ini merupakan turunan dari dokumen lain.
                        </p>
                    </div>
                    <div x-show="tab === 'upload'" x-transition class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" :required="tab === 'upload'" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Selesai</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" :required="tab === 'upload'" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div x-show="tab === 'upload'" x-transition class="pt-2 border-t border-slate-100">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Upload File Dokumen (PDF)</label>
                        <input type="file" name="final_pdf" accept="application/pdf" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" :required="tab === 'upload'">
                        <p class="text-xs text-slate-500 mt-1"><i class="fa-solid fa-info-circle mr-1"></i> Upload dokumen final yang sudah ditandatangani secara offline. Status dokumen akan langsung menjadi 'Aktif'.</p>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button type="submit" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto" x-text="tab === 'draft' ? 'Buat Draft' : 'Simpan & Aktifkan'"></button>
                <button type="button" onclick="document.getElementById('modal-create-doc').classList.add('hidden')" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Tanggal -->
<div id="modal-edit-date" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
        <form id="form-edit-date">
            <input type="hidden" id="edit-date-doc-id">
            <div class="bg-white px-6 pt-6 pb-4">
                <h3 class="text-base font-semibold text-slate-900 mb-1">Ubah Data Dokumen</h3>
                <p id="edit-date-title" class="text-sm text-slate-500 mb-4 truncate"></p>

                <div id="edit-date-error" class="hidden mb-3 bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded-lg text-sm"></div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nomor Dokumen</label>
                    <input type="text" id="edit-date-doc-number" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai</label>
                        <input type="date" id="edit-date-start" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Selesai</label>
                        <input type="date" id="edit-date-end" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex flex-row-reverse gap-3">
                <button type="submit" id="btn-save-dates" class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">Simpan Perubahan</button>
                <button type="button" onclick="document.getElementById('modal-edit-date').classList.add('hidden')" class="inline-flex justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete Document -->
<div id="modal-delete-doc" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
        <form id="form-delete-doc">
            <input type="hidden" id="delete-doc-id">
            <div class="bg-white px-6 pt-6 pb-4">
                <h3 class="text-base font-semibold text-red-600 mb-1">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Konfirmasi Hapus
                </h3>
                <p class="text-sm text-slate-600 mb-4">Anda akan menghapus dokumen <strong id="delete-doc-title"></strong>. Tindakan ini tidak dapat dibatalkan.</p>
                <div id="delete-doc-error" class="hidden mb-3 bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded-lg text-sm"></div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ketik "konfirmasi" untuk melanjutkan</label>
                    <input type="text" id="delete-confirmation-text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-sm" placeholder="konfirmasi">
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex flex-row-reverse gap-3">
                <button type="submit" id="btn-delete-doc" disabled class="inline-flex justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition disabled:opacity-50 disabled:cursor-not-allowed">Hapus Dokumen</button>
                <button type="button" onclick="document.getElementById('modal-delete-doc').classList.add('hidden')" class="inline-flex justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    const allDocs = [
        @foreach($allDocs as $d)
        { 
            id: '{{ $d->id }}', 
            title: '{!! addslashes($d->document_number ? $d->document_number . " - " : "") !!}{!! addslashes($d->title) !!} ({{ $d->created_at->format("d M Y") }})',
            type: '{{ $d->type }}',
            status: '{{ $d->status }}'
        },
        @endforeach
    ];

    let parentSelectTs;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize TomSelect for existing party selects
        document.querySelectorAll('select[name="parties[]"]').forEach(el => {
            initPartySelect(el);
        });

        const typeSelect = document.getElementById('input-type');
        const parentContainer = document.getElementById('parent-doc-container');
        const parentLabel = document.getElementById('parent-doc-label');

        parentSelectTs = new TomSelect('#input-parent', {
            valueField: 'id',
            labelField: 'title',
            searchField: 'title',
            create: false,
            placeholder: 'Ketik untuk mencari rujukan...',
            allowEmptyOption: true,
            options: []
        });

        const lastDocNumbers = @json($lastDocNumbers ?? []);
        const lastDocInfo = document.getElementById('last-doc-number-info');
        const lastDocText = document.getElementById('last-doc-number-text');

        function updateParentOptions() {
            const type = typeSelect.value;
            
            if (lastDocNumbers[type]) {
                lastDocText.textContent = lastDocNumbers[type];
                lastDocInfo.classList.remove('hidden');
            } else {
                lastDocInfo.classList.add('hidden');
            }
            
            if (type === 'mou') {
                parentContainer.classList.add('hidden');
                parentSelectTs.clear();
                parentSelectTs.clearOptions();
            } else {
                parentContainer.classList.remove('hidden');
                if (type === 'moa') {
                    parentLabel.textContent = 'Rujukan MoU';
                } else if (type === 'ia') {
                    parentLabel.textContent = 'Rujukan MoA';
                }

                const targetParentType = type === 'moa' ? 'mou' : 'moa';
                
                parentSelectTs.clear();
                parentSelectTs.clearOptions();
                
                const filteredDocs = allDocs.filter(doc => doc.type === targetParentType && doc.status === 'signed');
                parentSelectTs.addOptions(filteredDocs);
                parentSelectTs.refreshOptions(false);
            }
        }

        typeSelect.addEventListener('change', updateParentOptions);
        updateParentOptions();
        
        @if(old('parent_id'))
            setTimeout(() => {
                parentSelectTs.setValue('{{ old("parent_id") }}', true);
            }, 100);
        @endif
    });

    const partySelectTemplate = `
        <select name="parties[]" required class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">-- Pilih Pihak Tambahan --</option>
            <optgroup label="Unit Pengusul (Internal)">
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->jabatan ?: $unit->name }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Mitra (Eksternal)">
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->nama_mitra ?: $client->name }}</option>
                @endforeach
            </optgroup>
        </select>
    `;

    let partySelects = [];

    function initPartySelect(el) {
        const ts = new TomSelect(el, {
            create: false,
            sortField: { field: "text", direction: "asc" },
            onChange: function() {
                updatePartyOptions();
            }
        });
        partySelects.push(ts);
        return ts;
    }

    function removeParty(btn) {
        const item = btn.closest('.party-item');
        const select = item.querySelector('select');
        const tsIndex = partySelects.findIndex(ts => ts.input === select);
        if (tsIndex > -1) {
            partySelects[tsIndex].destroy();
            partySelects.splice(tsIndex, 1);
        }
        item.remove();
        updatePartyOptions();
    }

    function updatePartyOptions() {
        const selectedValues = partySelects.map(ts => ts.getValue()).filter(val => val !== '');
        
        partySelects.forEach(ts => {
            const currentValue = ts.getValue();
            
            Object.keys(ts.options).forEach(value => {
                if (!value) return;
                const opt = ts.options[value];
                const isDisabled = selectedValues.includes(value) && value !== currentValue;
                
                if (opt.disabled !== isDisabled) {
                    // Update the option with the new disabled state
                    ts.updateOption(value, { ...opt, disabled: isDisabled });
                }
            });
        });
    }

    function addParty() {
        const container = document.getElementById('parties-container');
        
        const div = document.createElement('div');
        div.className = 'party-item flex items-center gap-2';
        div.innerHTML = `
            <div class="flex-1">${partySelectTemplate}</div>
            <button type="button" onclick="removeParty(this)" class="text-red-500 hover:text-red-700 px-2 py-2"><i class="fa-solid fa-trash"></i></button>
        `;
        container.appendChild(div);

        initPartySelect(div.querySelector('select'));
        updatePartyOptions();
    }
</script>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<style>
.ts-control {
    border-color: #cbd5e1 !important;
    border-radius: 0.375rem !important;
    padding: 0.5rem 0.75rem !important;
    font-size: 0.875rem !important;
    line-height: 1.25rem !important;
    box-shadow: none !important;
}
.ts-control.focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5) !important;
}
.ts-dropdown {
    font-size: 0.875rem !important;
    border-radius: 0.375rem !important;
    border-color: #cbd5e1 !important;
    z-index: 100 !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
// Edit Date Modal
function openEditDateModal(docId, docNumber, startDate, endDate, title) {
    document.getElementById('edit-date-doc-id').value = docId;
    document.getElementById('edit-date-doc-number').value = docNumber;
    document.getElementById('edit-date-start').value = startDate;
    document.getElementById('edit-date-end').value = endDate;
    document.getElementById('edit-date-title').textContent = title;
    document.getElementById('edit-date-error').classList.add('hidden');
    document.getElementById('edit-date-error').textContent = '';
    document.getElementById('modal-edit-date').classList.remove('hidden');
}

document.getElementById('form-edit-date').addEventListener('submit', async function(e) {
    e.preventDefault();
    const docId = document.getElementById('edit-date-doc-id').value;
    const docNumber = document.getElementById('edit-date-doc-number').value;
    const startDate = document.getElementById('edit-date-start').value;
    const endDate = document.getElementById('edit-date-end').value;
    const errEl = document.getElementById('edit-date-error');
    const btn = document.getElementById('btn-save-dates');

    btn.disabled = true;
    btn.textContent = 'Menyimpan...';
    errEl.classList.add('hidden');

    try {
        const res = await fetch(`/documents/${docId}/dates`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ document_number: docNumber, start_date: startDate, end_date: endDate }),
        });

        const data = await res.json();

        if (res.ok && data.success) {
            document.getElementById('modal-edit-date').classList.add('hidden');
            window.location.reload();
        } else {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Terjadi kesalahan.');
            errEl.textContent = msgs;
            errEl.classList.remove('hidden');
        }
    } catch (err) {
        errEl.textContent = 'Gagal terhubung ke server. Silakan coba lagi.';
        errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan Perubahan';
    }
});

// Delete Document Modal
function openDeleteModal(docId, title) {
    document.getElementById('delete-doc-id').value = docId;
    document.getElementById('delete-doc-title').textContent = title;
    document.getElementById('delete-confirmation-text').value = '';
    document.getElementById('btn-delete-doc').disabled = true;
    document.getElementById('delete-doc-error').classList.add('hidden');
    document.getElementById('modal-delete-doc').classList.remove('hidden');
}

document.getElementById('delete-confirmation-text').addEventListener('input', function() {
    document.getElementById('btn-delete-doc').disabled = this.value.toLowerCase() !== 'konfirmasi';
});

document.getElementById('form-delete-doc').addEventListener('submit', async function(e) {
    e.preventDefault();
    const docId = document.getElementById('delete-doc-id').value;
    const btn = document.getElementById('btn-delete-doc');
    const errEl = document.getElementById('delete-doc-error');

    btn.disabled = true;
    btn.textContent = 'Menghapus...';
    errEl.classList.add('hidden');

    try {
        const res = await fetch(`/documents/${docId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        const data = await res.json();

        if (res.ok && data.success) {
            document.getElementById('modal-delete-doc').classList.add('hidden');
            window.location.reload();
        } else {
            errEl.textContent = data.message || 'Gagal menghapus dokumen.';
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Hapus Dokumen';
        }
    } catch (err) {
        errEl.textContent = 'Gagal terhubung ke server. Silakan coba lagi.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Hapus Dokumen';
    }
});
</script>
@endpush
