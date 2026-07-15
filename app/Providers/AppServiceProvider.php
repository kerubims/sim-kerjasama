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
            \App\Models\Document::where('status', 'signed')
                ->whereNotNull('end_date')
                ->where('end_date', '<', now()->startOfDay())
                ->update(['status' => 'expired']);

            \Illuminate\Support\Facades\Cache::put('daily_expired_document_check', true, now()->endOfDay());
        }
    }
}
