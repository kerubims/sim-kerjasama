@extends('layouts.app')

@section('title', 'Tracking Dokumen')
@section('page-title', 'Tracking Dokumen')

@section('content')
<div x-data="trackingPage()" class="space-y-6">
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
        <div class="flex gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" x-model="searchQuery" @keyup.enter="search()"
                       placeholder="Ketik nomor dokumen atau judul kerjasama..."
                       class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>
            <button @click="search()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-search mr-2"></i> Lacak
            </button>
        </div>
    </div>

    <!-- Document Hierarchy Tree -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6" x-show="showTree" x-transition>
        <h3 class="font-bold text-slate-800 mb-6 flex items-center gap-2">
            <i class="fa-solid fa-diagram-project text-blue-600"></i> Hierarki Dokumen Kerjasama
        </h3>

        <!-- Tree Visualization -->
        <div class="space-y-4">
            <!-- MoU Level -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 flex items-center gap-4 cursor-pointer" @click="toggleNode('mou1')">
                    <i class="fa-solid fa-chevron-down text-blue-600 transition-transform" :class="{ 'rotate-180': !openNodes.mou1 }"></i>
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-slate-900">MoU - Kerjasama Penelitian AI</div>
                        <div class="text-xs text-slate-500 mt-0.5">023/MOU/TI/2023 • PT Teknologi Maju • 15 Jan 2024 - 15 Jan 2026</div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                </div>

                <div x-show="openNodes.mou1" x-transition class="border-t border-slate-200">
                    <!-- MoA Level -->
                    <div class="ml-8 border-l-2 border-blue-200">
                        <div class="pl-6 py-3 flex items-center gap-4 hover:bg-slate-50 cursor-pointer" @click="toggleNode('moa1')">
                            <i class="fa-solid fa-chevron-down text-purple-500 text-xs transition-transform" :class="{ 'rotate-180': !openNodes.moa1 }"></i>
                            <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center text-white text-xs">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-slate-800 text-sm">MoA - Implementasi Sistem Informasi RS</div>
                                <div class="text-xs text-slate-500">045/MOA/FK/2024 • PT RSJ Kota Malang</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Review</span>
                        </div>

                        <div x-show="openNodes.moa1" x-transition>
                            <!-- IA Level -->
                            <div class="ml-8 border-l-2 border-purple-200">
                                <div class="pl-6 py-3 flex items-center gap-4 hover:bg-slate-50">
                                    <div class="w-6 h-6 bg-teal-500 rounded flex items-center justify-center text-white text-xs">
                                        <i class="fa-solid fa-clipboard-check text-[10px]"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-slate-700 text-sm">IA - Workshop IoT Terapan</div>
                                        <div class="text-xs text-slate-500">089/IA/TI/2024 • 12 Jun 2024</div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Draft</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Another MoA -->
                    <div class="ml-8 border-l-2 border-blue-200">
                        <div class="pl-6 py-3 flex items-center gap-4 hover:bg-slate-50">
                            <div class="w-1 h-1"></div>
                            <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center text-white text-xs">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-slate-800 text-sm">MoA - Pengembangan Lab AI</div>
                                <div class="text-xs text-slate-500">078/MOA/TI/2024 • PT Teknologi Maju</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second MoU -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 flex items-center gap-4 cursor-pointer" @click="toggleNode('mou2')">
                    <i class="fa-solid fa-chevron-down text-blue-600 transition-transform" :class="{ 'rotate-180': !openNodes.mou2 }"></i>
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-slate-900">MoU - Program Magang Industri</div>
                        <div class="text-xs text-slate-500 mt-0.5">067/MOU/FKIP/2024 • Dinas Pendidikan Jatim • 10 Mar 2024 - 10 Mar 2027</div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                </div>

                <div x-show="openNodes.mou2" x-transition class="border-t border-slate-200">
                    <div class="ml-8 border-l-2 border-blue-200">
                        <div class="pl-6 py-3 flex items-center gap-4 hover:bg-slate-50">
                            <div class="w-1 h-1"></div>
                            <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center text-white text-xs">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-slate-800 text-sm">MoA - Kurikulum Berbasis Industri</div>
                                <div class="text-xs text-slate-500">099/MOA/FKIP/2024 • Dinas Pendidikan</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State (before search) -->
    <div x-show="!showTree" class="bg-white rounded-xl shadow-sm border border-slate-100 p-12 text-center">
        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-diagram-project text-3xl text-slate-400"></i>
        </div>
        <h3 class="font-bold text-slate-700 text-lg">Mulai Lacak Dokumen</h3>
        <p class="text-slate-500 text-sm mt-2 max-w-md mx-auto">Masukkan nomor atau judul dokumen pada kolom pencarian di atas untuk melihat hierarki dokumen kerjasama (MoU → MoA → IA).</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function trackingPage() {
    return {
        searchQuery: '',
        showTree: false,
        openNodes: { mou1: true, moa1: true, mou2: false },
        search() {
            this.showTree = this.searchQuery.trim().length > 0 || true;
        },
        toggleNode(key) {
            this.openNodes[key] = !this.openNodes[key];
        }
    }
}
</script>
@endpush
