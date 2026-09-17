import React, { useEffect, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import { EditorState } from 'prosemirror-state';
import { EditorView } from 'prosemirror-view';
import { keymap } from 'prosemirror-keymap';
import { baseKeymap, toggleMark } from 'prosemirror-commands';
import { history, undo, redo } from 'prosemirror-history';
import { docxSchema } from '../editor/schema';
import { astToProseMirrorDoc, proseMirrorDocToAst, AstData } from '../editor/astConverter';
import Swal from 'sweetalert2';

interface DocxEditorProps {
  document: {
    id: number;
    title: string;
    status: string;
    doc_number: string;
    type: string;
    file_path: string | null;
  };
  canEdit: boolean;
  initialAst: AstData;
}

export default function DocxEditor({ document: doc, canEdit, initialAst }: DocxEditorProps) {
  const editorRef = useRef<HTMLDivElement>(null);
  const viewRef = useRef<EditorView | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [saving, setSaving] = useState(false);
  const [progress, setProgress] = useState<number | null>(null);
  const [statusText, setStatusText] = useState<string>('');

  useEffect(() => {
    if (!editorRef.current) return;

    const pmDoc = astToProseMirrorDoc(initialAst, docxSchema);

    const state = EditorState.create({
      doc: pmDoc,
      plugins: [
        history(),
        keymap({
          'Mod-z': undo,
          'Mod-y': redo,
          'Mod-b': toggleMark(docxSchema.marks.bold),
          'Mod-i': toggleMark(docxSchema.marks.italic),
          'Mod-u': toggleMark(docxSchema.marks.underline),
        }),
        keymap(baseKeymap),
      ],
    });

    const view = new EditorView(editorRef.current, {
      state,
      editable: () => canEdit,
    });

    viewRef.current = view;

    return () => {
      view.destroy();
    };
  }, []);

  const applyMark = (markType: string, attrs?: Record<string, any>) => {
    if (!viewRef.current) return;
    const mark = docxSchema.marks[markType];
    if (mark) {
      toggleMark(mark, attrs)(viewRef.current.state, viewRef.current.dispatch);
    }
  };

  const handleSave = async () => {
    if (!viewRef.current) return;
    setSaving(true);
    const ast = proseMirrorDocToAst(viewRef.current.state.doc);

    try {
      const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
      const res = await fetch(`/documents/${doc.id}/ast`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ ast }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Tersimpan 1:1!',
          text: 'Struktur OpenXML DOCX berhasil diperbarui.',
          timer: 1800,
          showConfirmButton: false,
        });
      } else {
        Swal.fire('Gagal Menyimpan', data.message || 'Terjadi kesalahan.', 'error');
      }
    } catch (err) {
      Swal.fire('Error', 'Gagal koneksi ke server.', 'error');
    } finally {
      setSaving(false);
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('docx_file', file);
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;

    setProgress(0);
    setStatusText('Mengunggah berkas DOCX...');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', `/documents/${doc.id}/upload-docx`, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

    xhr.upload.onprogress = (evt) => {
      if (evt.lengthComputable) {
        const pct = Math.round((evt.loaded / evt.total) * 100);
        setProgress(pct);
        if (pct >= 100) setStatusText('Parsing struktur OpenXML 1:1...');
      }
    };

    xhr.onload = () => {
      setProgress(null);
      try {
        const data = JSON.parse(xhr.responseText);
        if (xhr.status === 200 && data.success && data.ast) {
          if (viewRef.current) {
            const newDoc = astToProseMirrorDoc(data.ast, docxSchema);
            const newState = EditorState.create({
              doc: newDoc,
              plugins: viewRef.current.state.plugins,
            });
            viewRef.current.updateState(newState);
          }
          Swal.fire({
            icon: 'success',
            title: 'Import AST 1:1 Berhasil!',
            text: 'Dokumen Word berhasil dimuat tanpa distorsi HTML.',
            timer: 2000,
            showConfirmButton: false,
          });
        } else {
          Swal.fire('Gagal Import', data.message || 'Gagal memproses file.', 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'Format respon server tidak valid.', 'error');
      }
    };

    xhr.onerror = () => {
      setProgress(null);
      Swal.fire('Error', 'Kesalahan jaringan saat pengunggah berkas.', 'error');
    };

    xhr.send(formData);
  };

  return (
    <>
      <Head title={`Editor OpenXML - ${doc.title}`} />

      {/* Progress Bar Modal */}
      {progress !== null && (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs">
          <div className="w-full max-w-md bg-white rounded-xl p-6 shadow-2xl text-center font-sans">
            <h3 className="text-lg font-bold text-slate-800 mb-2">Proses Import OpenXML AST</h3>
            <p className="text-sm font-medium text-slate-600 mb-4">{statusText}</p>
            <div className="w-full bg-slate-200 rounded-full h-3.5 overflow-hidden mb-2">
              <div
                className="bg-blue-600 h-full rounded-full transition-all duration-200"
                style={{ width: `${progress}%` }}
              ></div>
            </div>
            <span className="text-xs font-semibold text-slate-500">{progress}% selesai</span>
          </div>
        </div>
      )}

      <div className="min-h-screen bg-slate-100 flex flex-col font-sans">
        {/* Top Navigation Bar */}
        <header className="bg-white border-b border-slate-200 px-6 py-3.5 flex items-center justify-between sticky top-0 z-40 shadow-xs">
          <div className="flex items-center gap-4">
            <a href="/documents" className="text-slate-500 hover:text-slate-800 transition">
              <i className="fa-solid fa-arrow-left text-lg"></i>
            </a>
            <div>
              <h1 className="text-lg font-bold text-slate-800 leading-tight">{doc.title}</h1>
              <span className="text-xs text-slate-500 font-medium">Doc No: {doc.doc_number || '-'} | Status: {doc.status}</span>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <input
              type="file"
              ref={fileInputRef}
              className="hidden"
              accept=".docx"
              onChange={handleFileUpload}
            />

            {canEdit && (
              <>
                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  className="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-md text-sm font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                  <i className="fa-solid fa-file-word"></i> Import DOCX
                </button>

                <button
                  type="button"
                  onClick={handleSave}
                  disabled={saving}
                  className="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-md text-sm font-semibold shadow-xs transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                >
                  <i className="fa-regular fa-floppy-disk"></i> {saving ? 'Menyimpan...' : 'Simpan 1:1'}
                </button>
              </>
            )}

            <a
              href={`/documents/${doc.id}/download-docx`}
              target="_blank"
              className="bg-slate-700 hover:bg-slate-800 text-white px-3.5 py-1.5 rounded-md text-sm font-semibold shadow-xs transition flex items-center gap-1.5"
            >
              <i className="fa-solid fa-download"></i> Download DOCX
            </a>

            <a
              href={`/documents/${doc.id}/download-pdf`}
              target="_blank"
              className="bg-red-700 hover:bg-red-800 text-white px-3.5 py-1.5 rounded-md text-sm font-semibold shadow-xs transition flex items-center gap-1.5"
            >
              <i className="fa-solid fa-file-pdf"></i> Download PDF (A4)
            </a>
          </div>
        </header>

        {/* Toolbar */}
        <div className="bg-slate-50 border-b border-slate-200 px-6 py-2 flex items-center gap-2 overflow-x-auto">
          <button
            type="button"
            onClick={() => applyMark('bold')}
            className="p-2 hover:bg-slate-200 rounded text-slate-700 font-bold w-9 h-9 flex items-center justify-center cursor-pointer"
            title="Bold (Ctrl+B)"
          >
            B
          </button>
          <button
            type="button"
            onClick={() => applyMark('italic')}
            className="p-2 hover:bg-slate-200 rounded text-slate-700 italic w-9 h-9 flex items-center justify-center cursor-pointer"
            title="Italic (Ctrl+I)"
          >
            I
          </button>
          <button
            type="button"
            onClick={() => applyMark('underline')}
            className="p-2 hover:bg-slate-200 rounded text-slate-700 underline w-9 h-9 flex items-center justify-center cursor-pointer"
            title="Underline (Ctrl+U)"
          >
            U
          </button>

          <div className="h-5 w-[1px] bg-slate-300 mx-1"></div>

          <button
            type="button"
            onClick={() => applyMark('fontFamily', { family: 'Times New Roman' })}
            className="px-2 py-1 text-xs hover:bg-slate-200 rounded text-slate-700 font-serif cursor-pointer"
          >
            Times New Roman
          </button>
          <button
            type="button"
            onClick={() => applyMark('fontFamily', { family: 'Calibri' })}
            className="px-2 py-1 text-xs hover:bg-slate-200 rounded text-slate-700 font-sans cursor-pointer"
          >
            Calibri
          </button>

          <div className="h-5 w-[1px] bg-slate-300 mx-1"></div>

          <button
            type="button"
            onClick={() => applyMark('fontSize', { size: 12 })}
            className="px-2 py-1 text-xs hover:bg-slate-200 rounded text-slate-700 cursor-pointer"
          >
            12pt
          </button>
          <button
            type="button"
            onClick={() => applyMark('fontSize', { size: 14 })}
            className="px-2 py-1 text-xs hover:bg-slate-200 rounded text-slate-700 cursor-pointer"
          >
            14pt
          </button>
          <button
            type="button"
            onClick={() => applyMark('fontSize', { size: 16 })}
            className="px-2 py-1 text-xs hover:bg-slate-200 rounded text-slate-700 cursor-pointer"
          >
            16pt
          </button>
        </div>

        {/* Main Canvas Area */}
        <main className="flex-1 p-8 flex justify-center overflow-y-auto">
          <div
            className="bg-white shadow-xl border border-slate-300 rounded-sm"
            style={{
              width: '21cm',
              minHeight: '29.7cm',
              padding: '2.5cm 2cm',
              boxSizing: 'border-box',
            }}
          >
            <div ref={editorRef} className="prose prose-slate max-w-none focus:outline-none min-h-[22cm]"></div>
          </div>
        </main>
      </div>
    </>
  );
}
