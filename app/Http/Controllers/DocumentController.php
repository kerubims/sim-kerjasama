<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentHistory;
use App\Models\DocumentParty;
use App\Models\DocumentComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $query = Document::with(['parent', 'parties.user'])->orderBy('created_at', 'desc');
        
        // Filter berdasarkan role
        if ($user->hasRole('client')) {
            // Client hanya melihat dokumen dimana dia menjadi party DAN status BUKAN draft
            $query->where('status', '!=', 'draft')
                  ->whereHas('parties', function ($q) use ($user) {
                      $q->where('user_id', $user->id);
                  });
        } elseif ($user->hasRole('unit_pengusul')) {
            // Unit Pengusul hanya melihat dokumen dimana dia menjadi party DAN status bukan draft & bukan review_client
            $query->whereNotIn('status', ['draft', 'review_client'])
                  ->whereHas('parties', function ($q) use ($user) {
                      $q->where('user_id', $user->id);
                  });
        }
        // Super Admin melihat semua dokumen (no filter)
        
        $documents = $query->paginate(10);
        $allDocs = Document::all(); // for parent selection

        $clients = User::role('client')->get();
        $units = User::role('unit_pengusul')->get();
        
        return view('documents.index', compact('documents', 'clients', 'units', 'allDocs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:mou,moa,ia',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'parties' => 'required|array|min:2',
            'parties.*' => 'required|exists:users,id',
            'parent_id' => 'nullable|exists:documents,id'
        ]);

        $document = Document::create([
            'title' => $request->title,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'parent_id' => $request->parent_id,
            'status' => 'draft',
            'allow_client_upload' => false,
            'created_by' => Auth::id(),
            'document_number' => 'DOC-' . date('Ymd') . '-' . rand(1000, 9999),
            'content' => '<h1>Perjanjian Kerjasama</h1><p>Silakan edit isi perjanjian ini...</p>'
        ]);

        // Get unique users to avoid SQL unique constraint violations
        $uniqueParties = array_unique($request->parties);

        foreach ($uniqueParties as $userId) {
            $user = User::find($userId);
            if ($user) {
                // Determine role based on Spatie roles
                $roleType = $user->hasRole('client') ? 'client' : 'unit_pengusul';
                
                DocumentParty::create([
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'role_type' => $roleType
                ]);
            }
        }

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'Created',
            'message' => 'Dokumen ' . strtoupper($request->type) . ' dibuat dengan ' . count($uniqueParties) . ' pihak'
        ]);

        return redirect()->route('documents.editor', $document->id)->with('success', 'Dokumen berhasil dibuat');
    }

    public function editor($id)
    {
        $user = Auth::user();
        $document = Document::with([
            'histories.user', 
            'comments.user', 
            'parties.user'
        ])->findOrFail($id);

        // Access control: client cannot see draft documents
        if ($user->hasRole('client') && $document->status === 'draft') {
            abort(403, 'Anda belum memiliki akses ke dokumen ini.');
        }

        // Access control: unit_pengusul cannot see draft or review_client documents
        if ($user->hasRole('unit_pengusul') && in_array($document->status, ['draft', 'review_client'])) {
            abort(403, 'Anda belum memiliki akses ke dokumen ini.');
        }

        return view('documents.editor', ['doc' => $document]);
    }

    public function updateContent(Request $request, $id)
    {
        $request->validate(['content' => 'required|string']);
        
        $document = Document::findOrFail($id);
        $user = Auth::user();

        // Check canEdit logic (matching reference)
        $party = DocumentParty::where('document_id', $id)
                              ->where('user_id', $user->id)
                              ->first();
        $userHasSigned = $party && $party->signature_path;

        $canEdit = !$userHasSigned && (
            ($user->hasRole('super_admin') && $document->status !== 'signed') ||
            ($user->hasRole('client') && $document->status === 'review_client') ||
            ($user->hasRole('unit_pengusul') && $document->status === 'review_unit')
        );

        if (!$canEdit) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengedit dokumen ini.'], 403);
        }

        $document->update(['content' => $request->content]);

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'Edit',
            'message' => 'Konten diperbarui'
        ]);

        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);
        
        $document = Document::findOrFail($id);
        
        $updateData = ['status' => $request->status];

        // Save allow_client_upload when sending to client
        if ($request->status === 'review_client') {
            $updateData['allow_client_upload'] = $request->boolean('allowUpload', false);
        }

        $document->update($updateData);

        $message = 'Status berubah menjadi ' . $request->status;
        if ($request->status === 'review_client') {
            $message = 'Dikirim ke Client' . ($request->boolean('allowUpload') ? ' (dengan izin upload draft)' : '');
        }

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'Status Change',
            'message' => $message
        ]);

        return response()->json(['success' => true]);
    }

    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'text' => 'required|string',
            'anchor_id' => 'nullable|string',
            'quote' => 'nullable|string'
        ]);

        $document = Document::findOrFail($id);
        
        $document->comments()->create([
            'user_id' => Auth::id(),
            'text' => $request->text,
            'quoted_text' => $request->quote
        ]);

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'Comment',
            'message' => 'Menambahkan komentar'
        ]);

        return response()->json(['success' => true]);
    }

    public function signDocument(Request $request, $id)
    {
        $request->validate([
            'signature_file' => 'required|image|max:2048',
        ]);

        $document = Document::findOrFail($id);
        $user = Auth::user();
        $userId = $user->id;

        $party = DocumentParty::where('document_id', $id)
                              ->where('user_id', $userId)
                              ->first();

        if (!$party) {
            return response()->json(['success' => false, 'message' => 'Anda bukan pihak dalam dokumen ini.'], 403);
        }

        if ($party->signature_path) {
            return response()->json(['success' => false, 'message' => 'Anda sudah menandatangani dokumen ini.'], 400);
        }

        if ($request->hasFile('signature_file')) {
            $path = $request->file('signature_file')->store('signatures', 'public');

            $party->update([
                'signature_path' => $path,
                'signed_at' => now()
            ]);

            DocumentHistory::create([
                'document_id' => $document->id,
                'user_id' => $userId,
                'action' => 'Signed',
                'message' => 'Menandatangani dokumen (upload tanda tangan)'
            ]);

            // Auto status change based on who signs (matching reference logic)
            if ($user->hasRole('client')) {
                // Client signs → status moves to review_unit
                $document->update(['status' => 'review_unit']);
                DocumentHistory::create([
                    'document_id' => $document->id,
                    'user_id' => $userId,
                    'action' => 'Status Change',
                    'message' => 'Client telah menandatangani, status menjadi REVIEW UNIT'
                ]);
            } elseif ($user->hasRole('unit_pengusul')) {
                // Unit signs → status moves to signed (AKTIF)
                $document->update(['status' => 'signed']);
                DocumentHistory::create([
                    'document_id' => $document->id,
                    'user_id' => $userId,
                    'action' => 'Status Change',
                    'message' => 'Unit Pengusul telah menandatangani, dokumen AKTIF'
                ]);
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Gagal mengunggah file tanda tangan'], 400);
    }
}
