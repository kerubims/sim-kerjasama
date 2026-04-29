@extends('layouts.app')

@section('title', 'Export Laporan')
@section('page-title', 'Export Laporan Kerjasama')

@section('content')
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
                <div class="text-sm text-green-100">Aktif/Signed</div>
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

    <form class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai</label>
            <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Akhir</label>
            <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Dokumen</label>
            <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Semua Jenis</option>
                <option>MoU</option>
                <option>MoA</option>
                <option>IA</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Semua Status</option>
                <option>Aktif</option>
                <option>Draft</option>
                <option>Review</option>
                <option>Kedaluwarsa</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Unit</label>
            <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Semua Unit</option>
                <option>Unit TI</option>
                <option>Unit FK</option>
                <option>Unit FKIP</option>
            </select>
        </div>
        <div class="flex items-end gap-2" x-data>
            <button type="button" @click="$dispatch('toast', {type: 'info', message: 'Menerapkan filter...'})" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-search mr-1"></i> Filter
            </button>
            <button type="button" @click="$dispatch('toast', {type: 'success', message: 'Filter di-reset.'})" class="px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
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
<div class="flex gap-3 mb-6" x-data>
    <a href="{{ route('reports.export-pdf') }}" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition inline-flex items-center">
        <i class="fa-solid fa-file-pdf mr-2"></i> Export PDF
    </a>
    <a href="{{ route('reports.export-excel') }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition inline-flex items-center">
        <i class="fa-solid fa-file-excel mr-2"></i> Export Excel
    </a>
    <button @click="window.print()" class="bg-slate-600 hover:bg-slate-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition">
        <i class="fa-solid fa-print mr-2"></i> Print
    </button>
</div>

<!-- Preview Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-800">Preview Laporan</h3>
        <span class="text-xs text-slate-500">Menampilkan 5 data terbaru</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500">
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
                @foreach($recentDocs as $idx => $doc)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-3">{{ $idx + 1 }}</td>
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
                    <td class="px-6 py-3">{{ $doc->parties->where('role_type', 'client')->first()->user->name ?? '-' }}</td>
                    <td class="px-6 py-3">{{ $doc->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-3">{{ $doc->end_date ? \Carbon\Carbon::parse($doc->end_date)->format('d M Y') : '-' }}</td>
                    <td class="px-6 py-3">
                        @php
                            $sc = match($doc->status) { 'signed' => 'bg-green-100 text-green-700', 'draft' => 'bg-slate-100 text-slate-600', default => 'bg-yellow-100 text-yellow-700' };
                            $sl = match($doc->status) { 'signed' => 'Aktif', 'draft' => 'DRAFT', 'review_client' => 'REVIEW CLIENT', 'review_unit' => 'REVIEW UNIT', default => strtoupper(str_replace('_', ' ', $doc->status)) };
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $sc }}">{{ $sl }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
            datasets: [{ data: [{{ $stats['active'] }}, {{ \App\Models\Document::where('status', 'draft')->count() }}, {{ \App\Models\Document::whereIn('status', ['review_client', 'review_unit'])->count() }}, {{ \App\Models\Document::where('status', 'expired')->count() }}], backgroundColor: ['#22c55e', '#94a3b8', '#f59e0b', '#ef4444'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15, font: { size: 11 } } } } }
    });
});
</script>
@endpush
