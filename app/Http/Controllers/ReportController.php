<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DocumentsExport;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => Document::count(),
            'active' => Document::where('status', 'signed')->count(),
            'mou' => Document::where('type', 'mou')->count(),
            'moa' => Document::where('type', 'moa')->count(),
            'ia' => Document::where('type', 'ia')->count(),
        ];

        $recentDocs = Document::with('parties.user')->orderBy('created_at', 'desc')->take(5)->get();
        
        return view('reports.index', compact('stats', 'recentDocs'));
    }

    public function exportPdf(Request $request)
    {
        $documents = Document::with(['parties.user', 'parent'])->get();
        $pdf = Pdf::loadView('reports.pdf', compact('documents'));
        return $pdf->download('laporan-dokumen-kerjasama.pdf');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new DocumentsExport, 'laporan-dokumen-kerjasama.xlsx');
    }
}
