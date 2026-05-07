<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('documents:check-expiry')]
#[Description('Check for expiring soon and expired documents')]
class CheckDocumentExpiry extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = \Carbon\Carbon::today();
        
        // 1. Check for expired documents (Signed -> Expired)
        $expiredDocs = \App\Models\Document::where('status', 'signed')
            ->whereNotNull('end_date')
            ->where('end_date', '<', $today)
            ->get();

        foreach ($expiredDocs as $doc) {
            $doc->update(['status' => 'expired']);
            
            // Notify all parties
            $users = $doc->parties->map(fn($p) => $p->user)->filter();
            if ($users->count() > 0) {
                \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\DocumentNotification(
                    'Dokumen Kedaluwarsa',
                    "Dokumen \"{$doc->title}\" telah melewati masa berlaku.",
                    'fa-file-circle-xmark',
                    url("/documents/editor/{$doc->id}")
                ));
            }
            
            $this->info("Document #{$doc->id} marked as expired.");
        }

        // 2. Check for expiring soon (Exactly 30 days from now)
        $expiringSoon = \App\Models\Document::where('status', 'signed')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '=', $today->copy()->addDays(30)->toDateString())
            ->get();

        foreach ($expiringSoon as $doc) {
            $users = $doc->parties->map(fn($p) => $p->user)->filter();
            if ($users->count() > 0) {
                \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\DocumentNotification(
                    'Masa Tenggang Dokumen',
                    "Dokumen \"{$doc->title}\" akan segera berakhir dalam 30 hari.",
                    'fa-triangle-exclamation',
                    url("/documents/editor/{$doc->id}")
                ));
            }
            
            $this->info("Notification sent for expiring document #{$doc->id}.");
        }

        $this->info('Expiry check completed.');
    }
}
