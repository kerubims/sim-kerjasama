@extends('layouts.app')

@section('title', 'Beranda')
@section('page-title', 'Ringkasan Beranda')

@section('content')
@php
    $user = Auth::user();
    $isSuperAdmin = $user->hasRole('super_admin');
@endphp

<!-- Welcome Section -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Selamat Datang, {{ $user->name }}</h1>
    <p class="text-slate-500 mt-1">
        @if($isSuperAdmin)
            Berikut adalah ringkasan data kerjasama universitas per hari ini.
        @else
            Berikut adalah daftar dokumen kerjasama yang perlu Anda tinjau.
        @endif
    </p>
</div>

@if($isSuperAdmin)
<!-- Stats Cards (Admin Only) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Mitra -->
    <a href="{{ route('users.index') }}" class="block">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow h-full">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="text-sm text-slate-500 font-medium">Total Mitra</div>
                    <div class="text-3xl font-bold text-slate-900 mt-1">{{ number_format($stats['total_mitra']) }}</div>
                </div>
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
            <div class="text-xs font-medium text-slate-500">
                Total mitra terdaftar
            </div>
        </div>
    </a>

    <!-- Dokumen Aktif -->
    <a href="{{ route('documents.index', ['status' => 'signed']) }}" class="block">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow h-full">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="text-sm text-slate-500 font-medium">Dokumen Aktif</div>
                    <div class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['active'] }}</div>
                </div>
                <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
            </div>
            <div class="text-xs font-medium text-slate-500">
                Dokumen status "Signed"
            </div>
        </div>
    </a>

    <!-- Masa Tenggang -->
    <a href="{{ route('documents.index', ['status' => 'expiring']) }}" class="block">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow h-full">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="text-sm text-slate-500 font-medium">Masa Tenggang</div>
                    <div class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['expiring'] }}</div>
                </div>
                <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center text-yellow-600">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="text-xs font-medium text-yellow-600">
                Expiring < 30 days
            </div>
        </div>
    </a>

    <!-- Kedaluwarsa -->
    <a href="{{ route('documents.index', ['status' => 'kadaluarsa']) }}" class="block">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow h-full">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="text-sm text-slate-500 font-medium">Kedaluwarsa</div>
                    <div class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['expired'] }}</div>
                </div>
                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center text-red-600">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </div>
            </div>
            <div class="text-xs font-medium text-red-600">
                Perlu tindak lanjut
            </div>
        </div>
    </a>
</div>

