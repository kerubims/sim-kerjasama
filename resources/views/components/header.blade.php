<!-- Header -->
<header class="bg-white h-16 border-b border-slate-200 flex items-center justify-between px-8 shrink-0">
    <h2 class="text-xl font-bold text-slate-800">@yield('page-title', 'Dashboard')</h2>
    <div class="flex items-center gap-6">
        <!-- Search -->
        <div x-data="globalSearch()" class="relative" @click.away="showResults = false">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" placeholder="Cari dokumen..." x-model="query" @input.debounce.300ms="search()" @focus="if(results.length) showResults = true"
                   class="pl-10 pr-4 py-2 bg-slate-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 w-64 transition">
            <!-- Search Results Dropdown -->
            <div x-show="showResults && results.length > 0" x-transition x-cloak
                 class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden">
                <div class="p-3 border-b border-slate-100">
                    <h4 class="font-semibold text-xs text-slate-500 uppercase">Hasil Pencarian</h4>
                </div>
                <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
                    <template x-for="r in results" :key="r.id">
                        <a :href="r.url" class="px-4 py-3 hover:bg-slate-50 transition flex items-center gap-3 cursor-pointer block">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs shrink-0"
                                 :class="r.type === 'MOU' ? 'bg-blue-500' : r.type === 'MOA' ? 'bg-green-500' : 'bg-purple-500'">
                                <span x-text="r.type" class="font-bold text-[9px]"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-slate-800 truncate" x-text="r.title"></div>
                                <div class="text-xs text-slate-500" x-text="r.party"></div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium shrink-0"
                                  :class="r.status === 'signed' ? 'bg-green-100 text-green-700' : r.status === 'draft' ? 'bg-slate-100 text-slate-600' : 'bg-yellow-100 text-yellow-700'"
                                  x-text="r.status === 'signed' ? 'Aktif' : r.status.toUpperCase().replace('_', ' ')"></span>
                        </a>
                    </template>
                </div>
            </div>
            <div x-show="showResults && results.length === 0 && query.length >= 2" x-cloak
                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 z-50 p-6 text-center">
                <i class="fa-solid fa-magnifying-glass text-slate-300 text-2xl mb-2"></i>
                <p class="text-sm text-slate-500">Tidak ada dokumen ditemukan</p>
            </div>
        </div>

        <!-- Notification Bell -->
        <div x-data="notifBell()" x-init="fetchNotifs()" class="relative">
            <button @click="open = !open; if(open) fetchNotifs()" class="text-slate-400 hover:text-slate-600 relative">
                <i class="fa-regular fa-bell"></i>
                <span x-show="unreadCount > 0" x-cloak class="absolute -top-1.5 -right-1.5 min-w-[16px] h-4 bg-red-500 rounded-full border-2 border-white flex items-center justify-center">
                    <span class="text-[9px] font-bold text-white" x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
                </span>
            </button>

            <!-- Notification Dropdown -->
            <div x-show="open" @click.away="open = false" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h4 class="font-bold text-sm text-slate-800">Notifikasi</h4>
                    <button x-show="unreadCount > 0" @click="markAllRead()" class="text-xs text-blue-600 hover:underline font-medium">Tandai Semua Dibaca</button>
                </div>
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <template x-if="notifications.length === 0">
                        <div class="px-4 py-8 text-center text-slate-400 text-sm">
                            <i class="fa-regular fa-bell-slash text-2xl mb-2"></i>
                            <p>Belum ada notifikasi</p>
                        </div>
                    </template>
                    <template x-for="n in notifications" :key="n.id">
                        <a :href="n.url || '#'" @click="markRead(n)" class="px-4 py-3 hover:bg-slate-50 transition cursor-pointer block"
                           :class="n.read ? 'opacity-60' : 'bg-blue-50/30'">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                     :class="n.read ? 'bg-slate-100 text-slate-400' : 'bg-blue-100 text-blue-600'">
                                    <i class="fa-solid text-xs" :class="n.icon"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-slate-800 truncate" x-text="n.title"></div>
                                    <div class="text-xs text-slate-500 mt-0.5 line-clamp-2" x-text="n.message"></div>
                                    <div class="text-xs text-slate-400 mt-1" x-text="n.created_at"></div>
                                </div>
                                <div x-show="!n.read" class="w-2 h-2 bg-blue-500 rounded-full mt-2 shrink-0"></div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <!-- Action Buttons (yielded from pages) -->
        @yield('header-actions')
    </div>
</header>

@push('scripts')
<script>
function globalSearch() {
    return {
        query: '',
        results: [],
        showResults: false,
        async search() {
            if (this.query.length < 2) { this.results = []; this.showResults = false; return; }
            const res = await fetch('/documents/search?q=' + encodeURIComponent(this.query));
            this.results = await res.json();
            this.showResults = true;
        }
    }
}

function notifBell() {
    return {
        open: false,
        notifications: [],
        unreadCount: 0,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,
        async fetchNotifs() {
            const res = await fetch('/notifications');
            const data = await res.json();
            this.notifications = data.notifications;
            this.unreadCount = data.unread_count;
        },
        async markRead(n) {
            if (!n.read) {
                await fetch('/notifications/' + n.id + '/read', { method: 'POST', headers: {'X-CSRF-TOKEN': this.csrfToken} });
                n.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
        },
        async markAllRead() {
            await fetch('/notifications/read-all', { method: 'POST', headers: {'X-CSRF-TOKEN': this.csrfToken} });
            this.notifications.forEach(n => n.read = true);
            this.unreadCount = 0;
        }
    }
}
</script>
@endpush
