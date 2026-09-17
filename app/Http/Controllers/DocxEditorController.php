<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
import_gotenberg:
use Gotenberg\Gotenberg;
use Gotenberg\Stream;

class DocxEditorController extends Controller
{
    public function editor($id)
    {
        $document = Document::with(['parties.user'])->findOrFail($id);
        $user = auth()->user();

        $canEdit = ($user->hasRole('super_admin') && $document->status !== 'signed') ||
                   ($user->hasRole('client') && $document->status === 'review_client') ||
                   ($user->hasRole('unit_pengusul') && $document->status === 'review_unit');

        $astData = $this->getOrGenerateAst($document);

        return Inertia::render('DocxEditor', [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'status' => $document->status,
                'doc_number' => $document->doc_number,
                'type' => $document->type,
                'file_path' => $document->file_path,
            ],
            'canEdit' => $canEdit,
            'initialAst' => $astData,
        ]);
    }

    public function saveAst(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $astData = $request->input('ast');

        if (!$astData) {
            return response()->json(['success' => false, 'message' => 'Data AST kosong.'], 400);
        }

        $tempAst = storage_path('app/ast_' . uniqid() . '.json');
        $docxPath = storage_path('app/documents/doc_' . $document->id . '.docx');
        if (!file_exists(dirname($docxPath))) {
            mkdir(dirname($docxPath), 0755, true);
        }

        file_put_contents($tempAst, json_encode($astData));

        $pythonBin = '/home/ubs/.hermes/hermes-agent/venv/bin/python3';
        $scriptPath = base_path('app/Services/DocxAST.py');

        $command = escapeshellcmd("$pythonBin $scriptPath build " . escapeshellarg($tempAst) . ' ' . escapeshellarg($docxPath));
        shell_exec($command);

        if (file_exists($tempAst)) {
            @unlink($tempAst);
        }

        if (file_exists($docxPath)) {
            $document->file_path = 'documents/doc_' . $document->id . '.docx';
            $document->save();

            return response()->json(['success' => true, 'message' => 'Dokumen DOCX berhasil disimpan 1:1.']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal membuat file DOCX.'], 500);
    }

    public function uploadDocx(Request $request, $id)
    {
        $request->validate([
            'docx_file' => 'required|file|max:20480',
        ]);

        $document = Document::findOrFail($id);
        $file = $request->file('docx_file');
        $tempPath = storage_path('app/upload_' . uniqid() . '.docx');
        $file->move(dirname($tempPath), basename($tempPath));

        // Also save as current docx file
        $docxPath = storage_path('app/documents/doc_' . $document->id . '.docx');
        if (!file_exists(dirname($docxPath))) {
            mkdir(dirname($docxPath), 0755, true);
        }
        copy($tempPath, $docxPath);
        $document->file_path = 'documents/doc_' . $document->id . '.docx';
        $document->save();

        $pythonBin = '/home/ubs/.hermes/hermes-agent/venv/bin/python3';
        $scriptPath = base_path('app/Services/DocxAST.py');

        $command = escapeshellcmd("$pythonBin $scriptPath parse " . escapeshellarg($tempPath));
        $output = shell_exec($command);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        if ($output) {
            $astData = json_decode($output, true);
            if ($astData) {
                return response()->json([
                    'success' => true,
                    'ast' => $astData
                ]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Gagal memparsing struktur DOCX.'], 500);
    }

    public function downloadDocx($id)
    {
        $document = Document::findOrFail($id);
        $docxPath = storage_path('app/documents/doc_' . $document->id . '.docx');

        if (!file_exists($docxPath)) {
            $astData = $this->getOrGenerateAst($document);
            $tempAst = storage_path('app/ast_' . uniqid() . '.json');
            file_put_contents($tempAst, json_encode($astData));

            $pythonBin = '/home/ubs/.hermes/hermes-agent/venv/bin/python3';
            $scriptPath = base_path('app/Services/DocxAST.py');
            shell_exec(escapeshellcmd("$pythonBin $scriptPath build " . escapeshellarg($tempAst) . ' ' . escapeshellarg($docxPath)));
            @unlink($tempAst);
        }

        if (file_exists($docxPath)) {
            return response()->download($docxPath, Str::slug($document->title) . '.docx');
        }

        abort(404, 'Dokumen DOCX tidak ditemukan.');
    }

    public function downloadPdf($id)
    {
        $document = Document::findOrFail($id);
        $docxPath = storage_path('app/documents/doc_' . $document->id . '.docx');

        if (!file_exists($docxPath)) {
            $astData = $this->getOrGenerateAst($document);
            $tempAst = storage_path('app/ast_' . uniqid() . '.json');
            file_put_contents($tempAst, json_encode($astData));

            $pythonBin = '/home/ubs/.hermes/hermes-agent/venv/bin/python3';
            $scriptPath = base_path('app/Services/DocxAST.py');
            shell_exec(escapeshellcmd("$pythonBin $scriptPath build " . escapeshellarg($tempAst) . ' ' . escapeshellarg($docxPath)));
            @unlink($tempAst);
        }

        $apiUrl = config('services.gotenberg.url');
        if ($apiUrl && file_exists($docxPath)) {
            try {
                $gotenbergRequest = Gotenberg::libreOffice($apiUrl)
                    ->convert(Stream::path($docxPath));
                $response = Gotenberg::send($gotenbergRequest);
                $pdfContent = $response->getBody()->getContents();

                return response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . Str::slug($document->title) . '.pdf"',
                ]);
            } catch (\Exception $e) {
                // Fallback if Gotenberg is unavailable
            }
        }

        abort(500, 'Gotenberg PDF service tidak tersedia.');
    }

    private function getOrGenerateAst(Document $document)
    {
        $docxPath = storage_path('app/documents/doc_' . $document->id . '.docx');
        $pythonBin = '/home/ubs/.hermes/hermes-agent/venv/bin/python3';
        $scriptPath = base_path('app/Services/DocxAST.py');

        if (file_exists($docxPath)) {
            $output = shell_exec(escapeshellcmd("$pythonBin $scriptPath parse " . escapeshellarg($docxPath)));
            if ($output) {
                $parsed = json_decode($output, true);
                if ($parsed) return $parsed;
            }
        }

        return [
            "version" => "1.0",
            "sections" => [],
            "body" => [
                [
                    "type" => "paragraph",
                    "alignment" => "center",
                    "style" => "Heading1",
                    "runs" => [
                        [
                            "type" => "run",
                            "text" => strtoupper($document->title),
                            "bold" => true,
                            "italic" => false,
                            "fontFamily" => "Times New Roman",
                            "fontSize" => 16.0
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "alignment" => "justify",
                    "style" => "Normal",
                    "runs" => [
                        [
                            "type" => "run",
                            "text" => "Dokumen kerjasama ini dibuat dan disepakati oleh para pihak.",
                            "bold" => false,
                            "italic" => false,
                            "fontFamily" => "Times New Roman",
                            "fontSize" => 12.0
                        ]
                    ]
                ]
            ]
        ];
    }
}
