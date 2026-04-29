<!-- Header -->
<header class="bg-white h-16 border-b border-slate-200 flex items-center justify-between px-8 shrink-0">
    <h2 class="text-xl font-bold text-slate-800">@yield('page-title', 'Dashboard')</h2>
    <div class="flex items-center gap-6">
        <!-- Search -->
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" placeholder="Cari dokumen..."
                   class="pl-10 pr-4 py-2 bg-slate-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 w-64 transition">
        </div>

        <!-- Notification Bell -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="text-slate-400 hover:text-slate-600 relative">
                <i class="fa-regular fa-bell"></i>
                <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
            </button>

            <!-- Notification Dropdown -->
            <div x-show="open" @click.away="open = false" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h4 class="font-bold text-sm text-slate-800">Notifikasi</h4>
                </div>
                <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                    <div class="px-4 py-3 hover:bg-slate-50 transition cursor-pointer">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 shrink-0 mt-0.5">
                                <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-slate-800">3 dokumen akan kedaluwarsa</div>
                                <div class="text-xs text-slate-500 mt-0.5">Masa berlaku kurang dari 30 hari</div>
                                <div class="text-xs text-slate-400 mt-1">2 jam yang lalu</div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 hover:bg-slate-50 transition cursor-pointer">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 shrink-0 mt-0.5">
                                <i class="fa-solid fa-file-pen text-xs"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-slate-800">Dokumen baru menunggu review</div>
                                <div class="text-xs text-slate-500 mt-0.5">MoU antara Univ - PT Teknologi Maju</div>
                                <div class="text-xs text-slate-400 mt-1">5 jam yang lalu</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-slate-50 border-t border-slate-100 text-center">
                    <a href="#" class="text-xs text-blue-600 font-medium hover:underline">Lihat Semua Notifikasi</a>
                </div>
            </div>
        </div>

        <!-- Action Buttons (yielded from pages) -->
        @yield('header-actions')
    </div>
</header>
