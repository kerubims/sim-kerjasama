<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Document;
use App\Models\DocumentParty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitNames = ['Surya Prasetyo', 'Budi Santoso', 'Siti Aminah', 'Rina Wulandari', 'Ahmad Rizal'];
        $clientNames = ['Gunawan Firmansyah', 'Hendra Wijaya', 'Dewi Lestari', 'Iwan Setiawan', 'Maya Putri'];
        $companyNames = ['PT Maju Bersama', 'CV Karya Mandiri', 'PT Solusi Teknologi', 'PT Bina Nusantara', 'CV Sejahtera Abadi'];

        // 1. Membuat 10 User (5 Unit Pengusul, 5 Klien)
        $units = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::create([
                'name' => $unitNames[$i],
                'email' => "unit" . ($i + 1) . "@test.com",
                'password' => Hash::make('password'),
                'jabatan' => "Ketua Departemen " . ($i + 1),
            ]);
            $user->assignRole('unit_pengusul');
            $units[] = $user;
        }

        $clients = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::create([
                'name' => $clientNames[$i],
                'email' => "client" . ($i + 1) . "@test.com",
                'password' => Hash::make('password'),
                'jabatan' => "Direktur Utama",
                'nama_mitra' => $companyNames[$i]
            ]);
            $user->assignRole('client');
            $clients[] = $user;
        }

        // 2. Membuat 10 Dokumen Kerjasama dengan Kondisi Beragam
        $types = ['mou', 'moa', 'ia'];
        $statuses = ['draft', 'review_unit', 'review_client', 'signed', 'expired'];

        for ($i = 1; $i <= 10; $i++) {
            $type = $types[array_rand($types)];
            
            // Agar distribusi status lebih seimbang dan masuk akal
            $status = $statuses[array_rand($statuses)];
            
            // Penentuan tanggal yang logis sesuai status
            $startDate = null;
            $endDate = null;
            
            if ($status === 'expired') {
                $startDate = Carbon::now()->subYears(2);
                $endDate = Carbon::now()->subDays(5); // Sudah lewat
            } elseif ($status === 'signed') {
                $startDate = Carbon::now()->subDays(10);
                $endDate = Carbon::now()->addYears(3); // Masih aktif
            }

            // Buat dokumen
            $doc = Document::create([
                'title' => "Dokumen Kerjasama $type - Proyek Inovasi 0$i",
                'type' => $type,
                'document_number' => "DOC/" . strtoupper($type) . "/2026/00" . $i,
                'content' => "<p>Ini adalah rancangan draf dokumen kerjasama simulasi.</p>",
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_by' => $units[array_rand($units)]->id, 
            ]);

            // Pasangkan 1 Unit Pengusul ke Dokumen
            DocumentParty::create([
                'document_id' => $doc->id,
                'user_id' => $units[array_rand($units)]->id,
                'role_type' => 'unit_pengusul'
            ]);

            // Pasangkan 1 Klien/Mitra ke Dokumen
            DocumentParty::create([
                'document_id' => $doc->id,
                'user_id' => $clients[array_rand($clients)]->id,
                'role_type' => 'client'
            ]);
        }
    }
}
