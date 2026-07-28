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
            ->paginate(10)->withQueryString();

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

            // Bar Chart: Tren MoU & MoA per bulan
            $selectedYear = $request->input('year', date('Y'));
            $mouData = array_fill(0, 12, 0);
            $moaData = array_fill(0, 12, 0);
            $docsThisYear = Document::whereYear('created_at', $selectedYear)
                ->whereIn('status', ['signed', 'expired'])
                ->get();
            foreach ($docsThisYear as $doc) {
                $month = (int)$doc->created_at->format('n') - 1;
                if (strtolower($doc->type) === 'mou') $mouData[$month]++;
                if (strtolower($doc->type) === 'moa') $moaData[$month]++;
            }
            
            // Donut Chart: Hierarki Dokumen (Funnel / Progression)
            $mouCount = Document::where('type', 'MoU')
                ->whereIn('status', ['signed', 'expired'])
                ->count();
                
            $mouWithMoa = Document::where('type', 'MoU')
                ->whereIn('status', ['signed', 'expired'])
                ->whereHas('children', function($q) {
                    $q->where('type', 'MoA')->whereIn('status', ['signed', 'expired']);
                })->count();
                
            $mouWithIa = Document::where('type', 'MoU')
                ->whereIn('status', ['signed', 'expired'])
                ->whereHas('children', function($q) {
                    $q->where('type', 'MoA')->whereIn('status', ['signed', 'expired'])
                      ->whereHas('children', function($q2) {
                          $q2->where('type', 'IA')->whereIn('status', ['signed', 'expired']);
                      });
                })->count();

            // Donut Chart: Kategori Kerjasama (Scope)
            $scopeStats = Document::selectRaw('cooperation_scope, count(*) as count')
                ->whereNotNull('cooperation_scope')
                ->whereIn('status', ['signed', 'expired'])
                ->groupBy('cooperation_scope')
                ->get()->pluck('count', 'cooperation_scope');

            // Top Mitra
            $topMitra = \App\Models\DocumentParty::where('role_type', 'client')
                ->whereHas('document', function($q) {
                    $q->whereIn('status', ['signed', 'expired']);
                })
                ->with(['user.partner'])
                ->get()
                ->groupBy(function($party) {
                    return $party->user->nama_mitra ?? $party->user->partner->name ?? $party->user->name ?? 'Unknown';
                })
                ->map->count()
                ->sortByDesc(fn($count) => $count)
                ->take(5);

            $chartData = [
                'bar' => [
                    'mou' => $mouData,
                    'moa' => $moaData
                ],
                'donut' => [
                    'labels' => ['Total MoU', 'MoU dengan MoA', 'MoU dengan IA'],
                    'data' => [
                        $mouCount,
                        $mouWithMoa,
                        $mouWithIa
                    ]
                ],
                'scope' => [
                    'labels' => ['Lokal', 'Dalam Negeri', 'Nasional', 'Luar Negeri'],
                    'data' => [
                        $scopeStats['lokal'] ?? 0,
                        $scopeStats['dalam_negeri'] ?? 0,
                        $scopeStats['nasional'] ?? 0,
                        $scopeStats['luar_negeri'] ?? 0,
                    ]
                ],
                'top_mitra' => [
                    'labels' => $topMitra->keys()->toArray(),
                    'data' => $topMitra->values()->toArray(),
                ]
            ];
        }

        return view('dashboard', compact('stats', 'documents', 'chartData', 'isSuperAdmin'));
    }

    public function getChartData(Request $request)
    {
        if (!Auth::user()->hasRole('super_admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $type = $request->input('type', 'bar'); // default bar for backward compatibility
        
        if ($type === 'unit') {
            $filter = $request->input('filter', 'all'); // 'all', 'this_year', 'last_year'
            
            $query = \App\Models\DocumentParty::where('role_type', 'unit_pengusul')
                ->join('users', 'document_parties.user_id', '=', 'users.id')
                ->join('proposer_units', 'users.proposer_unit_id', '=', 'proposer_units.id')
                ->join('documents', 'document_parties.document_id', '=', 'documents.id')
                ->whereIn('documents.status', ['signed', 'expired'])
                ->selectRaw('proposer_units.name, count(document_parties.id) as count')
                ->groupBy('proposer_units.name');

            if ($filter === 'this_year') {
                $query->whereYear('document_parties.created_at', date('Y'));
            } elseif ($filter === 'last_year') {
                $query->whereYear('document_parties.created_at', date('Y') - 1);
            }

            $data = $query->get()->pluck('count', 'name');
            
            return response()->json([
                'labels' => $data->keys(),
                'data' => $data->values()
            ]);
        }
        
        // Fallback for bar chart
        $selectedYear = $request->input('year', date('Y'));
        $mouData = array_fill(0, 12, 0);
        $moaData = array_fill(0, 12, 0);
        $docsThisYear = Document::whereYear('created_at', $selectedYear)
            ->whereIn('status', ['signed', 'expired'])
            ->get();
        
        foreach ($docsThisYear as $doc) {
            $month = (int)$doc->created_at->format('n') - 1;
            if (strtolower($doc->type) === 'mou') $mouData[$month]++;
            if (strtolower($doc->type) === 'moa') $moaData[$month]++;
        }
        
        return response()->json([
            'mou' => $mouData,
            'moa' => $moaData
        ]);
    }
}
