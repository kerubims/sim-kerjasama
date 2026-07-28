<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'partner', 'proposerUnit']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());
        $roles = Role::orderBy('name')->get();
        $partners = \App\Models\Partner::orderBy('name')->get();
        $units = \App\Models\ProposerUnit::orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'partners', 'units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|exists:roles,name',
            'proposer_unit_id' => 'nullable|exists:proposer_units,id',
            'partner_id' => 'nullable|exists:partners,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'proposer_unit_id' => $request->role === 'unit_pengusul' ? $request->proposer_unit_id : null,
            'partner_id' => $request->role === 'client' ? $request->partner_id : null,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => 'required|exists:roles,name',
            'proposer_unit_id' => 'nullable|exists:proposer_units,id',
            'partner_id' => 'nullable|exists:partners,id',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'proposer_unit_id' => $request->role === 'unit_pengusul' ? $request->proposer_unit_id : null,
            'partner_id' => $request->role === 'client' ? $request->partner_id : null,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
