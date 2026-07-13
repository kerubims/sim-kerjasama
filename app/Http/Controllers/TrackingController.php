<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        // Get all root documents (MoU = no parent) with children hierarchy, only showing active/expired ones
        $query = Document::with([
                'parties.user',
                'children' => function ($q) {
                    $q->whereIn('status', ['signed', 'expired'])->with([
                        'parties.user',
                        'children' => function ($q2) {
                            $q2->whereIn('status', ['signed', 'expired'])->with('parties.user');
                        }
                    ]);
                }
            ])
            ->whereNull('parent_id')
            ->whereIn('status', ['signed', 'expired']);

        if ($search) {
            // Search in root or children
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhereHas('children', function ($q2) use ($search) {
                      $q2->where('title', 'like', "%{$search}%")
                         ->orWhere('document_number', 'like', "%{$search}%")
                         ->orWhereHas('children', function ($q3) use ($search) {
                             $q3->where('title', 'like', "%{$search}%")
                                ->orWhere('document_number', 'like', "%{$search}%");
                         });
                  });
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        return view('tracking.index', compact('documents', 'search'));
    }
}
