@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')

@section('header-actions')
<button onclick="document.getElementById('modal-add-user').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
    <i class="fa-solid fa-plus mr-2"></i> Tambah Pengguna
</button>
@endsection

@section('content')
<div x-data="userPage()" class="space-y-6">
    {{-- Flash Messages --}}
    @if(session('success'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
        <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
        <i class="fa-solid fa-times-circle"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Search --}}
    <div class="flex items-center gap-3">
        <form method="GET" action="{{ route('users.index') }}" class="relative flex-1 max-w-md">
            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                   class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
        </form>
        @if(request('search'))
        <a href="{{ route('users.index') }}" class="text-sm text-slate-500 hover:text-slate-700"><i class="fa-solid fa-times mr-1"></i>Reset</a>
        @endif
        <div class="ml-auto text-sm text-slate-500">Total: <span class="font-semibold text-slate-700">{{ $users->total() }}</span> pengguna</div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500">
                <tr>
                    <th class="px-6 py-4">Pengguna</th>
                    <th class="px-6 py-4">Email</th>
                    <th class="px-6 py-4">Unit Pengusul</th>
                    <th class="px-6 py-4">Mitra</th>
                    <th class="px-6 py-4">Role</th>
                    <th class="px-6 py-4">Terdaftar</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $u)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @php
                                $bgColor = match($u->roles->first()?->name) {
                                    'super_admin' => '0D8ABC',
                                    'unit_pengusul' => '27AE60',
                                    'client' => 'E67E22',
                                    default => '95A5A6',
                                };
                            @endphp
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($u->name) }}&background={{ $bgColor }}&color=fff" class="h-8 w-8 rounded-full" alt="">
                            <span class="font-medium text-slate-900">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs">{{ $u->email }}</td>
                    <td class="px-6 py-4 text-xs">{{ $u->proposerUnit->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-xs">{{ $u->partner->name ?? '-' }}</td>
                    <td class="px-6 py-4">
                        @php
                            $roleName = $u->roles->first()?->name ?? 'none';
                            $roleClass = match($roleName) {
                                'super_admin' => 'bg-red-100 text-red-700',
                                'unit_pengusul' => 'bg-blue-100 text-blue-700',
                                'client' => 'bg-orange-100 text-orange-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $roleClass }}">
                            {{ strtoupper(str_replace('client', 'mitra', str_replace('_', ' ', $roleName))) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-500">{{ $u->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <button @click="editUser({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->email }}', '{{ $u->proposer_unit_id ?? '' }}', '{{ $u->partner_id ?? '' }}', '{{ $roleName }}')" class="text-slate-400 hover:text-blue-600 transition" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                        @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $u->id) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-red-600 ml-3 transition" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-slate-400">Tidak ada pengguna ditemukan</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Modal Add User --}}
<div id="modal-add-user" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="px-6 py-5">
                <h3 class="text-lg font-semibold text-slate-900 mb-4"><i class="fa-solid fa-user-plus mr-2 text-blue-600"></i>Tambah Pengguna Baru</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nama lengkap">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="email@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Minimal 6 karakter">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                        <select name="role" required onchange="toggleRoleFields(this.value, 'create')" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Role --</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ strtoupper(str_replace('client', 'mitra', str_replace('_', ' ', $role->name))) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="create-unit-field" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Unit Pengusul <span class="text-xs text-slate-400 font-normal">(wajib untuk unit)</span></label>
                        <select name="proposer_unit_id" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Unit --</option>
                            @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="create-partner-field" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mitra / Instansi <span class="text-xs text-slate-400 font-normal">(wajib untuk mitra)</span></label>
                        <select name="partner_id" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Mitra --</option>
                            @foreach($partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-add-user').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit User --}}
<div id="modal-edit-user" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        <form id="form-edit-user" method="POST">
            @csrf @method('PUT')
            <div class="px-6 py-5">
                <h3 class="text-lg font-semibold text-slate-900 mb-4"><i class="fa-solid fa-user-pen mr-2 text-blue-600"></i>Edit Pengguna</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" id="edit-name" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" id="edit-email" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-slate-400 font-normal">(kosongkan jika tidak diubah)</span></label>
                        <input type="password" name="password" minlength="6" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="••••••">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                        <select name="role" id="edit-role" onchange="toggleRoleFields(this.value, 'edit')" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ strtoupper(str_replace('client', 'mitra', str_replace('_', ' ', $role->name))) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="edit-unit-field" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Unit Pengusul <span class="text-xs text-slate-400 font-normal">(wajib untuk unit)</span></label>
                        <select name="proposer_unit_id" id="edit-unit" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Unit --</option>
                            @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="edit-partner-field" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mitra / Instansi <span class="text-xs text-slate-400 font-normal">(wajib untuk mitra)</span></label>
                        <select name="partner_id" id="edit-partner" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Mitra --</option>
                            @foreach($partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-edit-user').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleRoleFields(role, prefix) {
    if (role === 'client') {
        document.getElementById(prefix + '-partner-field').classList.remove('hidden');
        document.getElementById(prefix + '-unit-field').classList.add('hidden');
    } else if (role === 'unit_pengusul') {
        document.getElementById(prefix + '-unit-field').classList.remove('hidden');
        document.getElementById(prefix + '-partner-field').classList.add('hidden');
    } else {
        document.getElementById(prefix + '-unit-field').classList.add('hidden');
        document.getElementById(prefix + '-partner-field').classList.add('hidden');
    }
}

function userPage() {
    return {
        editUser(id, name, email, unit_id, partner_id, role) {
            document.getElementById('form-edit-user').action = '/users/' + id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-unit').value = unit_id;
            document.getElementById('edit-partner').value = partner_id;
            document.getElementById('edit-role').value = role;
            
            toggleRoleFields(role, 'edit');
            
            document.getElementById('modal-edit-user').classList.remove('hidden');
        }
    }
}
</script>
@endpush
