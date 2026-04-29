<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Document;
use App\Models\DocumentParty;
use App\Models\DocumentHistory;
use Carbon\Carbon;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@univ.ac.id')->first();
        $unit = User::where('email', 'unit_ti@univ.ac.id')->first();
        $client = User::where('email', 'pt_tech@mitra.com')->first();

        if (!$admin || !$unit || !$client) return;

        // 1. Create MoU
        $mou = Document::create([
            'type' => 'mou',
            'document_number' => '023/MOU/TI/2023',
            'title' => 'Kerjasama Penelitian AI',
            'content' => '<h1 style="text-align:center;">MEMORANDUM OF UNDERSTANDING</h1><p style="text-align:center;">Nomor: 023/MOU/TI/2023</p><br><p>Pada hari ini, Senin tanggal 15 Januari 2024, yang bertanda tangan di bawah ini:</p><br><p><strong>PIHAK PERTAMA</strong></p><p>Nama: Prof. Dr. Ahmad Sutanto, M.Sc.</p><p>Jabatan: Rektor Universitas</p><br><p><strong>PIHAK KEDUA</strong></p><p>Nama: Ir. Budi Santoso, M.T.</p><p>Jabatan: Direktur PT Teknologi Maju</p>',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addYears(2),
            'status' => 'signed',
            'created_by' => $admin->id,
        ]);

        DocumentParty::create(['document_id' => $mou->id, 'user_id' => $client->id, 'role_type' => 'client', 'signed_at' => Carbon::now()->subMonths(3)]);
        DocumentParty::create(['document_id' => $mou->id, 'user_id' => $unit->id, 'role_type' => 'unit_pengusul', 'signed_at' => Carbon::now()->subMonths(3)]);

        DocumentHistory::create(['document_id' => $mou->id, 'user_id' => $admin->id, 'action' => 'created', 'message' => 'Membuat draft MoU']);
        DocumentHistory::create(['document_id' => $mou->id, 'user_id' => $admin->id, 'action' => 'signed', 'message' => 'Dokumen ditandatangani oleh semua pihak']);

        // 2. Create MoA (Child of MoU)
        $moa = Document::create([
            'parent_id' => $mou->id,
            'type' => 'moa',
            'document_number' => '045/MOA/TI/2024',
            'title' => 'Implementasi Sistem Cerdas',
            'content' => '<p>Detail implementasi...</p>',
            'start_date' => Carbon::now()->subMonths(1),
            'end_date' => Carbon::now()->addMonths(11),
            'status' => 'review_client',
            'created_by' => $admin->id,
        ]);

        DocumentParty::create(['document_id' => $moa->id, 'user_id' => $client->id, 'role_type' => 'client']);
        DocumentParty::create(['document_id' => $moa->id, 'user_id' => $unit->id, 'role_type' => 'unit_pengusul']);
        
        DocumentHistory::create(['document_id' => $moa->id, 'user_id' => $admin->id, 'action' => 'created', 'message' => 'Membuat draft MoA berdasarkan MoU']);
        DocumentHistory::create(['document_id' => $moa->id, 'user_id' => $admin->id, 'action' => 'status_changed', 'message' => 'Mengirim ke Client untuk direview']);

        // 3. Create Draft MoU
        $draft = Document::create([
            'type' => 'mou',
            'document_number' => null,
            'title' => 'Program Magang Industri',
            'content' => '<p>Draft program magang mahasiswa di industri...</p>',
            'start_date' => null,
            'end_date' => null,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        
        DocumentParty::create(['document_id' => $draft->id, 'user_id' => $unit->id, 'role_type' => 'unit_pengusul']);
        DocumentHistory::create(['document_id' => $draft->id, 'user_id' => $admin->id, 'action' => 'created', 'message' => 'Membuat draft baru']);
    }
}
