<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DocumentsExport;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = Document::query();

        if ($request->filled('start_date')) {
            $baseQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $baseQuery->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('type')) {
            $baseQuery->where('type', strtolower($request->type));
        }
        if ($request->filled('status')) {
            $baseQuery->where('status', strtolower($request->status));
        }
        if ($request->filled('unit')) {
            $baseQuery->whereHas('parties', function($q) use ($request) {
                $q->where('role_type', 'unit_pengusul')
                  ->whereHas('user', function($uq) use ($request) {
                      $uq->where('jabatan', $request->unit);
                  });
            });
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'signed')->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'review' => (clone $baseQuery)->whereIn('status', ['review_client', 'review_unit'])->count(),
            'expired' => (clone $baseQuery)->where('status', 'expired')->count(),
            'mou' => (clone $baseQuery)->where('type', 'mou')->count(),
            'moa' => (clone $baseQuery)->where('type', 'moa')->count(),
            'ia' => (clone $baseQuery)->where('type', 'ia')->count(),
        ];

        $units = \App\Models\User::role('unit_pengusul')
            ->pluck('jabatan', 'jabatan')
            ->filter()
            ->toArray();

        $query = (clone $baseQuery)->with('parties.user');
        
        $recentDocs = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        
        $allDocs = $query->orderBy('created_at', 'desc')->get();
        
        return view('reports.index', compact('stats', 'recentDocs', 'allDocs', 'units'));
    }

    public function exportPdf(Request $request)
    {
        $query = Document::with(['parties.user', 'parent']);
        
        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->end_date);
        if ($request->filled('type')) $query->where('type', strtolower($request->type));
        if ($request->filled('status')) $query->where('status', strtolower($request->status));
        if ($request->filled('unit')) {
            $query->whereHas('parties', function($q) use ($request) {
                $q->where('role_type', 'unit_pengusul')
                  ->whereHas('user', function($uq) use ($request) {
                      $uq->where('jabatan', $request->unit);
                  });
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->get();
        $pdf = Pdf::loadView('reports.pdf', compact('documents'));
        return $pdf->download('laporan-dokumen-kerjasama.pdf');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new DocumentsExport($request->all()), 'laporan-dokumen-kerjasama.xlsx');
    }
}
