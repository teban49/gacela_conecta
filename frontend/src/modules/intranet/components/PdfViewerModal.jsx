import { X } from 'lucide-react';

const PdfViewerModal = ({ title, url, onClose }) => {
  if (!url) return null;

  return (
    <div
      className="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/80 p-3 sm:p-6"
      role="presentation"
      onMouseDown={(event) => {
        if (event.target === event.currentTarget) onClose();
      }}
    >
      <section
        role="dialog"
        aria-modal="true"
        aria-labelledby="pdf-viewer-title"
        className="flex h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
      >
        <header className="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-3">
          <h2 id="pdf-viewer-title" className="min-w-0 truncate font-semibold text-slate-900">
            {title}
          </h2>
          <button
            type="button"
            onClick={onClose}
            aria-label="Cerrar visor de PDF"
            className="shrink-0 rounded-lg p-2 text-slate-500 hover:bg-slate-100"
          >
            <X size={18} />
          </button>
        </header>
        <iframe
          title={title}
          src={`${url}#toolbar=1&navpanes=1&statusbar=1`}
          className="min-h-0 flex-1 bg-slate-100"
        />
      </section>
    </div>
  );
};

export default PdfViewerModal;