<!-- Charts Section (Admin Only) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Bar Chart -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-slate-800">Tren Kerjasama (MoU & MoA)</h3>
            <select id="trendYearSelector" class="text-xs border-none bg-slate-50 rounded px-2 py-1 text-slate-600 focus:ring-0 cursor-pointer" onchange="updateChart(this.value)">
                @for ($i = 0; $i < 5; $i++)
                    @php $y = date('Y') - $i; @endphp
                    <option value="{{ $y }}" {{ request('year', date('Y')) == $y ? 'selected' : '' }}>
                        {{ $i == 0 ? 'Tahun Ini (' . $y . ')' : ($i == 1 ? 'Tahun Lalu (' . $y . ')' : $y) }}
                    </option>
                @endfor
            </select>
        </div>
        <div class="h-64">
            <canvas id="barChart"></canvas>
        </div>
    </div>

    <!-- Donut Chart: Unit Pengusul -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-slate-800">Distribusi Unit Pengusul</h3>
            <select id="unitChartFilter" class="text-xs border-none bg-slate-50 rounded px-2 py-1 text-slate-600 focus:ring-0 cursor-pointer" onchange="updateUnitChart(this.value)">
                <option value="all">Semua Waktu</option>
                <option value="this_year">Tahun Ini</option>
                <option value="last_year">Tahun Lalu</option>
            </select>
        </div>
        <div class="h-64 relative flex justify-center">
            <canvas id="unitDonutChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Nested Donut Chart: Hierarki Dokumen -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-6">Hierarki Dokumen (Rasio)</h3>
        <div class="h-48 relative flex justify-center">
            <canvas id="donutChart"></canvas>
        </div>
        <div class="mt-6 grid grid-cols-3 gap-4 text-xs text-center">
            <div class="flex items-center justify-center gap-2"><span class="w-2 h-2 rounded-full bg-[#1e3a5f]"></span> MoU</div>
            <div class="flex items-center justify-center gap-2"><span class="w-2 h-2 rounded-full bg-[#60a5fa]"></span> MoA</div>
            <div class="flex items-center justify-center gap-2"><span class="w-2 h-2 rounded-full bg-[#2dd4bf]"></span> IA</div>
        </div>
    </div>

    <!-- Donut Chart: Scope -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-6">Kategori Kerjasama</h3>
        <div class="h-48 relative flex justify-center">
            <canvas id="scopeDonutChart"></canvas>
        </div>
        <div class="mt-6 grid grid-cols-4 gap-2 text-xs text-center">
            <div class="flex items-center justify-center gap-1"><span class="w-2 h-2 rounded-full bg-[#3b82f6]"></span> Lokal</div>
            <div class="flex items-center justify-center gap-1"><span class="w-2 h-2 rounded-full bg-[#10b981]"></span> Dalam Negeri</div>
            <div class="flex items-center justify-center gap-1"><span class="w-2 h-2 rounded-full bg-[#f59e0b]"></span> Nasional</div>
            <div class="flex items-center justify-center gap-1"><span class="w-2 h-2 rounded-full bg-[#ef4444]"></span> Luar Negeri</div>
        </div>
    </div>
    
    <!-- Bar Chart: Top Mitra -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-6">Top Mitra</h3>
        <div class="h-48 relative flex justify-center">
            <canvas id="topMitraChart"></canvas>
        </div>
        <div class="mt-6 text-xs text-center text-slate-500">
            5 Mitra dengan jumlah dokumen terbanyak
        </div>
    </div>
</div>
@endif

