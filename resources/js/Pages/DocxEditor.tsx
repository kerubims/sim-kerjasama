import React, { useEffect, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import { renderAsync } from 'docx-preview';
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
}

export default function DocxEditor({ document: doc, canEdit }: DocxEditorProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [progress, setProgress] = useState<number | null>(null);

  // Load and render DOCX binary file natively using docx-preview engine
  const loadDocx = async () => {
    setLoading(true);
    try {
      const res = await fetch(`/documents/${doc.id}/download-docx`);
      if (!res.ok) throw new Error('Dokumen belum dibuat.');
      const blob = await res.blob();
      const arrayBuffer = await blob.arrayBuffer();

      if (containerRef.current) {
        containerRef.current.innerHTML = '';
        await renderAsync(arrayBuffer, containerRef.current, undefined, {
          inWrapper: true,
          ignoreWidth: false,
          ignoreHeight: false,
          ignoreFonts: false,
          breakPages: true,
          experimental: true,
          useBase64URL: true,
        });

        // Make content editable if user has permission
        if (canEdit) {
          const wrapper = containerRef.current.querySelector('.docx-wrapper');
          if (wrapper) {
            (wrapper as HTMLElement).contentEditable = 'true';
            (wrapper as HTMLElement).style.outline = 'none';
          }
        }
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDocx();
  }, [doc.id]);

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('docx_file', file);
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;

    setProgress(0);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', `/documents/${doc.id}/upload-docx`, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

    xhr.upload.onprogress = (evt) => {
      if (evt.lengthComputable) {
        setProgress(Math.round((evt.loaded / evt.total) * 100));
      }
    };

    xhr.onload = () => {
      setProgress(null);
      if (xhr.status === 200) {
        Swal.fire({
          icon: 'success',
          title: 'Import 1:1 Berhasil!',
          text: 'File Word dirender sempurna oleh Canvas Engine.',
          timer: 1800,
          showConfirmButton: false,
        });
        loadDocx();
      } else {
        Swal.fire('Gagal Import', 'Gagal memproses berkas DOCX.', 'error');
      }
    };

    xhr.onerror = () => {
      setProgress(null);
      Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
    };

    xhr.send(formData);
  };

  return (
    <>
      <Head title={`Editor Canvas 1:1 - ${doc.title}`} />

      {/* Progress Overlay */}
      {progress !== null && (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs">
          <div className="w-full max-w-md bg-white rounded-xl p-6 shadow-2xl text-center font-sans">
            <h3 className="text-lg font-bold text-slate-800 mb-2">Memuat Canvas Engine 1:1</h3>
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

      <div className="min-h-screen bg-slate-200 flex flex-col font-sans">
        {/* Top Header */}
        <header className="bg-white border-b border-slate-300 px-6 py-3 flex items-center justify-between sticky top-0 z-50 shadow-sm">
          <div className="flex items-center gap-4">
            <a href="/documents" className="text-slate-500 hover:text-slate-800 transition">
              <i className="fa-solid fa-arrow-left text-lg"></i>
            </a>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-lg font-bold text-slate-800 leading-tight">{doc.title}</h1>
                <span className="bg-blue-100 text-blue-800 text-[11px] font-bold px-2 py-0.5 rounded">
                  CANVAS ENGINE 1:1
                </span>
              </div>
              <span className="text-xs text-slate-500 font-medium">
                Doc No: {doc.doc_number || '-'} | Status: {doc.status}
              </span>
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
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                className="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-md text-sm font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
              >
                <i className="fa-solid fa-file-word"></i> Import DOCX
              </button>
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

        {/* Canvas Render Body */}
        <main className="flex-1 p-8 flex justify-center overflow-y-auto relative">
          {loading && (
            <div className="absolute inset-0 bg-white/70 flex items-center justify-center z-10">
              <div className="flex items-center gap-3 bg-white px-5 py-3 rounded-lg shadow-lg border border-slate-200">
                <i className="fa-solid fa-circle-notch fa-spin text-blue-600 text-xl"></i>
                <span className="text-sm font-medium text-slate-700">Rendering dokumen 1:1...</span>
              </div>
            </div>
          )}

          <div
            ref={containerRef}
            className="docx-viewer-container w-full max-w-[850px] shadow-2xl rounded-sm"
          ></div>
        </main>
      </div>

      <style>{`
        .docx-viewer-container .docx-wrapper {
          background: #e2e8f0 !important;
          padding: 30px 0 !important;
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: 20px;
        }
        .docx-viewer-container .docx-wrapper > section.docx {
          background: #ffffff !important;
          box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
          margin: 0 auto !important;
          box-sizing: border-box !important;
          position: relative !important;
        }
        .docx-viewer-container table {
          border-collapse: collapse !important;
        }
      `}</style>
    </>
  );
}
