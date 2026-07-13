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
        $stats = [
            'total' => Document::count(),
            'active' => Document::where('status', 'signed')->count(),
            'mou' => Document::where('type', 'mou')->count(),
            'moa' => Document::where('type', 'moa')->count(),
            'ia' => Document::where('type', 'ia')->count(),
        ];

        $query = Document::with('parties.user');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('type')) {
            $query->where('type', strtolower($request->type));
        }
        if ($request->filled('status')) {
            $query->where('status', strtolower($request->status));
        }

        $recentDocs = $query->orderBy('created_at', 'desc')->take(20)->get();
        
        return view('reports.index', compact('stats', 'recentDocs'));
    }

    public function exportPdf(Request $request)
    {
        $query = Document::with(['parties.user', 'parent']);
        
        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->end_date);
        if ($request->filled('type')) $query->where('type', strtolower($request->type));
        if ($request->filled('status')) $query->where('status', strtolower($request->status));

        $documents = $query->orderBy('created_at', 'desc')->get();
        $pdf = Pdf::loadView('reports.pdf', compact('documents'));
        return $pdf->download('laporan-dokumen-kerjasama.pdf');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new DocumentsExport($request->all()), 'laporan-dokumen-kerjasama.xlsx');
    }
}
