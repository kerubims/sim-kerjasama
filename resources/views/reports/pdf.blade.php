<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Dokumen Kerjasama</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; table-layout: fixed; word-wrap: break-word; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; word-wrap: break-word; overflow-wrap: break-word; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2 class="text-center">Laporan Dokumen Kerjasama</h2>
    <p class="text-center mb-2">Tanggal Cetak: {{ now()->format('d M Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="18%">Judul</th>
                <th width="7%">Jenis</th>
                <th width="12%">Nomor Dokumen</th>
                <th width="12%">Mitra</th>
                <th width="12%">Nama PIC</th>
                <th width="12%">Unit</th>
                <th width="8%">Status</th>
                <th width="8%">Tgl Mulai</th>
                <th width="8%">Tgl Selesai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $idx => $doc)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>
                    {{ $doc->title }}
                    @if($doc->parent)
                        <br><small style="color:#666;">Ref: {{ $doc->parent->title }}</small>
                    @endif
                </td>
                <td class="text-center">{{ strtoupper($doc->type) }}</td>
                <td>{{ $doc->document_number ?? '-' }}</td>
                <td>{{ $doc->parties->where('role_type', 'client')->first()?->user->nama_mitra ?? '-' }}</td>
                <td>{{ $doc->parties->where('role_type', 'unit_pengusul')->first()?->user->name ?? '-' }}</td>
                <td>{{ $doc->parties->where('role_type', 'unit_pengusul')->first()?->user->jabatan ?? '-' }}</td>
                <td class="text-center">{{ strtoupper(str_replace('_', ' ', $doc->status)) }}</td>
                <td>{{ $doc->start_date ? $doc->start_date->format('d M Y') : '-' }}</td>
                <td>{{ $doc->end_date ? $doc->end_date->format('d M Y') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
