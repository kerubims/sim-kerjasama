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
        
        // Super Admin sees all documents
        $query = Document::with(['parent', 'parties.user'])->orderBy('created_at', 'desc');
        
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
        $document = Document::with([
            'histories.user', 
            'comments.user', 
            'parties.user'
        ])->findOrFail($id);

        return view('documents.editor', ['doc' => $document]);
    }

    public function updateContent(Request $request, $id)
    {
        $request->validate(['content' => 'required|string']);
        
        $document = Document::findOrFail($id);
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
        $request->validate(['status' => 'required|string', 'allowUpload' => 'boolean']);
        
        $document = Document::findOrFail($id);
        $document->update(['status' => $request->status]);

        $message = 'Status berubah menjadi ' . $request->status;
        if ($request->status === 'review_client') {
            $message = 'Dikirim ke Client' . ($request->allowUpload ? ' (dengan izin upload draft)' : '');
        } elseif ($request->status === 'signed' || $request->status === 'review_unit') {
            // Also logic for signature would go here later (Phase 5)
            $message = 'Menandatangani dokumen';
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
        $userId = Auth::id();

        $party = DocumentParty::where('document_id', $id)
                              ->where('user_id', $userId)
                              ->first();

        if (!$party) {
            return response()->json(['success' => false, 'message' => 'Anda bukan pihak dalam dokumen ini.'], 403);
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
                'message' => 'Mengunggah tanda tangan'
            ]);

            $totalParties = DocumentParty::where('document_id', $id)->count();
            $signedParties = DocumentParty::where('document_id', $id)->whereNotNull('signature_path')->count();

            if ($signedParties === $totalParties) {
                $document->update(['status' => 'signed']);
                DocumentHistory::create([
                    'document_id' => $document->id,
                    'user_id' => $userId,
                    'action' => 'Status Change',
                    'message' => 'Semua pihak telah menandatangani, status menjadi AKTIF'
                ]);
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Gagal mengunggah file tanda tangan'], 400);
    }
}
