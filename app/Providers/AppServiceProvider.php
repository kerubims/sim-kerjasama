<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Jalankan pengecekan dokumen kedaluwarsa maksimal 1 kali setiap hari
        // Menggunakan cache agar query ini tidak berjalan pada setiap request HTTP
        if (!\Illuminate\Support\Facades\Cache::has('daily_expired_document_check')) {
            // 1. Otomatis ubah status dokumen yang lewat batas waktu menjadi 'expired'
            \App\Models\Document::where('status', 'signed')
                ->whereNotNull('end_date')
                ->where('end_date', '<', now()->startOfDay())
                ->update(['status' => 'expired']);

            // 2. Pengecekan Notifikasi H-30 dan Kritis
            $admins = \App\Models\User::role('super_admin')->get();
            
            if ($admins->count() > 0) {
                // Ambil dokumen yang masih aktif dan punya tanggal selesai
                $activeDocs = \App\Models\Document::where('status', 'signed')
                    ->whereNotNull('end_date')
                    ->get();
                    
                $today = now()->startOfDay();
                
                foreach ($activeDocs as $doc) {
                    $endDate = \Carbon\Carbon::parse($doc->end_date)->startOfDay();
                    $diffDays = $today->diffInDays($endDate, false);
                    
                    // Trigger H-30 tepat
                    if ($diffDays === 30) {
                        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\DocumentNotification(
                            'Peringatan Kedaluwarsa H-30',
                            'Dokumen "' . \Illuminate\Support\Str::limit($doc->title, 40) . '" akan kedaluwarsa dalam 30 hari.',
                            'fa-triangle-exclamation text-yellow-500',
                            route('documents.editor', $doc->id)
                        ));
                    }
                }
            }

            \Illuminate\Support\Facades\Cache::put('daily_expired_document_check', true, now()->endOfDay());
        }
    }
}
