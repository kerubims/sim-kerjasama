@extends('layouts.app')

@section('title', 'Tracking Dokumen')
@section('page-title', 'Tracking Dokumen')

@section('content')
<div class="space-y-6">
    <!-- Search Bar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-magnifying-glass text-blue-600"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800">Cari & Lacak Dokumen</h3>
                <p class="text-sm text-slate-500">Temukan hierarki dokumen berdasarkan nomor atau judul kerjasama</p>
            </div>
        </div>
        <form method="GET" action="{{ route('tracking.index') }}" class="flex gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Ketik nomor dokumen atau judul kerjasama..."
                       class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-search mr-2"></i> Lacak
            </button>
            @if($search)
            <a href="{{ route('tracking.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2.5 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-times"></i>
            </a>
            @endif
        </form>
    </div>

    <!-- Document Hierarchy Tree -->
    @if($documents->count() > 0)
    <div x-data="{ openNodes: {} }" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <h3 class="font-bold text-slate-800 mb-6 flex items-center gap-2">
            <i class="fa-solid fa-diagram-project text-blue-600"></i> Hierarki Dokumen Kerjasama
            <span class="text-sm font-normal text-slate-500 ml-2">({{ $documents->count() }} root)</span>
        </h3>

        <div class="space-y-4">
            @foreach($documents as $mou)
            @php
                $mouKey = 'mou_' . $mou->id;
                $statusClass = match($mou->status) {
                    'signed' => 'bg-green-100 text-green-700',
                    'draft' => 'bg-slate-100 text-slate-600',
                    'review_client' => 'bg-yellow-100 text-yellow-700',
                    'review_unit' => 'bg-orange-100 text-orange-700',
                    default => 'bg-slate-100 text-slate-600',
                };
                $statusLabel = match($mou->status) {
                    'signed' => 'Aktif',
                    'draft' => 'Draft',
                    'review_client' => 'Review Mitra',
                    'review_unit' => 'Review Unit',
                    default => ucfirst($mou->status),
                };
                $clientName = $mou->parties->where('role_type', 'client')->first()?->user?->name ?? '-';
                $unitName = $mou->parties->where('role_type', 'unit_pengusul')->first()?->user?->name ?? '-';
                $rootUi = match($mou->type) {
                    'mou' => ['bg' => 'bg-blue-50', 'iconBg' => 'bg-blue-600', 'icon' => 'fa-handshake'],
                    'moa' => ['bg' => 'bg-purple-50', 'iconBg' => 'bg-purple-500', 'icon' => 'fa-file-signature'],
                    'ia' => ['bg' => 'bg-teal-50', 'iconBg' => 'bg-teal-500', 'icon' => 'fa-clipboard-check'],
                    default => ['bg' => 'bg-slate-50', 'iconBg' => 'bg-slate-500', 'icon' => 'fa-file']
                };
            @endphp
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <!-- MoU Level -->
                <div class="{{ $rootUi['bg'] }} px-6 py-4 flex items-center gap-4 cursor-pointer" @click="openNodes['{{ $mouKey }}'] = !openNodes['{{ $mouKey }}']">
                    <i class="fa-solid fa-chevron-down text-slate-400 transition-transform text-sm" :class="{ 'rotate-180': !openNodes['{{ $mouKey }}'] }"></i>
                    <div class="w-10 h-10 {{ $rootUi['iconBg'] }} rounded-lg flex items-center justify-center text-white">
                        <i class="fa-solid {{ $rootUi['icon'] }}"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-slate-900">{{ strtoupper($mou->type) }} — {{ $mou->title }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ $mou->document_number ?? 'No. belum ditetapkan' }} • {{ $clientName }}
                            @if($mou->start_date && $mou->end_date)
                            • {{ \Carbon\Carbon::parse($mou->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($mou->end_date)->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                    <a href="{{ route('documents.editor', $mou->id) }}" @click.stop class="text-slate-400 hover:text-blue-600 transition" title="Buka Editor">
                        <i class="fa-solid fa-arrow-up-right-from-square text-sm"></i>
                    </a>
                </div>

                @if($mou->children->count() > 0)
                <div x-show="openNodes['{{ $mouKey }}']" x-transition class="border-t border-slate-200">
                    @foreach($mou->children as $moa)
                    @php
                        $moaKey = 'moa_' . $moa->id;
                        $moaStatusClass = match($moa->status) {
                            'signed' => 'bg-green-100 text-green-700',
                            'draft' => 'bg-slate-100 text-slate-600',
                            default => 'bg-yellow-100 text-yellow-700',
                        };
                        $moaStatusLabel = match($moa->status) {
                            'signed' => 'Aktif', 'draft' => 'Draft',
                            'review_client' => 'Review Mitra', 'review_unit' => 'Review Unit',
                            default => ucfirst($moa->status),
                        };
                        $moaClient = $moa->parties->where('role_type', 'client')->first()?->user?->name ?? '-';
                    @endphp
                    <div class="ml-8 border-l-2 border-blue-200">
                        <div class="pl-6 py-3 flex items-center gap-4 hover:bg-slate-50 cursor-pointer" @click="openNodes['{{ $moaKey }}'] = !openNodes['{{ $moaKey }}']">
                            <i class="fa-solid fa-chevron-down text-purple-500 text-xs transition-transform" :class="{ 'rotate-180': !openNodes['{{ $moaKey }}'] }"></i>
                            <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center text-white text-xs">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-slate-800 text-sm">{{ strtoupper($moa->type) }} — {{ $moa->title }}</div>
                                <div class="text-xs text-slate-500">{{ $moa->document_number ?? 'No. belum ditetapkan' }} • {{ $moaClient }}</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $moaStatusClass }}">{{ $moaStatusLabel }}</span>
                            <a href="{{ route('documents.editor', $moa->id) }}" @click.stop class="text-slate-400 hover:text-blue-600 transition" title="Buka Editor">
                                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            </a>
                        </div>

                        @if($moa->children->count() > 0)
                        <div x-show="openNodes['{{ $moaKey }}']" x-transition>
                            @foreach($moa->children as $ia)
                            @php
                                $iaStatusClass = match($ia->status) {
                                    'signed' => 'bg-green-100 text-green-700',
                                    'draft' => 'bg-slate-100 text-slate-600',
                                    default => 'bg-yellow-100 text-yellow-700',
                                };
                                $iaStatusLabel = match($ia->status) {
                                    'signed' => 'Aktif', 'draft' => 'Draft',
                                    'review_client' => 'Review Mitra', 'review_unit' => 'Review Unit',
                                    default => ucfirst($ia->status),
                                };
                            @endphp
                            <div class="ml-8 border-l-2 border-purple-200">
                                <div class="pl-6 py-3 flex items-center gap-4 hover:bg-slate-50">
                                    <div class="w-6 h-6 bg-teal-500 rounded flex items-center justify-center text-white text-xs">
                                        <i class="fa-solid fa-clipboard-check text-[10px]"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-slate-700 text-sm">{{ strtoupper($ia->type) }} — {{ $ia->title }}</div>
                                        <div class="text-xs text-slate-500">{{ $ia->document_number ?? 'No. belum ditetapkan' }} • {{ $ia->created_at->format('d M Y') }}</div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $iaStatusClass }}">{{ $iaStatusLabel }}</span>
                                    <a href="{{ route('documents.editor', $ia->id) }}" class="text-slate-400 hover:text-blue-600 transition" title="Buka Editor">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-12 text-center">
        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-diagram-project text-3xl text-slate-400"></i>
        </div>
        @if($search)
        <h3 class="font-bold text-slate-700 text-lg">Tidak Ditemukan</h3>
        <p class="text-slate-500 text-sm mt-2">Tidak ada dokumen yang cocok dengan pencarian "{{ $search }}".</p>
        @else
        <h3 class="font-bold text-slate-700 text-lg">Belum Ada Dokumen</h3>
        <p class="text-slate-500 text-sm mt-2 max-w-md mx-auto">Buat dokumen kerjasama terlebih dahulu di halaman Dokumen Kerjasama untuk melihat hierarki di sini.</p>
        @endif
    </div>
    @endif
</div>
@endsection
