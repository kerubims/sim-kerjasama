@extends('layouts.app')

@section('title', 'Master Pengusul')
@section('page-title', 'Master Data Unit Pengusul')

@section('header-actions')
<button onclick="document.getElementById('modal-create-unit').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
    <i class="fa-solid fa-plus mr-2"></i> Tambah Unit
</button>
@endsection

@section('content')
{{-- Filter Toolbar --}}
<div class="bg-white px-4 py-3 rounded-xl shadow-sm border border-slate-100 mb-4 flex items-center gap-3 flex-wrap">
    <form action="{{ route('master.units') }}" method="GET" class="flex items-center gap-3 flex-wrap w-full">
        <div class="relative flex-1 min-w-[200px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode unit..." class="w-full pl-8 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition">
            <i class="fa-solid fa-filter mr-1"></i> Cari
        </button>
        @if(request('q'))
        <a href="{{ route('master.units') }}" class="text-xs text-slate-400 hover:text-red-500 transition">
            <i class="fa-solid fa-rotate-left mr-1"></i> Reset
        </a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500">
                <tr>
                    <th class="px-6 py-4">No</th>
                    <th class="px-6 py-4">Nama Unit</th>
                    <th class="px-6 py-4">Kode</th>
                    <th class="px-6 py-4">Deskripsi</th>
                    <th class="px-6 py-4">Pengguna</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($units as $idx => $unit)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 text-slate-500">{{ $units->firstItem() + $idx }}</td>
                    <td class="px-6 py-4 font-medium text-slate-900">{{ $unit->name }}</td>
                    <td class="px-6 py-4">
                        @if($unit->code)
                            <span class="px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-600">{{ $unit->code }}</span>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-slate-500 max-w-xs truncate">{{ $unit->description ?: '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                            <i class="fa-solid fa-user"></i> {{ $unit->users_count }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        <button type="button" onclick='openEditUnitModal(@json($unit))' class="text-slate-500 hover:text-slate-700 text-xs font-medium">
                            <i class="fa-solid fa-pen mr-1"></i> Edit
                        </button>
                        <button type="button" onclick="deleteUnit({{ $unit->id }}, '{{ addslashes($unit->name) }}')" class="text-red-500 hover:text-red-700 text-xs font-medium ml-3">
                            <i class="fa-solid fa-trash mr-1"></i> Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-slate-400">Belum ada data unit pengusul</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
        {{ $units->links() }}
    </div>
</div>

{{-- Modal Create Unit --}}
<div id="modal-create-unit" class="fixed inset-0 bg-slate-900/50 {{ $errors->any() ? '' : 'hidden' }} items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
        <form action="{{ route('master.units.store') }}" method="POST" class="flex flex-col min-h-0">
            @csrf
            <div class="px-6 pt-6 pb-4 overflow-y-auto flex-1">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fa-solid fa-building text-blue-500 mr-2"></i> Tambah Unit Pengusul
                </h3>

                @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Unit <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Nama fakultas/lembaga/unit...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Unit</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Kode singkat (opsional)...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Deskripsi singkat...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex flex-row-reverse gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-semibold transition">Simpan</button>
                <button type="button" onclick="document.getElementById('modal-create-unit').classList.add('hidden')" class="bg-white text-slate-900 px-4 py-2 rounded-md text-sm font-semibold ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit Unit --}}
<div id="modal-edit-unit" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
        <form id="form-edit-unit" method="POST" class="flex flex-col min-h-0">
            @csrf
            @method('PUT')
            <div class="px-6 pt-6 pb-4 overflow-y-auto flex-1">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fa-solid fa-pen text-blue-500 mr-2"></i> Edit Unit Pengusul
                </h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Unit <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit-unit-name" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Unit</label>
                        <input type="text" name="code" id="edit-unit-code" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                        <textarea name="description" id="edit-unit-description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"></textarea>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex flex-row-reverse gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-semibold transition">Simpan Perubahan</button>
                <button type="button" onclick="document.getElementById('modal-edit-unit').classList.add('hidden')" class="bg-white text-slate-900 px-4 py-2 rounded-md text-sm font-semibold ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEditUnitModal(unit) {
    document.getElementById('form-edit-unit').action = '/master/units/' + unit.id;
    document.getElementById('edit-unit-name').value = unit.name;
    document.getElementById('edit-unit-code').value = unit.code || '';
    document.getElementById('edit-unit-description').value = unit.description || '';
    document.getElementById('modal-edit-unit').classList.remove('hidden');
}

async function deleteUnit(id, name) {
    if (!confirm(`Hapus unit "${name}"? Tindakan ini tidak dapat dibatalkan.`)) return;

    try {
        const res = await fetch(`/master/units/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Gagal menghapus unit.');
        }
    } catch (err) {
        alert('Gagal terhubung ke server.');
    }
}
</script>
@endpush
