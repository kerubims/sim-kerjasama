<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $stats = [
            'total' => Document::count(),
            'active' => Document::where('status', 'signed')->count(),
            'expiring' => Document::where('status', 'signed')
                            ->whereNotNull('end_date')
                            ->where('end_date', '<', now()->addDays(30))
                            ->where('end_date', '>', now())
                            ->count(),
            'expired' => Document::where('end_date', '<', now())->count(),
        ];

        // Fetch recent documents depending on role
        if ($isSuperAdmin) {
            $recentDocs = Document::with('parties.user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } else {
            $recentDocs = Document::whereHas('parties', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with('parties.user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        return view('dashboard', compact('stats', 'recentDocs'));
    }
}
