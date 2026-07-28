@extends('layouts.app')

@section('title', 'Master Mitra')
@section('page-title', 'Master Data Mitra')

@section('header-actions')
<button onclick="document.getElementById('modal-create-partner').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
    <i class="fa-solid fa-plus mr-2"></i> Tambah Mitra
</button>
@endsection

@section('content')
{{-- Filter Toolbar --}}
<div class="bg-white px-4 py-3 rounded-xl shadow-sm border border-slate-100 mb-4 flex items-center gap-3 flex-wrap">
    <form action="{{ route('master.partners') }}" method="GET" class="flex items-center gap-3 flex-wrap w-full">
        <div class="relative flex-1 min-w-[200px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama mitra..." class="w-full pl-8 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <select name="category" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <option value="">Semua Kategori</option>
            <option value="pemerintah" {{ request('category') == 'pemerintah' ? 'selected' : '' }}>Pemerintah</option>
            <option value="swasta" {{ request('category') == 'swasta' ? 'selected' : '' }}>Swasta</option>
            <option value="pendidikan" {{ request('category') == 'pendidikan' ? 'selected' : '' }}>Pendidikan</option>
            <option value="lainnya" {{ request('category') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
        </select>
        <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition">
            <i class="fa-solid fa-filter mr-1"></i> Filter
        </button>
        @if(request('q') || request('category'))
        <a href="{{ route('master.partners') }}" class="text-xs text-slate-400 hover:text-red-500 transition">
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
                    <th class="px-6 py-4">Nama Mitra</th>
                    <th class="px-6 py-4">Kategori</th>
                    <th class="px-6 py-4">Email</th>
                    <th class="px-6 py-4">Telepon</th>
                    <th class="px-6 py-4">Pengguna</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($partners as $idx => $partner)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 text-slate-500">{{ $partners->firstItem() + $idx }}</td>
                    <td class="px-6 py-4 font-medium text-slate-900">
                        {{ $partner->name }}
                        @if($partner->website)
                            <a href="{{ $partner->website }}" target="_blank" class="text-blue-500 hover:text-blue-700 ml-1"><i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $catClass = match($partner->category) {
                                'pemerintah' => 'bg-blue-100 text-blue-700',
                                'swasta' => 'bg-green-100 text-green-700',
                                'pendidikan' => 'bg-purple-100 text-purple-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded text-xs font-medium {{ $catClass }}">{{ ucfirst($partner->category) }}</span>
                    </td>
                    <td class="px-6 py-4">{{ $partner->email ?: '-' }}</td>
                    <td class="px-6 py-4">{{ $partner->phone ?: '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                            <i class="fa-solid fa-user"></i> {{ $partner->users_count }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        <button type="button" onclick='openEditPartnerModal(@json($partner))' class="text-slate-500 hover:text-slate-700 text-xs font-medium">
                            <i class="fa-solid fa-pen mr-1"></i> Edit
                        </button>
                        <button type="button" onclick="deletePartner({{ $partner->id }}, '{{ addslashes($partner->name) }}')" class="text-red-500 hover:text-red-700 text-xs font-medium ml-3">
                            <i class="fa-solid fa-trash mr-1"></i> Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-slate-400">Belum ada data mitra</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
        {{ $partners->links() }}
    </div>
</div>

{{-- Modal Create Partner --}}
<div id="modal-create-partner" class="fixed inset-0 bg-slate-900/50 {{ $errors->any() ? '' : 'hidden' }} items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        <form action="{{ route('master.partners.store') }}" method="POST">
            @csrf
            <div class="px-6 pt-6 pb-4">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fa-solid fa-building text-blue-500 mr-2"></i> Tambah Mitra Baru
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
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Mitra <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Nama institusi/perusahaan...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="category" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white">
                            <option value="pemerintah" {{ old('category') == 'pemerintah' ? 'selected' : '' }}>Pemerintah</option>
                            <option value="swasta" {{ old('category', 'swasta') == 'swasta' ? 'selected' : '' }}>Swasta</option>
                            <option value="pendidikan" {{ old('category') == 'pendidikan' ? 'selected' : '' }}>Pendidikan</option>
                            <option value="lainnya" {{ old('category') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="email@mitra.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="08xx...">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Website</label>
                        <input type="url" name="website" value="{{ old('website') }}" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="https://...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
                        <textarea name="address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Alamat lengkap...">{{ old('address') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Deskripsi singkat...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex flex-row-reverse gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-semibold transition">Simpan</button>
                <button type="button" onclick="document.getElementById('modal-create-partner').classList.add('hidden')" class="bg-white text-slate-900 px-4 py-2 rounded-md text-sm font-semibold ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit Partner --}}
<div id="modal-edit-partner" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 backdrop-blur-sm flex">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        <form id="form-edit-partner" method="POST">
            @csrf
            @method('PUT')
            <div class="px-6 pt-6 pb-4">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fa-solid fa-pen text-blue-500 mr-2"></i> Edit Data Mitra
                </h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Mitra <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit-partner-name" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="category" id="edit-partner-category" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white">
                            <option value="pemerintah">Pemerintah</option>
                            <option value="swasta">Swasta</option>
                            <option value="pendidikan">Pendidikan</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" id="edit-partner-email" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Telepon</label>
                            <input type="text" name="phone" id="edit-partner-phone" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Website</label>
                        <input type="url" name="website" id="edit-partner-website" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
                        <textarea name="address" id="edit-partner-address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                        <textarea name="description" id="edit-partner-description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"></textarea>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex flex-row-reverse gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-semibold transition">Simpan Perubahan</button>
                <button type="button" onclick="document.getElementById('modal-edit-partner').classList.add('hidden')" class="bg-white text-slate-900 px-4 py-2 rounded-md text-sm font-semibold ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEditPartnerModal(partner) {
    document.getElementById('form-edit-partner').action = '/master/partners/' + partner.id;
    document.getElementById('edit-partner-name').value = partner.name;
    document.getElementById('edit-partner-category').value = partner.category;
    document.getElementById('edit-partner-email').value = partner.email || '';
    document.getElementById('edit-partner-phone').value = partner.phone || '';
    document.getElementById('edit-partner-website').value = partner.website || '';
    document.getElementById('edit-partner-address').value = partner.address || '';
    document.getElementById('edit-partner-description').value = partner.description || '';
    document.getElementById('modal-edit-partner').classList.remove('hidden');
}

async function deletePartner(id, name) {
    if (!confirm(`Hapus mitra "${name}"? Tindakan ini tidak dapat dibatalkan.`)) return;

    try {
        const res = await fetch(`/master/partners/${id}`, {
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
            alert(data.message || 'Gagal menghapus mitra.');
        }
    } catch (err) {
        alert('Gagal terhubung ke server.');
    }
}
</script>
@endpush
