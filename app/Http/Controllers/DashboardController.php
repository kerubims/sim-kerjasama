<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $baseQuery = Document::query();

        if (!$isSuperAdmin) {
            $baseQuery->whereHas('parties', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
            
            if ($user->hasRole('client')) {
                $baseQuery->where('status', '!=', 'draft');
            }
        }

        $documents = (clone $baseQuery)->with(['parties.user', 'parent'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = [];
        $chartData = [];

        if ($isSuperAdmin) {
            $stats = [
                'total_mitra' => \App\Models\DocumentParty::where('role_type', 'client')->distinct('user_id')->count(),
                'active' => Document::where('status', 'signed')->count(),
                'expiring' => Document::where('status', 'signed')
                                ->whereNotNull('end_date')
                                ->where('end_date', '<', now()->addDays(30))
                                ->where('end_date', '>', now())
                                ->count(),
                'expired' => Document::where('end_date', '<', now())->count(),
            ];

            // Bar Chart: Tren MoU & MoA per bulan (Tahun ini)
            $mouData = array_fill(0, 12, 0);
            $moaData = array_fill(0, 12, 0);
            $docsThisYear = Document::whereYear('created_at', date('Y'))->get();
            foreach ($docsThisYear as $doc) {
                $month = (int)$doc->created_at->format('n') - 1;
                if (strtolower($doc->type) === 'mou') $mouData[$month]++;
                if (strtolower($doc->type) === 'moa') $moaData[$month]++;
            }
            
            // Donut Chart: Komposisi Jenis Dokumen
            $typeStats = Document::selectRaw('type, count(*) as count')->groupBy('type')->get()->pluck('count', 'type');

            $chartData = [
                'bar' => [
                    'mou' => $mouData,
                    'moa' => $moaData
                ],
                'donut' => [
                    'labels' => ['MoU', 'MoA', 'IA'],
                    'data' => [
                        $typeStats['mou'] ?? 0,
                        $typeStats['moa'] ?? 0,
                        $typeStats['ia'] ?? 0
                    ]
                ]
            ];
        }

        return view('dashboard', compact('stats', 'documents', 'chartData', 'isSuperAdmin'));
    }
}
