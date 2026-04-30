<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Viewer Test</title>
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
            background-color: #2563eb;
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
            background-color: #1d4ed8;
        }
        .info {
            background: #eff6ff;
            color: #1e40af;
            padding: 10px 20px;
            font-size: 13px;
            border-bottom: 1px solid #bfdbfe;
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
    </style>
</head>
<body>
    @php
        $docUrl = session('viewer_url') ?? "https://calibre-ebook.com/downloads/demos/demo.docx";
    @endphp

    <div class="header">
        <form action="{{ route('test-viewer.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label style="font-size: 14px; font-weight: 600; color: #374151;">Upload DOCX Anda:</label>
            <input type="file" name="docx_file" accept=".docx" required>
            <button type="submit">Upload & Lihat</button>
        </form>
        @if(session('viewer_url'))
            <a href="{{ route('test-viewer') }}" style="font-size: 13px; color: #6b7280; text-decoration: none;">&times; Reset ke file sampel</a>
        @endif
    </div>
    
    @if(session('viewer_url'))
    <div class="info">
        <strong>URL Anda (dikirim ke Microsoft):</strong> <a href="{{ session('viewer_url') }}" target="_blank" style="color: #2563eb;">{{ session('viewer_url') }}</a>
    </div>
    @endif

    <div class="iframe-container">
        <!-- Microsoft Office Online Viewer -->
        <iframe src="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode($docUrl) }}" frameborder="0">
            Browser Anda tidak mendukung iframe.
        </iframe>
    </div>
</body>
</html>
