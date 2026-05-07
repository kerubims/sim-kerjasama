<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentHistory;
use App\Models\DocumentParty;
use App\Models\DocumentComment;
use App\Models\User;
use App\Notifications\DocumentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $query = Document::with(['parent', 'parties.user'])->orderBy('created_at', 'desc');
        
        // Filter berdasarkan role
        if ($user->hasRole('super_admin')) {
            // Super Admin melihat semua dokumen (no filter)
        } else {
            // Semua pengguna selain Super Admin HANYA melihat dokumen dimana ia terdaftar sebagai party
            $query->whereHas('parties', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
            
            // Khusus Client, mereka TIDAK boleh melihat status draft (tapi unit boleh melihat semuanya)
            if ($user->hasRole('client')) {
                $query->where('status', '!=', 'draft');
            }
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

        // Notify all parties
        $partyUsers = User::whereIn('id', $uniqueParties)->where('id', '!=', Auth::id())->get();
        Notification::send($partyUsers, new DocumentNotification(
            'Dokumen Baru',
            'Anda ditambahkan sebagai pihak dalam dokumen "' . Str::limit($document->title, 40) . '"',
            'fa-file-circle-plus',
            route('documents.editor', $document->id)
        ));

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

        // Access control
        if (!$user->hasRole('super_admin')) {
            // Check if user is a party to the document
            $isParty = $document->parties()->where('user_id', $user->id)->exists();
            if (!$isParty) {
                abort(403, 'Anda bukan pihak yang terlibat dalam dokumen ini.');
            }

            if ($user->hasRole('client')) {
                if ($document->status === 'draft') {
                    abort(403, 'Anda belum memiliki akses ke dokumen ini.');
                }
            }
            // unit_pengusul can see all statuses as long as they are a party
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

        // Notify relevant parties based on new status
        $partyUsers = $document->parties()->with('user')->get()->pluck('user')->filter(fn($u) => $u->id !== Auth::id());
        if ($request->status === 'review_client') {
            $targets = $partyUsers->filter(fn($u) => $u->hasRole('client'));
            Notification::send($targets, new DocumentNotification(
                'Dokumen Siap Direview',
                'Dokumen "' . Str::limit($document->title, 40) . '" telah dikirim untuk ditinjau.',
                'fa-file-pen',
                route('documents.editor', $document->id)
            ));
        } elseif ($request->status === 'review_unit') {
            $targets = $partyUsers->filter(fn($u) => $u->hasRole('unit_pengusul'));
            Notification::send($targets, new DocumentNotification(
                'Dokumen Siap Ditinjau',
                'Dokumen "' . Str::limit($document->title, 40) . '" menunggu peninjauan dan tanda tangan Anda.',
                'fa-clipboard-check',
                route('documents.editor', $document->id)
            ));
        }

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
        
        $comment = $document->comments()->create([
            'user_id' => Auth::id(),
            'text' => $request->text,
            'quoted_text' => $request->quote,
            'anchor_id' => $request->anchor_id,
            'is_resolved' => false
        ]);

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'Comment',
            'message' => 'Menambahkan komentar'
        ]);

        // Notify other parties about new comment
        $partyUsers = $document->parties()->with('user')->get()->pluck('user')->filter(fn($u) => $u->id !== Auth::id());
        Notification::send($partyUsers, new DocumentNotification(
            'Komentar Baru',
            Auth::user()->name . ' menambahkan komentar pada dokumen "' . Str::limit($document->title, 30) . '"',
            'fa-comment',
            route('documents.editor', $document->id)
        ));

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id
            ]
        ]);
    }

    public function resolveComment(Request $request, $id, $commentId)
    {
        $document = Document::findOrFail($id);
        $comment = DocumentComment::where('document_id', $document->id)->findOrFail($commentId);

        $comment->update(['is_resolved' => true]);

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'Comment Resolved',
            'message' => 'Menyelesaikan komentar'
        ]);

        return response()->json(['success' => true]);
    }

    public function rejectDocument(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $user = Auth::user();
        
        if (!$user->hasRole('unit_pengusul') && !$user->hasRole('super_admin')) {
            abort(403);
        }

        // Return to review_client so admin/client can fix, and wipe signatures
        $document->update(['status' => 'review_client']);
        
        DocumentParty::where('document_id', $document->id)->update([
            'signature_path' => null,
            'signed_at' => null
        ]);

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'action' => 'Rejected',
            'message' => 'Dokumen ditolak/dikembalikan untuk revisi. Semua tanda tangan direset.'
        ]);

        // Notify all parties about rejection
        $partyUsers = $document->parties()->with('user')->get()->pluck('user')->filter(fn($u) => $u->id !== Auth::id());
        Notification::send($partyUsers, new DocumentNotification(
            'Dokumen Dikembalikan',
            'Dokumen "' . Str::limit($document->title, 40) . '" telah ditolak dan dikembalikan untuk revisi.',
            'fa-rotate-left',
            route('documents.editor', $document->id)
        ));

        // Also notify admins
        $admins = User::role('super_admin')->where('id', '!=', Auth::id())->get();
        Notification::send($admins, new DocumentNotification(
            'Dokumen Ditolak',
            'Dokumen "' . Str::limit($document->title, 40) . '" dikembalikan oleh ' . $user->name,
            'fa-rotate-left',
            route('documents.editor', $document->id)
        ));

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

            // Cek status role
            if ($user->hasRole('client')) {
                // Cek apakah SEMUA client sudah tanda tangan
                $unsignedClients = DocumentParty::where('document_id', $document->id)
                                                ->where('role_type', 'client')
                                                ->whereNull('signature_path')
                                                ->count();
                if ($unsignedClients === 0) {
                    $document->update(['status' => 'review_unit']);
                    DocumentHistory::create([
                        'document_id' => $document->id,
                        'user_id' => $userId,
                        'action' => 'Status Change',
                        'message' => 'Semua Client telah menandatangani, status menjadi REVIEW UNIT'
                    ]);
                } else {
                    DocumentHistory::create([
                        'document_id' => $document->id,
                        'user_id' => $userId,
                        'action' => 'Signed',
                        'message' => 'Menandatangani dokumen (Menunggu ' . $unsignedClients . ' client lain)'
                    ]);
                }
            } elseif ($user->hasRole('unit_pengusul')) {
                // Cek apakah SEMUA unit pengusul sudah tanda tangan
                $unsignedUnits = DocumentParty::where('document_id', $document->id)
                                                ->where('role_type', 'unit_pengusul')
                                                ->whereNull('signature_path')
                                                ->count();
                if ($unsignedUnits === 0) {
                    $document->update(['status' => 'signed']);
                    DocumentHistory::create([
                        'document_id' => $document->id,
                        'user_id' => $userId,
                        'action' => 'Status Change',
                        'message' => 'Semua Unit Pengusul telah menandatangani, dokumen AKTIF'
                    ]);
                } else {
                    DocumentHistory::create([
                        'document_id' => $document->id,
                        'user_id' => $userId,
                        'action' => 'Signed',
                        'message' => 'Menandatangani dokumen (Menunggu ' . $unsignedUnits . ' unit lain)'
                    ]);
                }
            }

            // Notify other parties about signature
            $partyUsers = $document->parties()->with('user')->get()->pluck('user')->filter(fn($u) => $u->id !== Auth::id());
            Notification::send($partyUsers, new DocumentNotification(
                'Tanda Tangan Baru',
                $user->name . ' telah menandatangani dokumen "' . Str::limit($document->title, 30) . '"',
                'fa-signature',
                route('documents.editor', $document->id)
            ));

            // Notify admins too
            $admins = User::role('super_admin')->where('id', '!=', $userId)->get();
            Notification::send($admins, new DocumentNotification(
                'Tanda Tangan Baru',
                $user->name . ' menandatangani "' . Str::limit($document->title, 30) . '"',
                'fa-signature',
                route('documents.editor', $document->id)
            ));

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Gagal mengunggah file tanda tangan'], 400);
    }

    public function search(Request $request)
    {
        $q = $request->input('q', '');
        $user = Auth::user();

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $query = Document::with('parties.user')
            ->where(function ($qb) use ($q) {
                $qb->where('title', 'like', "%{$q}%")
                   ->orWhere('document_number', 'like', "%{$q}%");
            });

        // Apply role-based filters
        if (!$user->hasRole('super_admin')) {
            $query->whereHas('parties', function ($qb) use ($user) {
                $qb->where('user_id', $user->id);
            });
            if ($user->hasRole('client')) {
                $query->where('status', '!=', 'draft');
            }
        }

        $results = $query->orderBy('created_at', 'desc')->take(5)->get()->map(function ($doc) {
            return [
                'id'     => $doc->id,
                'title'  => $doc->title,
                'type'   => strtoupper($doc->type),
                'status' => $doc->status,
                'url'    => route('documents.editor', $doc->id),
                'party'  => $doc->parties->where('role_type', 'client')->first()?->user?->name ?? '-',
            ];
        });

        return response()->json($results);
    }
}