<!-- Tabel Dokumen Terbaru -->
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
        <h3 class="font-bold text-slate-800">
            {{ $isSuperAdmin ? 'Dokumen Kerjasama Terbaru' : 'Daftar Dokumen Anda' }}
        </h3>
        @if($isSuperAdmin)
        <a href="{{ route('documents.editor', ['id' => 'new']) }}" class="text-sm text-blue-600 font-medium hover:underline">
            + Buat Baru
        </a>
        @endif
    </div>
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
                    @if($isSuperAdmin)
                    <th class="px-6 py-4">Tanggal Dibuat</th>
                    @endif
                    <th class="px-6 py-4">Tanggal Mulai</th>
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
                            <div class="text-xs text-slate-500 mt-1">Rujukan: {{ $doc->parent->document_number ?? $doc->parent->title ?? '' }}</div>
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
                    <td class="px-6 py-4">{{ $doc->parties->where('role_type', 'client')->map(fn($p) => $p->user->partner->name ?? '-')->join(', ') ?: '-' }}</td>
                    <td class="px-6 py-4">{{ $doc->parties->where('role_type', 'client')->map(fn($p) => $p->user->name ?? '-')->join(', ') ?: '-' }}</td>
                    <td class="px-6 py-4">{{ $doc->parties->where('role_type', 'unit_pengusul')->map(fn($p) => $p->user->proposerUnit->name ?? '-')->join(', ') ?: '-' }}</td>
                    @if($isSuperAdmin)
                    <td class="px-6 py-4">{{ $doc->created_at->format('d M Y') }}</td>
                    @endif
                    <td class="px-6 py-4">{{ $doc->start_date ? \Carbon\Carbon::parse($doc->start_date)->format('d M Y') : '-' }}</td>
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
                                'review_client' => 'REVIEW MITRA',
                                'review_unit' => 'REVIEW UNIT',
                                default => strtoupper(str_replace('_', ' ', $doc->status)),
                            };
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('documents.editor', ['id' => $doc->id]) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                            <i class="fa-solid fa-pen-to-square mr-1"></i> Buka / Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-6 py-8 text-center text-slate-400">Belum ada dokumen</td>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($isSuperAdmin)
    const chartData = @json($chartData);

    let barChartInstance = null;

    // Bar Chart - Tren Kerjasama
    const barCtx = document.getElementById('barChart');
    if (barCtx && chartData.bar) {
        barChartInstance = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [
                    {
                        label: 'MoU',
                        data: chartData.bar.mou,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                    },
                    {
                        label: 'MoA',
                        data: chartData.bar.moa,
                        backgroundColor: '#94a3b8',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 11 } } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 }, stepSize: 1 } }
                }
            }
        });
    }

    window.updateChart = function(year) {
        if (!barChartInstance) return;
        
        // Use Fetch API to get data without reloading the page
        fetch(`{{ route('dashboard.chart-data') }}?year=${year}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                // Update dataset with new data
                barChartInstance.data.datasets[0].data = data.mou;
                barChartInstance.data.datasets[1].data = data.moa;
                barChartInstance.update();
            })
            .catch(err => console.error('Error fetching chart data:', err));
    }

    // Hierarki Dokumen (Nested Donut)
    const donutCtx = document.getElementById('donutChart');
    if (donutCtx && chartData.donut) {
        const dData = chartData.donut.data;
        const maxVal = Math.max(...dData, 1);
        
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Data', 'Sisa'],
                datasets: [
                    {
                        data: [dData[0], maxVal - dData[0]],
                        backgroundColor: ['#1e3a5f', 'transparent'],
                        borderWidth: 1,
                        borderColor: '#ffffff',
                    },
                    {
                        data: [dData[1], maxVal - dData[1]],
                        backgroundColor: ['#60a5fa', 'transparent'],
                        borderWidth: 1,
                        borderColor: '#ffffff',
                    },
                    {
                        data: [dData[2], maxVal - dData[2]],
                        backgroundColor: ['#2dd4bf', 'transparent'],
                        borderWidth: 1,
                        borderColor: '#ffffff',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '30%',
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        filter: function(tooltipItem) {
                            return tooltipItem.dataIndex === 0;
                        },
                        callbacks: {
                            label: function(context) {
                                let label = chartData.donut.labels[context.datasetIndex] || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.raw;
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // Scope Donut Chart
    const scopeCtx = document.getElementById('scopeDonutChart');
    if (scopeCtx && chartData.scope) {
        new Chart(scopeCtx, {
            type: 'doughnut',
            data: {
                labels: chartData.scope.labels,
                datasets: [{
                    data: chartData.scope.data,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // Top Mitra Bar Chart
    const topMitraCtx = document.getElementById('topMitraChart');
    if (topMitraCtx && chartData.top_mitra) {
        new Chart(topMitraCtx, {
            type: 'bar',
            data: {
                labels: chartData.top_mitra.labels,
                datasets: [{
                    label: 'Jumlah Dokumen',
                    data: chartData.top_mitra.data,
                    backgroundColor: '#8b5cf6',
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 }, stepSize: 1 } },
                    y: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    // Unit Pengusul Donut Chart (AJAX)
    let unitDonutInstance = null;
    const unitCtx = document.getElementById('unitDonutChart');
    
    function initUnitChart(labels, data) {
        if (unitCtx) {
            if (unitDonutInstance) {
                unitDonutInstance.destroy();
            }
            
            unitDonutInstance = new Chart(unitCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e', 
                            '#f97316', '#eab308', '#84cc16', '#10b981',
                            '#06b6d4', '#64748b'
                        ],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: { legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8, font: { size: 10 } } } }
                }
            });
        }
    }

    window.updateUnitChart = function(filter) {
        fetch(`{{ route('dashboard.chart-data') }}?type=unit&filter=${filter}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) return;
                initUnitChart(data.labels, data.data);
            })
            .catch(err => console.error('Error fetching unit chart data:', err));
    };

    // Load initial unit chart data
    updateUnitChart('all');

    @endif
});
</script>
@endpush
