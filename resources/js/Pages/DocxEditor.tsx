import React, { useEffect, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import { renderAsync } from 'docx-preview';
import Swal from 'sweetalert2';

interface DocxEditorProps {
  doc: {
    id: number;
    title: string;
    content: string | null;
    file_path: string | null;
    status: string;
  };
  canEdit: boolean;
}

export default function DocxEditor({ doc, canEdit }: DocxEditorProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [saving, setSaving] = useState<boolean>(false);
  const [progress, setProgress] = useState<number | null>(null);

  const loadDocx = async () => {
    if (!containerRef.current) return;
    setLoading(true);
    try {
      const response = await fetch(`/documents/${doc.id}/download-docx?t=${Date.now()}`);
      if (!response.ok) {
        setLoading(false);
        return;
      }
      const blob = await response.blob();
      containerRef.current.innerHTML = '';
      
      await renderAsync(blob, containerRef.current, undefined, {
        inWrapper: true,
        ignoreWidth: false,
        ignoreHeight: false,
        ignoreFonts: false,
        breakPages: true,
        experimental: true,
        useBase64URL: true,
      });

      // Enhance sections with physical page sheet boundaries and page numbers
      const wrapper = containerRef.current.querySelector('.docx-wrapper');
      if (wrapper) {
        wrapper.classList.add('docx-page-stack');
      }

      const sections = containerRef.current.querySelectorAll('section.docx');
      sections.forEach((sec, index) => {
        sec.setAttribute('contenteditable', 'true');
        sec.classList.add('docx-paper-sheet');
        
        // Add visual page boundary marker if not already present
        if (!sec.querySelector('.docx-page-badge')) {
          const badge = document.createElement('div');
          badge.className = 'docx-page-badge';
          badge.innerHTML = `<span>Halaman ${index + 1} dari ${sections.length} (A4)</span>`;
          badge.setAttribute('contenteditable', 'false');
          sec.insertBefore(badge, sec.firstChild);
        }
      });
    } catch (error) {
      console.error('Error rendering DOCX:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDocx();
  }, [doc.id]);

  const handleSave = async () => {
    if (!containerRef.current) return;
    setSaving(true);
    try {
      const sections = containerRef.current.querySelectorAll('section.docx');
      let combinedHtml = '';
      sections.forEach((sec) => {
        // Clone to strip temporary UI badges before saving
        const clone = sec.cloneNode(true) as HTMLElement;
        const badge = clone.querySelector('.docx-page-badge');
        if (badge) badge.remove();
        combinedHtml += clone.innerHTML;
      });

      const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
      const res = await fetch(`/documents/${doc.id}/save-canvas-edits`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ html: combinedHtml }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Tersimpan 1:1 Presisi!',
          text: 'Perubahan dokumen berhasil disinkronkan ke file DOCX asli.',
          timer: 1800,
          showConfirmButton: false,
        });
        loadDocx();
      } else {
        Swal.fire('Gagal Menyimpan', data.message || 'Terjadi kesalahan pada server.', 'error');
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

    const xhr = new XMLHttpRequest();
    xhr.open('POST', `/documents/${doc.id}/upload-docx`, true);

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
    if (csrfToken) {
      xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    }

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        const percentComplete = Math.round((event.loaded / event.total) * 100);
        setProgress(percentComplete);
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
      Swal.fire('Error', 'Terjadi kesalahan saat mengunggah berkas.', 'error');
    };

    xhr.send(formData);
  };

  return (
    <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col font-sans">
      <Head title={`Editor Canvas 1:1 - ${doc.title}`} />

      {/* Global CSS to override font fallbacks and render physical paper sheet gaps */}
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Tinos:ital,wght@0,400;0,700;1,400;1,700&family=Carlito:ital,wght@0,400;0,700;1,400;1,700&display=swap');

        .docx-wrapper {
          background-color: #0f172a !important;
          padding: 40px 20px !important;
          display: flex !important;
          flex-direction: column !important;
          align-items: center !important;
          gap: 32px !important;
        }

        section.docx.docx-paper-sheet {
          background: #ffffff !important;
          color: #000000 !important;
          box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
          border-radius: 2px !important;
          position: relative !important;
          margin: 0 auto 32px auto !important;
          transition: transform 0.2s ease, box-shadow 0.2s ease;
          font-family: 'Times New Roman', 'Tinos', 'Calibri', 'Carlito', serif !important;
        }

        section.docx.docx-paper-sheet:focus-within {
          outline: 2px solid #3b82f6 !important;
          box-shadow: 0 25px 30px -5px rgba(59, 130, 246, 0.3), 0 0 0 1px #3b82f6 !important;
        }

        /* Physical Page Divider Badge */
        .docx-page-badge {
          position: absolute;
          top: -24px;
          right: 0;
          font-size: 11px;
          font-weight: 600;
          color: #94a3b8;
          user-select: none;
          pointer-events: none;
          display: flex;
          align-items: center;
          gap: 6px;
        }

        .docx-page-badge span {
          background: #1e293b;
          padding: 2px 10px;
          border-radius: 9999px;
          border: 1px solid #334155;
        }

        /* Page Gap Visual Separator Line */
        section.docx.docx-paper-sheet::after {
          content: '';
          position: absolute;
          bottom: -20px;
          left: 50%;
          transform: translateX(-50%);
          width: 80%;
          height: 1px;
          background: linear-gradient(90deg, transparent, #334155, transparent);
          pointer-events: none;
        }

        section.docx.docx-paper-sheet:last-of-type::after {
          display: none;
        }
      `}</style>

      {/* Header Bar */}
      <header className="sticky top-0 z-50 bg-slate-800/90 backdrop-blur-md border-b border-slate-700 px-6 py-3 flex items-center justify-between shadow-lg">
        <div className="flex items-center gap-3">
          <a
            href="/documents"
            className="p-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 transition"
            title="Kembali ke Daftar Dokumen"
          >
            ← Kembali
          </a>
          <div>
            <h1 className="text-base font-bold text-white flex items-center gap-2">
              📄 {doc.title}
              <span className="text-xs font-normal px-2 py-0.5 rounded bg-blue-500/20 text-blue-400 border border-blue-500/30">
                Presisi OpenXML 1:1
              </span>
            </h1>
            <p className="text-xs text-slate-400">Tampilan Lembaran Kertas A4 Identik Word / Google Docs</p>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <input
            type="file"
            id="docx-import-file-input"
            ref={fileInputRef}
            className="hidden"
            accept=".docx,.doc"
            onChange={handleFileUpload}
          />
          <button
            onClick={() => fileInputRef.current?.click()}
            className="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-100 font-semibold text-xs rounded-lg transition border border-slate-600 shadow-sm flex items-center gap-1.5"
          >
            📥 Import DOCX
          </button>

          <button
            onClick={handleSave}
            disabled={saving}
            className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-semibold text-xs rounded-lg transition shadow-md flex items-center gap-1.5"
          >
            {saving ? 'Menyimpan...' : '💾 Simpan Edit'}
          </button>

          <a
            href={`/documents/${doc.id}/download-docx`}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs rounded-lg transition shadow-md flex items-center gap-1.5"
          >
            ⬇️ Download DOCX
          </a>

          <a
            href={`/documents/${doc.id}/download-pdf`}
            className="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs rounded-lg transition shadow-md flex items-center gap-1.5"
          >
            📄 Download PDF (A4)
          </a>
        </div>
      </header>

      {/* Upload Progress Bar */}
      {progress !== null && (
        <div className="w-full bg-slate-800 h-2">
          <div
            className="bg-blue-500 h-2 transition-all duration-300"
            style={{ width: `${progress}%` }}
          />
        </div>
      )}

      {/* Main Container */}
      <main className="flex-1 p-6 relative flex justify-center">
        {loading && (
          <div className="absolute inset-0 bg-slate-900/80 z-40 flex flex-col items-center justify-center gap-3">
            <div className="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            <p className="text-sm font-medium text-slate-300">Memuat Lembaran Dokumen Word 1:1...</p>
          </div>
        )}

        {/* Paper Canvas Viewer Container */}
        <div
          ref={containerRef}
          className="docx-viewer-container w-full max-w-5xl flex flex-col items-center"
        />
      </main>
    </div>
  );
}
