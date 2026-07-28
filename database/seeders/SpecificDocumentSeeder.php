<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Document;
use App\Models\DocumentParty;
use App\Models\DocumentHistory;
use Carbon\Carbon;

class SpecificDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@univ.ac.id')->first();
        $unit = User::role('unit_pengusul')->first();
        
        $adminId = $admin ? $admin->id : User::first()->id;
        $unitId = $unit ? $unit->id : User::first()->id;

        $documents = [
            // MoA
            [
                'type' => 'moa',
                'title' => 'Pelaksanaan Tridharma perguruan tinggi',
            ],
            // MoU
            [
                'type' => 'mou',
                'title' => 'Penguatan penyelenggaraan pendidikan, Penelitian dan pengabdian kepada masyarakat serta pemberdayaan sumber daya manusia',
            ],
            [
                'type' => 'mou',
                'title' => 'Kerjasama Tridharma perguruan tinggi bidang pendidikan, penelitian dan pengabdian masyarakat serta pengembangan sumber daya manusia',
            ],
            [
                'type' => 'mou',
                'title' => 'Pendidikan, penelitian, dan pengabdian kepada masyarakat',
            ],
            [
                'type' => 'mou',
                'title' => 'Kerjasama Program dan kegiatan berkaitan dengan hak dan masa depan anak di kota malang',
            ],
            // IA
            [
                'type' => 'ia',
                'title' => 'Penugasan Dewan Juri dosen dalam lomba essay nasional',
            ],
            [
                'type' => 'ia',
                'title' => 'Pelaksanaan program magang kerja mahasiswa',
            ],
            [
                'type' => 'ia',
                'title' => 'Pelaksanaan penelitian dan pengembangan tugas akhir mahasiswa',
            ],
            [
                'type' => 'ia',
                'title' => 'Desain web lokapasar intelejen dengan sistem rekomendasi untuk meningkatkan visibilitas produk mbois mart malang',
            ],
            [
                'type' => 'ia',
                'title' => 'Kegiatan kuliah tamu',
            ],
        ];

        foreach ($documents as $doc) {
            $createdDoc = Document::create([
                'type' => $doc['type'],
                'document_number' => null,
                'title' => $doc['title'],
                'content' => '<p>' . $doc['title'] . '</p>',
                'start_date' => null,
                'end_date' => null,
                'status' => 'draft',
                'created_by' => $adminId,
            ]);
            
            DocumentParty::create(['document_id' => $createdDoc->id, 'user_id' => $unitId, 'role_type' => 'unit_pengusul']);
            DocumentHistory::create(['document_id' => $createdDoc->id, 'user_id' => $adminId, 'action' => 'created', 'message' => 'Membuat draft baru']);
        }
    }
}
