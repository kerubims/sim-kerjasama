@extends('layouts.app')

@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('header-actions')
<button x-data @click="$dispatch('toast', {type: 'info', message: 'Membuka form tambah user...'})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition">
    <i class="fa-solid fa-plus mr-2"></i> Tambah User
</button>
@endsection

@section('content')
@php
    $users = [
        ['id' => 1, 'name' => 'Super Admin', 'email' => 'admin@univ.ac.id', 'role' => 'super_admin', 'color' => '0D8ABC'],
        ['id' => 2, 'name' => 'Unit TI', 'email' => 'unit_ti@univ.ac.id', 'role' => 'unit_pengusul', 'color' => '27AE60'],
        ['id' => 3, 'name' => 'PT Teknologi Maju', 'email' => 'pt_tech@mitra.com', 'role' => 'client', 'color' => 'E67E22'],
        ['id' => 4, 'name' => 'PT RSJ Kota Malang', 'email' => 'pt_rsj@mitra.com', 'role' => 'client', 'color' => '9B59B6'],
        ['id' => 5, 'name' => 'Unit FK', 'email' => 'unit_fk@univ.ac.id', 'role' => 'unit_pengusul', 'color' => '3498DB'],
    ];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
    <table class="w-full text-left text-sm text-slate-600">
        <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500">
            <tr>
                <th class="px-6 py-4">User</th>
                <th class="px-6 py-4">Email</th>
                <th class="px-6 py-4">Role</th>
                <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($users as $u)
            <tr class="hover:bg-slate-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($u['name']) }}&background={{ $u['color'] }}&color=fff" class="h-8 w-8 rounded-full" alt="{{ $u['name'] }}">
                        <span class="font-medium text-slate-900">{{ $u['name'] }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 font-mono text-xs">{{ $u['email'] }}</td>
                <td class="px-6 py-4">
                    @php
                        $roleClass = match($u['role']) {
                            'super_admin' => 'bg-red-100 text-red-700',
                            'unit_pengusul' => 'bg-blue-100 text-blue-700',
                            'client' => 'bg-orange-100 text-orange-700',
                            default => 'bg-slate-100 text-slate-600',
                        };
                    @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $roleClass }}">
                        {{ strtoupper(str_replace('_', ' ', $u['role'])) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right" x-data>
                    <button @click="$dispatch('toast', {type: 'info', message: 'Membuka form edit...'})" class="text-slate-400 hover:text-blue-600 transition"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button @click="$dispatch('toast', {type: 'warning', message: 'Konfirmasi hapus user...'})" class="text-slate-400 hover:text-red-600 ml-3 transition"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
