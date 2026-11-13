import { useEffect, useMemo, useState } from 'react';
import { BookOpen, Building2, Eye, FileText, Plus, Trash2, Upload, X } from 'lucide-react';
import { useAuth } from '../../../contexts/AuthContext';
import { intranetAPI } from '../../../services/intranetAPI';
import PdfViewerModal from '../components/PdfViewerModal';

const libraries = {
  formatos: {
    title: 'Formatos',
    description: 'Consulta los formatos disponibles por empresa y categoría.',
    catalogUrl: '/media/intranet/formatos/catalog.json',
    categories: [
      { id: 'gestion humana', label: 'Gestión humana' },
      { id: 'compras', label: 'Compras' },
      { id: 'servicio al cliente', label: 'Servicio al cliente' },
      { id: 'sistemas', label: 'Sistemas' },
    ],
  },
  documentacion: {
    title: 'Documentación',
    description: 'Consulta la documentación corporativa de tu empresa.',
    catalogUrl: '/media/intranet/documentacion/catalog.json',
    categories: [],
  },
};

const normalize = (value) => String(value ?? '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLocaleLowerCase()
  .replace(/[^a-z0-9]/g, '');

const getCompanyDirectory = (companyName, catalog) => {
  const companyKey = normalize(companyName);
  if (!companyKey) return '';
  return Object.keys(catalog).find((directory) => {
    const directoryKey = normalize(directory);
    return directoryKey === companyKey
      || companyKey.includes(directoryKey)
      || directoryKey.includes(companyKey);
  }) ?? '';
};

const DocumentLibraryPage = ({ kind }) => {
  const { user, hasRole, hasPermission } = useAuth();
  const library = libraries[kind];
  const companies = user?.organization?.Empresas ?? [];
  const canManageDocuments = hasRole('admin') && hasPermission('intranet.administrar_documentos');
  const [catalog, setCatalog] = useState({});
  const [selectedCompanyId, setSelectedCompanyId] = useState('');
  const [selectedCategory, setSelectedCategory] = useState(library?.categories[0]?.id ?? '');
  const [viewer, setViewer] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showNewModal, setShowNewModal] = useState(false);
  const [file, setFile] = useState(null);
  const [title, setTitle] = useState('');
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [reload, setReload] = useState(0);

  useEffect(() => {
    if (!library) return undefined;
    let cancelled = false;
    setLoading(true);
    setError('');
    fetch(`${library.catalogUrl}?t=${reload}`)
      .then((response) => {
        if (!response.ok) throw new Error(`No se pudo cargar el catálogo (${response.status}).`);
        return response.json();
      })
      .then((data) => {
        if (!data || typeof data !== 'object' || Array.isArray(data)) {
          throw new Error('El catálogo de documentos tiene un formato inválido.');
        }
        if (!cancelled) setCatalog(data);
      })
      .catch((loadError) => {
        if (!cancelled) setError(loadError.message || 'No se pudo cargar el catálogo de documentos.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => { cancelled = true; };
  }, [library, reload]);

  useEffect(() => {
    if (companies.length === 1) {
      setSelectedCompanyId(String(companies[0].Id));
    } else if (!companies.some((company) => String(company.Id) === selectedCompanyId)) {
      setSelectedCompanyId('');
    }
  }, [companies, selectedCompanyId]);

  const selectedCompany = companies.find((company) => String(company.Id) === selectedCompanyId);
  const companyDirectory = selectedCompany ? getCompanyDirectory(selectedCompany.Nombre, catalog) : '';
  const files = useMemo(() => {
    if (!companyDirectory || !library) return [];
    const companyCatalog = catalog[companyDirectory] ?? {};
    const documents = library.categories.length
      ? companyCatalog[selectedCategory]
      : companyCatalog.documentos;
    return Array.isArray(documents) ? documents : [];
  }, [catalog, companyDirectory, kind, library, selectedCategory]);

  if (!library) return null;
  const Icon = kind === 'formatos' ? FileText : BookOpen;

  const documentUrl = (document) => `/media/intranet/${[
    kind,
    companyDirectory,
    ...(library.categories.length ? [selectedCategory] : []),
    document.archivo,
  ].map(encodeURIComponent).join('/')}`;

  const openNewModal = () => {
    setError('');
    setMessage('');
    setTitle('');
    setFile(null);
    setShowNewModal(true);
  };

  const saveDocument = async (event) => {
    event.preventDefault();
    if (!selectedCompany || !companyDirectory || !file) {
      setError('Selecciona una empresa y un archivo PDF.');
      return;
    }
    setSaving(true);
    setError('');
    setMessage('');
    try {
      const response = await intranetAPI.uploadLibraryDocument(kind, {
        Empresa: companyDirectory,
        Categoria: selectedCategory,
        Titulo: title,
        Archivo: file,
      });
      setMessage(response.data?.message || 'Documento agregado correctamente.');
      setShowNewModal(false);
      setReload((value) => value + 1);
    } catch (uploadError) {
      setError(uploadError.response?.data?.message || 'No fue posible guardar el documento.');
    } finally {
      setSaving(false);
    }
  };

  const deleteDocument = async (document) => {
    if (!window.confirm(`¿Borrar "${document.titulo}"?`)) return;
    setError('');
    setMessage('');
    try {
      const response = await intranetAPI.deleteLibraryDocument(kind, {
        Empresa: companyDirectory,
        Categoria: selectedCategory,
        Archivo: document.archivo,
      });
      setMessage(response.data?.message || 'Documento eliminado correctamente.');
      setReload((value) => value + 1);
    } catch (deleteError) {
      setError(deleteError.response?.data?.message || 'No fue posible borrar el documento.');
    }
  };

  return (
    <section className="space-y-5">
      <header className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <Icon className="text-orange-600" size={24} />
            <div>
              <h2 className="text-xl font-bold text-slate-900">{library.title}</h2>
              <p className="mt-1 text-sm text-slate-500">{library.description}</p>
            </div>
          </div>
          {canManageDocuments && selectedCompany && companyDirectory && (
            <button
              type="button"
              onClick={openNewModal}
              className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700"
            >
              <Plus size={16} /> Nuevo
            </button>
          )}
        </div>
        {companies.length > 1 && (
          <label className="mt-5 block max-w-xl text-sm font-semibold text-slate-700">
            Empresa
            <select
              value={selectedCompanyId}
              onChange={(event) => {
                setSelectedCompanyId(event.target.value);
                setViewer(null);
              }}
              className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3"
            >
              <option value="">Selecciona una empresa</option>
              {companies.map((company) => (
                <option key={company.Id} value={company.Id}>{company.Nombre}</option>
              ))}
            </select>
          </label>
        )}
        {companies.length === 1 && (
          <p className="mt-4 text-sm font-semibold text-slate-700">Empresa: {companies[0].Nombre}</p>
        )}
        {companies.length === 0 && (
          <p role="alert" className="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Tu usuario no tiene empresas afiliadas para consultar documentos.
          </p>
        )}
      </header>

      {library.categories.length > 0 && (
        <nav className="flex flex-wrap gap-2" aria-label="Categorías de formatos">
          {library.categories.map((category) => (
            <button
              key={category.id}
              type="button"
              onClick={() => {
                setSelectedCategory(category.id);
                setViewer(null);
              }}
              className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                selectedCategory === category.id
                  ? 'bg-orange-600 text-white'
                  : 'bg-white text-slate-700 hover:bg-orange-50'
              }`}
            >
              {category.label}
            </button>
          ))}
        </nav>
      )}

      {error && (
        <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</p>
      )}
      {message && (
        <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{message}</p>
      )}
      {loading ? (
        <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
          Cargando documentos...
        </div>
      ) : selectedCompany && companyDirectory ? (
        files.length > 0 ? (
          <div className="grid gap-4 md:grid-cols-2">
            {files.map((document) => (
              <article key={`${companyDirectory}/${selectedCategory}/${document.archivo}`} className="flex items-center justify-between gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex min-w-0 items-center gap-3">
                  <Building2 className="shrink-0 text-orange-600" size={22} />
                  <div className="min-w-0">
                    <span className="text-xs font-bold text-blue-600">
                      {library.categories.find((category) => category.id === selectedCategory)?.label ?? library.title}
                    </span>
                    <h3 className="break-words font-bold text-slate-800">{document.titulo}</h3>
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <button
                    type="button"
                    onClick={() => setViewer({ title: document.titulo, url: documentUrl(document) })}
                    className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                  >
                    <Eye size={14} /> Ver
                  </button>
                  {canManageDocuments && (
                    <button
                      type="button"
                      onClick={() => deleteDocument(document)}
                      className="inline-flex items-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50"
                    >
                      <Trash2 size={14} /> Borrar
                    </button>
                  )}
                </div>
              </article>
            ))}
          </div>
        ) : (
          <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
            No hay documentos disponibles {library.categories.length ? 'en esta categoría' : ''} para esta empresa.
          </p>
        )
      ) : companies.length > 0 ? (
        <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
          {selectedCompany
            ? 'No hay un catálogo configurado para esta empresa.'
            : 'Selecciona una empresa para consultar sus documentos.'}
        </p>
      ) : null}

      {showNewModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="presentation">
          <form onSubmit={saveDocument} className="w-full max-w-lg space-y-4 rounded-3xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="library-new-title">
            <div className="flex items-center justify-between gap-4">
              <h3 id="library-new-title" className="text-xl font-bold text-slate-900">
                Nuevo {kind === 'formatos' ? 'formato' : 'documento'}
              </h3>
              <button type="button" onClick={() => setShowNewModal(false)} aria-label="Cerrar" className="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                <X size={18} />
              </button>
            </div>
            {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
            <p className="text-sm text-slate-500">
              Empresa: <strong>{selectedCompany?.Nombre}</strong>
            </p>
            {library.categories.length > 0 && (
              <label className="block text-sm font-medium text-slate-700">
                Categoría
                <select
                  value={selectedCategory}
                  onChange={(event) => setSelectedCategory(event.target.value)}
                  className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                >
                  {library.categories.map((category) => (
                    <option key={category.id} value={category.id}>{category.label}</option>
                  ))}
                </select>
              </label>
            )}
            <label className="block text-sm font-medium text-slate-700">
              Título
              <input required maxLength={150} value={title} onChange={(event) => setTitle(event.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
            </label>
            <label className="block text-sm font-medium text-slate-700">
              Archivo PDF (máx. 30 MB)
              <input required type="file" accept="application/pdf,.pdf" onChange={(event) => setFile(event.target.files?.[0] ?? null)} className="mt-1 w-full rounded-xl border border-slate-300 p-2 text-sm" />
            </label>
            <div className="flex justify-end gap-2">
              <button type="button" onClick={() => setShowNewModal(false)} className="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
              <button type="submit" disabled={!file || saving} className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-50">
                <Upload size={15} /> {saving ? 'Guardando...' : 'Guardar'}
              </button>
            </div>
          </form>
        </div>
      )}

      <PdfViewerModal title={viewer?.title} url={viewer?.url} onClose={() => setViewer(null)} />
    </section>
  );
};

export default DocumentLibraryPage;
