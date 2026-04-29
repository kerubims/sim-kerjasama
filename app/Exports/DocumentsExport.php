<?php

namespace App\Exports;

use App\Models\Document;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Document::with(['parties.user', 'parent'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Judul',
            'Jenis',
            'Nomor Dokumen',
            'Rujukan',
            'Client',
            'Unit Pengusul',
            'Tanggal Dibuat',
            'Tanggal Selesai',
            'Status',
        ];
    }

    public function map($doc): array
    {
        $client = $doc->parties->where('role_type', 'client')->first();
        $unit = $doc->parties->where('role_type', 'unit_pengusul')->first();

        return [
            $doc->id,
            $doc->title,
            strtoupper($doc->type),
            $doc->document_number ?? '-',
            $doc->parent ? $doc->parent->title : '-',
            $client ? $client->user->name : '-',
            $unit ? $unit->user->name : '-',
            $doc->created_at->format('d M Y'),
            $doc->end_date ? \Carbon\Carbon::parse($doc->end_date)->format('d M Y') : '-',
            strtoupper(str_replace('_', ' ', $doc->status)),
        ];
    }
}
