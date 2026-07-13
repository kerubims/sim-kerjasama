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
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Document::with(['parent', 'parties.user'])->orderBy('created_at', 'desc');
        
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->q . '%')
                  ->orWhere('document_number', 'like', '%' . $request->q . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'kadaluarsa') {
                $query->whereNotNull('end_date')->where('end_date', '<', now());
            } elseif ($request->status === 'expiring') {
                $query->where('status', 'signed')
                      ->whereNotNull('end_date')
                      ->where('end_date', '<', now()->addDays(30))
                      ->where('end_date', '>', now());
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('unit')) {
            $query->whereHas('parties', function ($q) use ($request) {
                $q->where('user_id', $request->unit)->where('role_type', 'unit_pengusul');
            });
        }

        if ($request->filled('client')) {
            $query->whereHas('parties', function ($q) use ($request) {
                $q->where('user_id', $request->client)->where('role_type', 'client');
            });
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }
        
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

        // Nomor dokumen terakhir per jenis
        $lastDocNumbers = [
            'mou' => Document::where('type', 'mou')->whereNotNull('document_number')->latest()->first()?->document_number,
            'moa' => Document::where('type', 'moa')->whereNotNull('document_number')->latest()->first()?->document_number,
            'ia'  => Document::where('type', 'ia')->whereNotNull('document_number')->latest()->first()?->document_number,
        ];
        
        return view('documents.index', compact('documents', 'clients', 'units', 'allDocs', 'lastDocNumbers'));
    }

    public function store(Request $request)
    {
        $messages = [
            'title.required' => 'Judul kerjasama wajib diisi.',
            'title.max' => 'Judul kerjasama maksimal 255 karakter.',
            'document_number.unique' => 'Nomor dokumen sudah digunakan. Silakan gunakan nomor yang lain.',
            'document_number.max' => 'Nomor dokumen maksimal 255 karakter.',
            'type.required' => 'Jenis dokumen wajib dipilih.',
            'type.in' => 'Jenis dokumen tidak valid.',
            'start_date.date' => 'Format tanggal mulai tidak valid.',
            'end_date.date' => 'Format tanggal selesai tidak valid.',
            'end_date.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'parties.required' => 'Pihak kerjasama wajib dipilih.',
            'parties.min' => 'Minimal harus memilih 2 pihak.',
            'parties.*.required' => 'Data pihak kerjasama tidak valid.',
            'parties.*.exists' => 'Pihak yang dipilih tidak terdaftar di sistem.',
            'parent_id.exists' => 'Dokumen rujukan tidak ditemukan.',
            'final_pdf.required_if' => 'File dokumen PDF wajib diunggah.',
            'final_pdf.file' => 'Data yang diunggah harus berupa file.',
            'final_pdf.mimes' => 'Dokumen yang diunggah harus berformat PDF.',
            'final_pdf.max' => 'Ukuran file PDF maksimal adalah 10MB.',
            'submission_type.required' => 'Tipe pengiriman dokumen tidak valid.',
            'submission_type.in' => 'Tipe pengiriman dokumen tidak dikenali.',
        ];

        $request->validate([
            'title' => 'required|string|max:255',
            'document_number' => 'nullable|string|max:255|unique:documents,document_number',
            'type' => 'required|in:mou,moa,ia',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'parties' => 'required|array|min:2',
            'parties.*' => 'required|exists:users,id',
            'parent_id' => 'nullable|exists:documents,id',
            'submission_type' => 'required|in:draft,upload',
            'final_pdf' => 'required_if:submission_type,upload|file|mimes:pdf|max:10240',
        ], $messages);

        if ($request->submission_type === 'draft') {
            // Document draft creation logic (pure local)
            // No external API calls needed
        }

        $filePath = null;
        if ($request->submission_type === 'upload' && $request->hasFile('final_pdf')) {
            $filePath = $request->file('final_pdf')->store('documents/final', 'public');
        }

        $document = Document::create([
            'title' => $request->title,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'parent_id' => $request->parent_id,
            'status' => $request->submission_type === 'upload' ? 'signed' : 'draft',
            'file_path' => $filePath,
            'allow_client_upload' => $request->has('allow_client_upload'),
            'created_by' => Auth::id(),
            'document_number' => $request->document_number ?: ('DOC-' . date('Ymd') . '-' . rand(1000, 9999)),
            'content' => $request->submission_type === 'upload' ? '<h1>Dokumen Eksternal</h1><p>Dokumen ini ditandatangani secara offline. Silakan lihat lampiran PDF.</p>' : '<h1>Perjanjian Kerjasama</h1><p>Silakan edit isi perjanjian ini...</p>'
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
                    'role_type' => $roleType,
                    'signature_path' => $request->submission_type === 'upload' ? 'offline_signed' : null,
                    'signed_at' => $request->submission_type === 'upload' ? now() : null,
                ]);
            }
        }

        $action = $request->submission_type === 'upload' ? 'Uploaded' : 'Created';
        $message = $request->submission_type === 'upload' 
            ? 'Dokumen ' . strtoupper($request->type) . ' (Final) diunggah dengan ' . count($uniqueParties) . ' pihak'
            : 'Dokumen ' . strtoupper($request->type) . ' dibuat dengan ' . count($uniqueParties) . ' pihak';

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'message' => $message
        ]);

        // Notify all parties
        $partyUsers = User::whereIn('id', $uniqueParties)->where('id', '!=', Auth::id())->get();
        Notification::send($partyUsers, new DocumentNotification(
            $request->submission_type === 'upload' ? 'Dokumen Final Tersedia' : 'Dokumen Baru',
            'Anda ditambahkan sebagai pihak dalam dokumen "' . Str::limit($document->title, 40) . '"',
            'fa-file-circle-plus',
            route('documents.editor', $document->id)
        ));

        $successMsg = $request->submission_type === 'upload' ? 'Dokumen final berhasil diunggah dan status menjadi aktif' : 'Dokumen berhasil dibuat';
        return redirect()->route('documents.editor', $document->id)->with('success', $successMsg);
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

    public function preview($id)
    {
        $document = Document::findOrFail($id);
        $user = Auth::user();

        // Access control
        if (!$user->hasRole('super_admin')) {
            $isParty = $document->parties()->where('user_id', $user->id)->exists();
            if (!$isParty) {
                abort(403, 'Anda bukan pihak yang terlibat dalam dokumen ini.');
            }

            if ($user->hasRole('client')) {
                if ($document->status === 'draft') {
                    abort(403, 'Anda belum memiliki akses ke dokumen ini.');
                }
            }
        }

        if ($document->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($document->file_path)) {
            return response()->file(storage_path('app/public/' . $document->file_path));
        }

        abort(404, 'File dokumen tidak ditemukan.');
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

    public function updateDates(Request $request, $id)
    {
        $request->validate([
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
            'document_number' => 'nullable|string|max:255',
        ], [
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date'     => 'Format tanggal mulai tidak valid.',
            'end_date.required'   => 'Tanggal selesai wajib diisi.',
            'end_date.date'       => 'Format tanggal selesai tidak valid.',
            'end_date.after'      => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        $document = Document::findOrFail($id);

        $oldStart = $document->start_date ? \Carbon\Carbon::parse($document->start_date)->format('d M Y') : '-';
        $oldEnd   = $document->end_date ? \Carbon\Carbon::parse($document->end_date)->format('d M Y') : '-';
        $newStart = \Carbon\Carbon::parse($request->start_date)->format('d M Y');
        $newEnd   = \Carbon\Carbon::parse($request->end_date)->format('d M Y');

        $updateData = [
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ];

        $messages = [];
        $messages[] = "Tanggal diperbarui: {$oldStart}–{$oldEnd} → {$newStart}–{$newEnd}";

        if ($request->filled('document_number') && $request->document_number !== $document->document_number) {
            $existing = Document::where('document_number', $request->document_number)->where('id', '!=', $document->id)->exists();
            if ($existing) {
                return response()->json(['success' => false, 'message' => 'Nomor dokumen sudah digunakan.'], 422);
            }
            $oldNum = $document->document_number ?? '-';
            $updateData['document_number'] = $request->document_number;
            $messages[] = "Nomor dokumen diperbarui: {$oldNum} → {$request->document_number}";
        }

        $document->update($updateData);

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id'     => Auth::id(),
            'action'      => 'Date Update',
            'message'     => implode('. ', $messages),
        ]);

        return response()->json(['success' => true, 'message' => 'Data dokumen berhasil diperbarui.']);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);
        
        $document = Document::findOrFail($id);
        
        $updateData = ['status' => $request->status];

        // Save allow_client_upload when sending to mitra
        if ($request->status === 'review_client') {
            $updateData['allow_client_upload'] = $request->boolean('allowUpload', false);
        }

        $document->update($updateData);

        $message = 'Status berubah menjadi ' . $request->status;
        if ($request->status === 'review_unit') {
            $message = 'Dikirim ke Unit Pengusul untuk review dan tanda tangan';
        } elseif ($request->status === 'review_client') {
            $message = 'Dikirim ke Mitra untuk review dan tanda tangan' . ($request->boolean('allowUpload') ? ' (dengan izin upload draft)' : '');
        }

        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => 'Status Change',
            'message' => $message
        ]);

        // Notify relevant parties based on new status
        $partyUsers = $document->parties()->with('user')->get()->pluck('user')->filter(fn($u) => $u->id !== Auth::id());
        if ($request->status === 'review_unit') {
            $targets = $partyUsers->filter(fn($u) => $u->hasRole('unit_pengusul'));
            Notification::send($targets, new DocumentNotification(
                'Dokumen Siap Ditinjau',
                'Dokumen "' . Str::limit($document->title, 40) . '" menunggu peninjauan dan tanda tangan Anda.',
                'fa-clipboard-check',
                route('documents.editor', $document->id)
            ));
        } elseif ($request->status === 'review_client') {
            $targets = $partyUsers->filter(fn($u) => $u->hasRole('client'));
            Notification::send($targets, new DocumentNotification(
                'Dokumen Siap Direview',
                'Dokumen "' . Str::limit($document->title, 40) . '" telah dikirim untuk ditinjau.',
                'fa-file-pen',
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
        
        if (!$user->hasRole('unit_pengusul') && !$user->hasRole('client') && !$user->hasRole('super_admin')) {
            abort(403);
        }

        // Tentukan status kembalian: jika klien menolak, kembalikan ke review_unit (Unit Pengusul)
        $newStatus = 'draft';
        if ($user->hasRole('client')) {
            $newStatus = 'review_unit';
        }

        $document->update(['status' => $newStatus]);
        
        DocumentParty::where('document_id', $document->id)->update([
            'signature_path' => null,
            'stamp_path' => null,
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
            'stamp_file' => 'nullable|image|max:2048',
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

            $updateData = [
                'signature_path' => $path,
                'signed_at' => now()
            ];

            // Upload stempel keabsahan jika ada
            if ($request->hasFile('stamp_file')) {
                $stampPath = $request->file('stamp_file')->store('stamps', 'public');
                $updateData['stamp_path'] = $stampPath;
            }

            $party->update($updateData);

            // Alur baru: Unit Pengusul tanda tangan dulu → Mitra
            if ($user->hasRole('unit_pengusul')) {
                // Cek apakah SEMUA unit pengusul sudah tanda tangan
                $unsignedUnits = DocumentParty::where('document_id', $document->id)
                                                ->where('role_type', 'unit_pengusul')
                                                ->whereNull('signature_path')
                                                ->count();
                if ($unsignedUnits === 0) {
                    // Semua unit sudah tanda tangan → kirim ke Mitra
                    $document->update(['status' => 'review_client']);
                    DocumentHistory::create([
                        'document_id' => $document->id,
                        'user_id' => $userId,
                        'action' => 'Status Change',
                        'message' => 'Semua Unit Pengusul telah menandatangani, dikirim ke Mitra untuk review'
                    ]);

                    // Notify mitra
                    $mitraUsers = $document->parties()->with('user')->where('role_type', 'client')->get()->pluck('user');
                    Notification::send($mitraUsers, new DocumentNotification(
                        'Dokumen Siap Direview',
                        'Dokumen "' . Str::limit($document->title, 40) . '" telah dikirim untuk ditinjau dan ditandatangani.',
                        'fa-file-pen',
                        route('documents.editor', $document->id)
                    ));
                } else {
                    DocumentHistory::create([
                        'document_id' => $document->id,
                        'user_id' => $userId,
                        'action' => 'Signed',
                        'message' => 'Menandatangani dokumen (Menunggu ' . $unsignedUnits . ' unit lain)'
                    ]);
                }
            } elseif ($user->hasRole('client')) {
                // Cek apakah SEMUA mitra sudah tanda tangan
                $unsignedClients = DocumentParty::where('document_id', $document->id)
                                                ->where('role_type', 'client')
                                                ->whereNull('signature_path')
                                                ->count();
                if ($unsignedClients === 0) {
                    // Semua mitra sudah tanda tangan → dokumen AKTIF
                    $document->update(['status' => 'signed']);
                    DocumentHistory::create([
                        'document_id' => $document->id,
                        'user_id' => $userId,
                        'action' => 'Status Change',
                        'message' => 'Semua Mitra telah menandatangani, dokumen AKTIF'
                    ]);
                } else {
                    DocumentHistory::create([
                        'document_id' => $document->id,
                        'user_id' => $userId,
                        'action' => 'Signed',
                        'message' => 'Menandatangani dokumen (Menunggu ' . $unsignedClients . ' mitra lain)'
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

    public function exportPdf($id)
    {
        $document = Document::findOrFail($id);

        if (!$document->file_path) {
            abort(404, 'Dokumen belum memiliki file PDF.');
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($document->file_path)) {
            return response()->download(storage_path('app/public/' . $document->file_path), Str::slug($document->title) . '.pdf');
        }

        abort(404, 'File PDF tidak ditemukan.');
    }
}
