<?php

namespace App\Exports;

use App\Models\Document;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Document::with(['parties.user', 'parent']);
        
        if (!empty($this->filters['start_date'])) $query->whereDate('created_at', '>=', $this->filters['start_date']);
        if (!empty($this->filters['end_date'])) $query->whereDate('created_at', '<=', $this->filters['end_date']);
        if (!empty($this->filters['type'])) $query->where('type', strtolower($this->filters['type']));
        if (!empty($this->filters['status'])) $query->where('status', strtolower($this->filters['status']));

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Judul',
            'Jenis',
            'Nomor Dokumen',
            'Rujukan',
            'Mitra',
            'Jabatan PIC',
            'Nama PIC',
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
            $client ? $client->user->jabatan : '-',
            $client ? $client->user->nama_mitra : '-',
            $unit ? $unit->user->name : '-',
            $doc->created_at->format('d M Y'),
            $doc->end_date ? \Carbon\Carbon::parse($doc->end_date)->format('d M Y') : '-',
            strtoupper(str_replace('_', ' ', $doc->status)),
        ];
    }
}
