<!DOCTYPE html>
<html>
<head>
    <title>Laporan Dokumen Kerjasama</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        h2 { text-align: center; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Laporan Dokumen Kerjasama</h2>
    <p class="text-center">Tanggal Cetak: {{ now()->format('d M Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Judul</th>
                <th>Jenis</th>
                <th>Nomor Dokumen</th>
                <th>Mitra</th>
                <th>Jabatan PIC</th>
                <th>Nama PIC</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Tgl Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $idx => $doc)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>
                    {{ $doc->title }}
                    @if($doc->parent)
                        <br><small>Ref: {{ $doc->parent->title }}</small>
                    @endif
                </td>
                <td>{{ strtoupper($doc->type) }}</td>
                <td>{{ $doc->document_number ?? '-' }}</td>
                <td>{{ $doc->parties->where('role_type', 'client')->first()->user->name ?? '-' }}</td>
                <td>{{ $doc->parties->where('role_type', 'client')->first()->user->jabatan ?? '-' }}</td>
                <td>{{ $doc->parties->where('role_type', 'client')->first()->user->nama_mitra ?? '-' }}</td>
                <td>{{ $doc->parties->where('role_type', 'unit_pengusul')->first()->user->name ?? '-' }}</td>
                <td>{{ strtoupper(str_replace('_', ' ', $doc->status)) }}</td>
                <td>{{ $doc->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
