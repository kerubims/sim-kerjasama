<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gotenberg PDF Viewer Test</title>
    <style>
        body, html { 
            margin: 0; 
            padding: 0; 
            height: 100%; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            flex-direction: column;
            background-color: #f3f4f6;
        }
        .header {
            background: white;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header input[type="file"] {
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 14px;
        }
        .header button {
            background-color: #059669;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .header button:hover {
            background-color: #047857;
        }
        .info {
            background: #ecfdf5;
            color: #065f46;
            padding: 10px 20px;
            font-size: 13px;
            border-bottom: 1px solid #a7f3d0;
        }
        .error {
            background: #fef2f2;
            color: #991b1b;
            padding: 10px 20px;
            font-size: 13px;
            border-bottom: 1px solid #fecaca;
        }
        .iframe-container {
            flex: 1;
            width: 100%;
        }
        iframe { 
            width: 100%; 
            height: 100%; 
            border: none; 
            display: block;
        }
        .empty-state {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            flex-direction: column;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <form action="{{ route('test-viewer.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label style="font-size: 14px; font-weight: 600; color: #374151;">Uji Preview (DOCX ke PDF via Gotenberg):</label>
            <input type="file" name="docx_file" accept=".docx" required>
            <button type="submit">Konversi & Lihat</button>
        </form>
        @if($docUrl)
            <a href="{{ route('test-viewer') }}" style="font-size: 13px; color: #6b7280; text-decoration: none;">&times; Reset</a>
        @endif
    </div>
    
    @if($errors->any())
    <div class="error">
        <strong>Error:</strong> {{ $errors->first() }}
    </div>
    @endif

    @if($docUrl)
    <div class="info">
        <strong>File Berhasil Dikonversi ke PDF.</strong> Menampilkan preview di bawah ini.
    </div>
    <div class="iframe-container">
        <iframe src="{{ $docUrl }}" frameborder="0"></iframe>
    </div>
    @else
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p>Silakan upload file DOCX untuk melihat hasil konversi Gotenberg.</p>
    </div>
    @endif
</body>
</html>
