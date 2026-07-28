<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\ProposerUnit;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    // ============================================================
    // MASTER MITRA (Partners)
    // ============================================================

    public function partnersIndex(Request $request)
    {
        $query = Partner::withCount('users')->orderBy('name');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $partners = $query->paginate(15)->withQueryString();

        return view('master.partners', compact('partners'));
    }

    public function partnerStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:partners,name',
            'category' => 'required|in:pemerintah,swasta,pendidikan,lainnya',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama mitra wajib diisi.',
            'name.unique' => 'Nama mitra sudah terdaftar.',
            'category.required' => 'Kategori mitra wajib dipilih.',
            'category.in' => 'Kategori mitra tidak valid.',
        ]);

        Partner::create($request->only(['name', 'category', 'address', 'phone', 'email', 'website', 'description']));

        return redirect()->route('master.partners')->with('success', 'Mitra berhasil ditambahkan.');
    }

    public function partnerUpdate(Request $request, $id)
    {
        $partner = Partner::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:partners,name,' . $partner->id,
            'category' => 'required|in:pemerintah,swasta,pendidikan,lainnya',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
        ]);

        $partner->update($request->only(['name', 'category', 'address', 'phone', 'email', 'website', 'description']));

        return redirect()->route('master.partners')->with('success', 'Data mitra berhasil diperbarui.');
    }

    public function partnerDestroy($id)
    {
        $partner = Partner::findOrFail($id);

        if ($partner->users()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Mitra masih memiliki pengguna terkait.'], 422);
        }

        $partner->delete();

        return response()->json(['success' => true, 'message' => 'Mitra berhasil dihapus.']);
    }

    // ============================================================
    // MASTER PENGUSUL (Proposer Units)
    // ============================================================

    public function unitsIndex(Request $request)
    {
        $query = ProposerUnit::withCount('users')->orderBy('name');

        if ($request->filled('q')) {
            $query->where(function ($qb) use ($request) {
                $qb->where('name', 'like', '%' . $request->q . '%')
                   ->orWhere('code', 'like', '%' . $request->q . '%');
            });
        }

        $units = $query->paginate(15)->withQueryString();

        return view('master.units', compact('units'));
    }

    public function unitStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:proposer_units,name',
            'code' => 'nullable|string|max:50|unique:proposer_units,code',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama unit wajib diisi.',
            'name.unique' => 'Nama unit sudah terdaftar.',
            'code.unique' => 'Kode unit sudah digunakan.',
        ]);

        ProposerUnit::create($request->only(['name', 'code', 'description']));

        return redirect()->route('master.units')->with('success', 'Unit pengusul berhasil ditambahkan.');
    }

    public function unitUpdate(Request $request, $id)
    {
        $unit = ProposerUnit::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:proposer_units,name,' . $unit->id,
            'code' => 'nullable|string|max:50|unique:proposer_units,code,' . $unit->id,
            'description' => 'nullable|string',
        ]);

        $unit->update($request->only(['name', 'code', 'description']));

        return redirect()->route('master.units')->with('success', 'Data unit pengusul berhasil diperbarui.');
    }

    public function unitDestroy($id)
    {
        $unit = ProposerUnit::findOrFail($id);

        if ($unit->users()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Unit masih memiliki pengguna terkait.'], 422);
        }

        $unit->delete();

        return response()->json(['success' => true, 'message' => 'Unit pengusul berhasil dihapus.']);
    }
}
