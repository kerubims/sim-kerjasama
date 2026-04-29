@php
    $currentRoute = Route::currentRouteName();
    $user = Auth::user();
    $isSuperAdmin = $user && $user->hasRole('super_admin');
@endphp

<!-- Sidebar -->
<div class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0">
    <!-- Logo -->
    <div class="p-6 flex items-center gap-3">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <span class="font-bold text-lg text-slate-800 tracking-tight">SIM-KERMA</span>
    </div>

    <!-- Navigation -->
    <div class="px-4 py-2">
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">System</div>
        <nav class="space-y-1">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ $currentRoute === 'dashboard' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <i class="fa-solid fa-chart-pie w-5"></i> Dashboard
            </a>

            @role('super_admin')
            <a href="{{ route('documents.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ str_starts_with($currentRoute, 'documents') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <i class="fa-solid fa-file-lines w-5"></i> Dokumen Kerjasama
            </a>
            <a href="{{ route('tracking.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ $currentRoute === 'tracking.index' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <i class="fa-solid fa-diagram-project w-5"></i> Tracking Dokumen
            </a>
            <a href="{{ route('reports.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ $currentRoute === 'reports.index' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <i class="fa-solid fa-file-export w-5"></i> Export Laporan
            </a>
            <a href="{{ route('users.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ str_starts_with($currentRoute, 'users') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <i class="fa-solid fa-users w-5"></i> Users
            </a>
            @endrole
        </nav>
    </div>

    <!-- User Profile (Bottom) -->
    <div class="mt-auto p-4 border-t border-slate-200">
        <div class="flex items-center gap-3">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&background=0D8ABC&color=fff"
                 class="h-10 w-10 rounded-full border border-slate-200" alt="Avatar">
            <div class="flex-1 min-w-0">
                <div class="text-sm font-bold text-slate-900 truncate">{{ $user->name ?? 'Guest' }}</div>
                <div class="text-xs text-slate-500 truncate">{{ $user->email ?? '' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-slate-400 hover:text-red-500 transition" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </div>
</div>
