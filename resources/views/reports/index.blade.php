@extends('layouts.app')

@section('title', 'Ekspor Laporan')
@section('page-title', 'Ekspor Laporan Kerjasama')

@section('content')
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<style>
.ts-control {
    border-color: #cbd5e1 !important;
    border-radius: 0.5rem !important;
    padding: 0.5rem 0.75rem !important;
    font-size: 0.875rem !important;
    min-height: 38px !important;
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

<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-5 rounded-xl shadow-lg text-white">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-300">Total Dokumen</div>
                <div class="text-3xl font-bold mt-1">{{ $stats['total'] }}</div>
            </div>
            <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-file-contract text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-5 rounded-xl shadow-lg text-white">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-blue-100">MoU</div>
                <div class="text-3xl font-bold mt-1">{{ $stats['mou'] }}</div>
            </div>
            <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-handshake text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-5 rounded-xl shadow-lg text-white">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-purple-100">MoA</div>
                <div class="text-3xl font-bold mt-1">{{ $stats['moa'] }}</div>
            </div>
            <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-file-signature text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-br from-teal-500 to-teal-600 p-5 rounded-xl shadow-lg text-white">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-teal-100">IA</div>
                <div class="text-3xl font-bold mt-1">{{ $stats['ia'] }}</div>
            </div>
            <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-clipboard-check text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-br from-green-500 to-green-600 p-5 rounded-xl shadow-lg text-white">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-green-100">Aktif</div>
                <div class="text-3xl font-bold mt-1">{{ $stats['active'] }}</div>
            </div>
            <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-circle-check text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-filter text-blue-600"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800">Filter Laporan Audit</h3>
                <p class="text-sm text-slate-500">Pilih kriteria untuk menghasilkan laporan kerjasama</p>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Akhir</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Dokumen</label>
            <select name="type" class="w-full text-sm tom-select-filter">
                <option value="">Semua Jenis</option>
                <option value="mou" {{ request('type') == 'mou' ? 'selected' : '' }}>MoU</option>
                <option value="moa" {{ request('type') == 'moa' ? 'selected' : '' }}>MoA</option>
                <option value="ia" {{ request('type') == 'ia' ? 'selected' : '' }}>IA</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <select name="status" class="w-full text-sm tom-select-filter">
                <option value="">Semua Status</option>
                <option value="signed" {{ request('status') == 'signed' ? 'selected' : '' }}>Aktif</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="review_unit" {{ request('status') == 'review_unit' ? 'selected' : '' }}>Review Unit</option>
                <option value="review_client" {{ request('status') == 'review_client' ? 'selected' : '' }}>Review Mitra</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Unit</label>
            <select name="unit" class="w-full text-sm tom-select-filter">
                <option value="">Semua Unit</option>
                @foreach($units as $unitValue => $unitLabel)
                    <option value="{{ $unitValue }}" {{ request('unit') == $unitValue ? 'selected' : '' }}>{{ $unitLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2" x-data>
            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-search mr-1"></i> Filter
            </button>
            <a href="{{ route('reports.index') }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition inline-flex items-center justify-center">
                <i class="fa-solid fa-rotate-left"></i>
            </a>
        </div>
    </form>
</div>

<!-- Chart & Export Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <h3 class="font-bold text-slate-800 mb-4">Distribusi per Jenis</h3>
        <div class="h-64">
            <canvas id="reportBarChart"></canvas>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <h3 class="font-bold text-slate-800 mb-4">Status Dokumen</h3>
        <div class="h-64">
            <canvas id="reportPieChart"></canvas>
        </div>
    </div>
</div>

<!-- Export Buttons -->
<div class="flex flex-col sm:flex-row gap-3 mb-6" x-data>
    <a href="{{ route('reports.export-pdf', request()->all()) }}" target="_blank" class="w-full sm:w-auto justify-center bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition inline-flex items-center">
        <i class="fa-solid fa-file-pdf mr-2"></i> Ekspor PDF
    </a>
    <a href="{{ route('reports.export-excel', request()->all()) }}" target="_blank" class="w-full sm:w-auto justify-center bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition inline-flex items-center">
        <i class="fa-solid fa-file-excel mr-2"></i> Ekspor Excel
    </a>
    <button @click="window.print()" class="w-full sm:w-auto justify-center bg-slate-600 hover:bg-slate-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition inline-flex items-center">
        <i class="fa-solid fa-print mr-2"></i> Cetak
    </button>
</div>

<!-- Preview Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <h3 class="font-bold text-slate-800">Pratinjau Laporan</h3>
        <span class="text-xs text-slate-500">Menampilkan {{ $recentDocs->firstItem() ?? 0 }} - {{ $recentDocs->lastItem() ?? 0 }} dari {{ $recentDocs->total() }} data</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 whitespace-nowrap">
                <tr>
                    <th class="px-6 py-3">No</th>
                    <th class="px-6 py-3">Judul Kerjasama</th>
                    <th class="px-6 py-3">Jenis</th>
                    <th class="px-6 py-3">Mitra</th>
                    <th class="px-6 py-3">Tanggal Mulai</th>
                    <th class="px-6 py-3">Tanggal Selesai</th>
                    <th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentDocs as $idx => $doc)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-3 whitespace-nowrap">{{ $recentDocs->firstItem() + $idx }}</td>
                    <td class="px-6 py-3 font-medium text-slate-900">{{ $doc->title }}</td>
                    <td class="px-6 py-3">
                        @php
                            $typeClass = match(strtolower($doc->type)) {
                                'mou' => 'bg-blue-100 text-blue-700',
                                'moa' => 'bg-green-100 text-green-700',
                                default => 'bg-purple-100 text-purple-700',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded text-xs font-medium {{ $typeClass }}">{{ strtoupper($doc->type) }}</span>
                    </td>
                    <td class="px-6 py-3">{{ $doc->parties->where('role_type', 'client')->map(fn($p) => $p->user->nama_mitra ?? $p->user->partner->name ?? $p->user->name ?? '-')->join(', ') ?: '-' }}</td>
                    <td class="px-6 py-3">{{ $doc->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-3">{{ $doc->end_date ? \Carbon\Carbon::parse($doc->end_date)->format('d M Y') : '-' }}</td>
                    <td class="px-6 py-3">
                        @php
                            $isExpired = $doc->status == 'expired' || ($doc->end_date && \Carbon\Carbon::parse($doc->end_date)->isPast() && $doc->status == 'signed');
                            if ($isExpired) {
                                $sc = 'bg-red-100 text-red-700';
                                $sl = 'KADALUARSA';
                            } else {
                                $sc = match($doc->status) { 'signed' => 'bg-green-100 text-green-700', 'draft' => 'bg-slate-100 text-slate-600', default => 'bg-yellow-100 text-yellow-700' };
                                $sl = match($doc->status) { 'signed' => 'Aktif', 'draft' => 'DRAFT', 'review_client' => 'REVIEW MITRA', 'review_unit' => 'REVIEW UNIT', default => strtoupper(str_replace('_', ' ', $doc->status)) };
                            }
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $sc }}">{{ $sl }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-300"></i>
                            <p>Belum ada data laporan kerjasama</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($recentDocs->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $recentDocs->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize TomSelect for filter selects
    document.querySelectorAll('.tom-select-filter').forEach(el => {
        new TomSelect(el, {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });

    new Chart(document.getElementById('reportBarChart'), {
        type: 'bar',
        data: {
            labels: ['MoU', 'MoA', 'IA'],
            datasets: [{ label: 'Jumlah', data: [{{ $stats['mou'] }}, {{ $stats['moa'] }}, {{ $stats['ia'] }}], backgroundColor: ['#3b82f6', '#8b5cf6', '#14b8a6'], borderRadius: 6 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } } }
    });

    new Chart(document.getElementById('reportPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Aktif', 'Draft', 'Review', 'Kedaluwarsa'],
            datasets: [{ data: [{{ $stats['active'] }}, {{ $stats['draft'] }}, {{ $stats['review'] }}, {{ $stats['expired'] }}], backgroundColor: ['#22c55e', '#94a3b8', '#f59e0b', '#ef4444'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15, font: { size: 11 } } } } }
    });
});
</script>
@endpush
