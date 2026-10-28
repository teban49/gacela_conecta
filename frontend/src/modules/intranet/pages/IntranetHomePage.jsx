import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Newspaper, FileText, Network, BookOpen, Video, ExternalLink, Plus, Search, Filter, Upload, Download, History, X, Building2, Layers, Users, Trash2, HelpCircle } from 'lucide-react';
import NewsFeed from '../components/NewsFeed';
import FaqSection from '../components/FaqSection';
import OrganigramaSection from '../components/OrganigramaSection';
import { intranetAPI } from '../../../services/intranetAPI';
import DirectoryPage from './DirectoryPage';
import { useAuth } from '../../../contexts/AuthContext';
import { canViewQualityArea } from '../../../utils/roleAccess';

const platformNames = ['GIS', 'PXP', 'FICS', 'Ofima', 'Frixo'];
const manualCategories = ['Todos', 'GIS', 'FICS', 'PXP', 'Ofima'];
const qualityAreas = [
  { id: 'vinculaciones', label: 'Vinculaciones' },
  { id: 'mantenimiento', label: 'Mantenimiento' },
  { id: 'torre-control', label: 'Torre de control' },
  { id: 'calidad', label: 'Calidad' },
  { id: 'tecnologia', label: 'Tecnología' },
  { id: 'gerencia', label: 'Gerencia' },
  { id: 'servicio-cliente', label: 'Servicio al cliente' },
  { id: 'gestion-humana', label: 'Gestión humana' },
  { id: 'compras-proveedores', label: 'Compras y proveedores' },
];
const qualityCategories = [
  { id: 'caracterizacion', label: 'Caracterización' },
  { id: 'desempeño', label: 'Desempeño' },
  { id: 'directrices', label: 'Directrices' },
  { id: 'formatos', label: 'Formatos' },
  { id: 'matrices', label: 'Matrices' },
  { id: 'procedimiento', label: 'Procedimiento' },
];
const mediaPaths = {
  pildoras: '/media/intranet/pildoras/videos.json',
  manuales: '/media/intranet/manuales/manuales.json',
  links: '/media/intranet/aplicativos/links.json',
};
const routeTabs = {
  calidad: 'calidad',
  documentacion: 'documentacion',
  directorio: 'directorio',
  organigrama: 'organigrama',
  'preguntas-frecuentes': 'preguntas',
  faq: 'preguntas',
  manuales: 'manuales',
  pildoras: 'pildoras',
  linksaplicativos: 'links',
  links: 'links',
};

const loadMediaCatalog = async (path) => {
  const response = await fetch(path);
  if (!response.ok) {
    throw new Error(`No se pudo cargar el catálogo (${response.status}).`);
  }
  return response.json();
};

export const IntranetHomePage = () => {
  const { tab } = useParams();
  const { hasPermission, hasRole, user } = useAuth();
  const canManageDocuments = hasPermission('intranet.administrar_documentos');
  const canManageNews = hasPermission('intranet.administrar_noticias');
  const canManageQuality = hasRole('admin');
  const [activeTab, setActiveTab] = useState(() => routeTabs[tab?.toLowerCase()] ?? 'noticias');
  const [activePillPlatform, setActivePillPlatform] = useState(platformNames[0]);
  const [activeManualCategory, setActiveManualCategory] = useState('Todos');
  const [selectedQualityCompany, setSelectedQualityCompany] = useState('');
  const [selectedQualityArea, setSelectedQualityArea] = useState('');
  const [selectedQualityCategory, setSelectedQualityCategory] = useState('');
  const [qualityUploadCategory, setQualityUploadCategory] = useState(qualityCategories[0].id);
  const [qualityUploadFile, setQualityUploadFile] = useState(null);
  const [qualityUploading, setQualityUploading] = useState(false);
  const [qualityMessage, setQualityMessage] = useState('');
  const [qualityReload, setQualityReload] = useState(0);
  const [noticiaImage, setNoticiaImage] = useState(null);
  const [noticiaImagePreview, setNoticiaImagePreview] = useState('');
  const [qualityDocuments, setQualityDocuments] = useState([]);
  const [qualityDocumentsLoading, setQualityDocumentsLoading] = useState(false);
  const [qualityDocumentsError, setQualityDocumentsError] = useState('');
  const [loading, setLoading] = useState(true);
  const [mediaCatalog, setMediaCatalog] = useState({ pildoras: {}, manuales: [], links: [] });
  const [mediaError, setMediaError] = useState('');
  const [homeData, setHomeData] = useState({
    empresas: [],
    areas: [],
    categorias: [],
    noticias: [],
    manuales: [],
    pildoras: [],
    links: [],
  });

  const [documentos, setDocumentos] = useState([]);

  // Filter state for Documentos / Calidad
  const [selectedEmpresa, setSelectedEmpresa] = useState('');
  const [selectedArea, setSelectedArea] = useState('');
  const [selectedCategoria, setSelectedCategoria] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  // Modals
  const [showDocModal, setShowDocModal] = useState(false);
  const [showNoticiaModal, setShowNoticiaModal] = useState(false);
  const [showVersionModal, setShowVersionModal] = useState(false);
  const [selectedDoc, setSelectedDoc] = useState(null);

  const [docFormData, setDocFormData] = useState({
    EmpresaId: 1,
    AreaId: 1,
    CategoriaId: 1,
    Titulo: '',
    CodigoDocumento: '',
    Version: '1.0',
    RutaArchivo: '',
    Descripcion: '',
  });

  const [noticiaFormData, setNoticiaFormData] = useState({
    Titulo: '',
    Resumen: '',
    Contenido: '',
    ImagenUrl: '',
    Destacado: false,
  });

  const [versionFormData, setVersionFormData] = useState({
    Version: '',
    RutaArchivo: '',
  });

  useEffect(() => {
    fetchInitialData();
  }, []);

  useEffect(() => {
    if (activeTab === 'documentacion') {
      fetchDocumentos();
    }
  }, [activeTab, selectedEmpresa, selectedArea, selectedCategoria, searchTerm]);

  useEffect(() => {
    const companies = user?.organization?.Empresas ?? [];
    if (companies.length === 1) {
      setSelectedQualityCompany(String(companies[0].Id));
    } else if (!companies.some((company) => String(company.Id) === selectedQualityCompany)) {
      setSelectedQualityCompany('');
    }
  }, [user]);

  useEffect(() => {
    if (activeTab !== 'calidad' || !selectedQualityCompany) {
      setQualityDocuments([]);
      setQualityDocumentsError('');
      setQualityDocumentsLoading(false);
      return undefined;
    }

    let cancelled = false;
    setQualityDocumentsLoading(true);
    setQualityDocumentsError('');
    intranetAPI.getCalidadDocumentos(selectedQualityCompany)
      .then((response) => {
        if (!cancelled && response.data?.success) {
          setQualityDocuments(response.data.data ?? []);
        }
      })
      .catch((error) => {
        if (!cancelled) {
          setQualityDocumentsError(error.response?.data?.message || 'No fue posible cargar los documentos de Calidad.');
        }
      })
      .finally(() => {
        if (!cancelled) setQualityDocumentsLoading(false);
      });

    return () => { cancelled = true; };
  }, [activeTab, selectedQualityCompany, qualityReload]);

  useEffect(() => {
    const path = mediaPaths[activeTab];
    if (!path) return;
    let cancelled = false;
    setMediaError('');
    loadMediaCatalog(path)
      .then((catalog) => {
        if (cancelled) return;
        if (activeTab === 'pildoras') {
          if (!catalog || platformNames.some((platform) => !Array.isArray(catalog[platform]))) {
            throw new Error('El catálogo de píldoras debe incluir una lista por plataforma.');
          }
          setMediaCatalog((current) => ({ ...current, pildoras: catalog }));
        } else {
          if (!Array.isArray(catalog)) throw new Error('El catálogo tiene un formato inválido.');
          const requiredFields = activeTab === 'manuales' ? ['titulo', 'archivo'] : ['id', 'titulo', 'url', 'imagen'];
          if (catalog.some((item) => requiredFields.some((field) => typeof item[field] !== 'string' || item[field].trim() === ''))) {
            throw new Error('El catálogo contiene un elemento sin los campos requeridos.');
          }
          setMediaCatalog((current) => ({ ...current, [activeTab]: catalog }));
        }
      })
      .catch((error) => {
        if (!cancelled) setMediaError(error.message || 'No fue posible cargar el contenido.');
      });
    return () => { cancelled = true; };
  }, [activeTab]);

  useEffect(() => {
    if (tab) setActiveTab(routeTabs[tab.toLowerCase()] ?? 'noticias');
  }, [tab]);

  const fetchInitialData = async () => {
    try {
      setLoading(true);
      const res = await intranetAPI.getHomeData();
      if (res.data?.success) {
        setHomeData(res.data.data);
      }
    } catch (err) {
      console.error('Error cargando datos de intranet:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchDocumentos = async () => {
    try {
      const params = {};
      if (selectedEmpresa) params.EmpresaId = selectedEmpresa;
      if (selectedArea) params.AreaId = selectedArea;
      if (selectedCategoria) params.CategoriaId = selectedCategoria;
      if (searchTerm) params.q = searchTerm;

      const res = await intranetAPI.getDocumentos(params);
      if (res.data?.success) {
        setDocumentos(res.data.data);
      }
    } catch (err) {
      console.error('Error cargando documentos:', err);
    }
  };

  const handleSaveDoc = async () => {
    try {
      const res = await intranetAPI.createDocumento(docFormData);
      if (res.data?.success) {
        alert(res.data.message || 'Documento guardado');
        setShowDocModal(false);
        fetchDocumentos();
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Error guardando documento');
    }
  };

  const resetNoticiaForm = () => {
    setNoticiaFormData({ Titulo: '', Resumen: '', Contenido: '', ImagenUrl: '', Destacado: false });
    setNoticiaImage(null);
  };

  const handleNoticiaImageChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    setNoticiaImage(file);
  };

  useEffect(() => {
    if (!noticiaImage) {
      setNoticiaImagePreview('');
      return undefined;
    }
    const url = URL.createObjectURL(noticiaImage);
    setNoticiaImagePreview(url);
    return () => URL.revokeObjectURL(url);
  }, [noticiaImage]);

  const handleSaveNoticia = async () => {
    if (!noticiaFormData.Titulo.trim() || !noticiaFormData.Contenido.trim()) {
      alert('El título y el contenido son obligatorios.');
      return;
    }
    const body = new FormData();
    body.append('Titulo', noticiaFormData.Titulo.trim());
    body.append('Resumen', noticiaFormData.Resumen.trim());
    body.append('Contenido', noticiaFormData.Contenido.trim());
    body.append('Estado', 'publicada');
    if (noticiaImage) body.append('Imagen', noticiaImage);

    try {
      const res = await intranetAPI.createNoticia(body);
      if (res.data?.success) {
        alert(res.data.message || 'Noticia publicada');
        setShowNoticiaModal(false);
        resetNoticiaForm();
        fetchInitialData();
      }
    } catch (err) {
      const fieldErrors = Object.values(err.response?.data?.errors ?? {}).flat().join(' ');
      if (err.response?.status !== 422) alert(err.response?.data?.message || 'Error publicando noticia');
      else if (!fieldErrors) alert('Revisa los datos de la noticia.');
    }
  };

  const handleSaveVersion = async () => {
    if (!selectedDoc) return;
    try {
      const res = await intranetAPI.updateDocumentoVersion(selectedDoc.Id, versionFormData.Version, versionFormData.RutaArchivo);
      if (res.data?.success) {
        alert(res.data.message || 'Versión actualizada');
        setShowVersionModal(false);
        fetchDocumentos();
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Error actualizando versión');
    }
  };

  const handleDownloadQualityDocument = async (document) => {
    try {
      const response = await intranetAPI.descargarCalidadDocumento(
        selectedQualityCompany,
        document.area,
        document.archivo,
        document.categoria,
      );
      const downloadUrl = URL.createObjectURL(response.data);
      const anchor = window.document.createElement('a');
      anchor.href = downloadUrl;
      anchor.download = document.archivo;
      window.document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
      window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
    } catch (error) {
      setQualityDocumentsError(error.response?.data?.message || 'No fue posible descargar el documento.');
    }
  };

  const handleUploadQualityDocument = async (event) => {
    event.preventDefault();
    if (!qualityUploadFile || !selectedQualityArea) return;
    setQualityUploading(true);
    setQualityDocumentsError('');
    setQualityMessage('');
    try {
      const response = await intranetAPI.subirCalidadDocumento(
        selectedQualityCompany,
        selectedQualityArea,
        qualityUploadCategory,
        qualityUploadFile,
      );
      setQualityMessage(response.data?.message || 'Documento cargado correctamente.');
      setQualityUploadFile(null);
      event.target.reset();
      setQualityReload((value) => value + 1);
    } catch (error) {
      setQualityDocumentsError(error.response?.data?.message || 'No fue posible subir el documento.');
    } finally {
      setQualityUploading(false);
    }
  };

  const handleDeleteQualityDocument = async (document) => {
    if (!window.confirm(`¿Eliminar definitivamente "${document.titulo}"?`)) return;
    setQualityDocumentsError('');
    setQualityMessage('');
    try {
      const response = await intranetAPI.eliminarCalidadDocumento(
        selectedQualityCompany,
        document.area,
        document.archivo,
        document.categoria,
      );
      setQualityMessage(response.data?.message || 'Documento eliminado correctamente.');
      setQualityReload((value) => value + 1);
    } catch (error) {
      setQualityDocumentsError(error.response?.data?.message || 'No fue posible eliminar el documento.');
    }
  };

  const visibleManuals = [
    ...(homeData.manuales ?? []).map((manual) => ({
      id: `documento-${manual.Id}`,
      titulo: manual.Titulo || manual.Nombre,
      tipo: manual.CategoriaNombre || manual.Tipo,
      version: manual.Version,
      url: manual.RutaArchivo,
    })),
    ...mediaCatalog.manuales.map((manual, index) => ({
      id: `pdf-${manual.id ?? index}`,
      titulo: manual.titulo,
      tipo: manual.categoria || 'Manual técnico',
      version: manual.version,
      url: `/media/intranet/manuales/pdf/${encodeURIComponent(manual.archivo)}`,
    })),
  ].filter((manual) => activeManualCategory === 'Todos' || manual.tipo === activeManualCategory);

  if (loading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto p-6 space-y-6">
      {/* Header Intranet */}
      <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 className="text-3xl font-bold text-slate-900">Intranet Corporativa Grupo Gacela</h1>
            <p className="text-slate-500 mt-1">Centro de comunicación, gestión documental de Calidad y Organigrama.</p>
          </div>
          <div className="flex gap-2">
            {canManageDocuments && (
            <button 
              onClick={() => setShowDocModal(true)}
              className="inline-flex items-center gap-2 px-4 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-semibold hover:bg-orange-700 transition"
            >
              <Upload size={16} />
              Cargar Documento
            </button>
            )}
            {canManageNews && (
            <button 
              onClick={() => setShowNoticiaModal(true)}
              className="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition"
            >
              <Plus size={16} />
              Nueva Noticia
            </button>
            )}
          </div>
        </div>

        {/* Navigation Tabs */}
        <div className="mt-6 flex flex-wrap gap-2 border-b border-slate-100 pb-3">
          {[
            { id: 'noticias', label: 'Noticias & Comunicados', icon: Newspaper },
            { id: 'calidad', label: 'Gestión de Calidad', icon: FileText },
            { id: 'directorio', label: 'Directorio', icon: Users },
            { id: 'organigrama', label: 'Organigrama Corporativo', icon: Network },
            { id: 'manuales', label: 'Manuales Técnicos', icon: BookOpen },
            { id: 'pildoras', label: 'Píldoras Capacitación', icon: Video },
            { id: 'links', label: 'Links Aplicativos', icon: ExternalLink },
            { id: 'preguntas', label: 'Preguntas Frecuentes', icon: HelpCircle },
          ].map((tab) => {
            const Icon = tab.icon;
            const isActive = activeTab === tab.id;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold transition ${
                  isActive ? 'bg-orange-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
              >
                <Icon size={12} />
                {tab.label}
              </button>
            );
          })}
        </div>
      </div>

      {/* Tab: Noticias */}
      {activeTab === 'noticias' && (
        <NewsFeed noticias={homeData.noticias} />
      )}

      {activeTab === 'directorio' && <DirectoryPage embedded />}

      {/* Tab: Calidad & Documentacion */}
      {activeTab === 'calidad' && (
        <section className="space-y-5">
          <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-center gap-3">
              <Building2 className="text-orange-600" size={22} />
              <div>
                <h2 className="text-lg font-bold text-slate-900">Documentos de Calidad</h2>
                <p className="text-sm text-slate-500">Consulta los documentos disponibles para tus empresas.</p>
              </div>
            </div>
            {(user?.organization?.Empresas ?? []).length > 1 && (
              <div className="mt-5 max-w-xl">
                <label htmlFor="quality-company" className="mb-1 block text-xs font-semibold text-slate-500">Selecciona una empresa</label>
                <select
                  id="quality-company"
                  value={selectedQualityCompany}
                  onChange={(event) => {
                    setSelectedQualityCompany(event.target.value);
                    setSelectedQualityArea('');
                  }}
                  className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none"
                >
                  <option value="">Selecciona una empresa</option>
                  {user.organization.Empresas.map((company) => (
                    <option key={company.Id} value={company.Id}>{company.Nombre}</option>
                  ))}
                </select>
              </div>
            )}
            {(user?.organization?.Empresas ?? []).length === 1 && (
              <p className="mt-4 text-sm font-semibold text-slate-700">
                Empresa: {user.organization.Empresas[0].Nombre}
              </p>
            )}
            {(user?.organization?.Empresas ?? []).length === 0 && (
              <p role="alert" className="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Tu usuario no tiene empresas afiliadas para consultar documentos.
              </p>
            )}
          </div>

          {selectedQualityCompany && (
            <>
              <div className="flex gap-2 overflow-x-auto pb-1">
                {qualityAreas.filter((area) => canViewQualityArea(user, area.id)).map((area) => (
                  <button
                    key={area.id}
                    type="button"
                    onClick={() => {
                      setSelectedQualityArea(area.id);
                      setSelectedQualityCategory('');
                    }}
                    className={`flex-1 whitespace-nowrap rounded-full px-3 py-2.5 text-center text-xs font-bold transition xl:text-sm ${
                      selectedQualityArea === area.id
                        ? 'bg-orange-600 text-white shadow-sm'
                        : 'bg-white text-slate-700 hover:bg-orange-50'
                    }`}
                  >
                    {area.label}
                  </button>
                ))}
              </div>
              {qualityDocumentsError && (
                <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {qualityDocumentsError}
                </div>
              )}
              {qualityMessage && (
                <div role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                  {qualityMessage}
                </div>
              )}
              {selectedQualityArea && canManageQuality && (
                <form onSubmit={handleUploadQualityDocument} className="flex flex-wrap items-end gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                  <div>
                    <label htmlFor="quality-upload-category" className="mb-1 block text-xs font-semibold text-slate-500">Categoría</label>
                    <select
                      id="quality-upload-category"
                      value={qualityUploadCategory}
                      onChange={(event) => setQualityUploadCategory(event.target.value)}
                      className="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    >
                      {qualityCategories.map((category) => (
                        <option key={category.id} value={category.id}>{category.label}</option>
                      ))}
                    </select>
                  </div>
                  <div className="min-w-0 flex-1">
                    <label htmlFor="quality-upload-file" className="mb-1 block text-xs font-semibold text-slate-500">Documento PDF (máx. 20 MB)</label>
                    <input
                      id="quality-upload-file"
                      type="file"
                      accept="application/pdf,.pdf"
                      onChange={(event) => setQualityUploadFile(event.target.files?.[0] ?? null)}
                      className="w-full rounded-xl border border-slate-300 p-2 text-sm"
                    />
                  </div>
                  <button
                    type="submit"
                    disabled={!qualityUploadFile || qualityUploading}
                    className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-50"
                  >
                    <Upload size={16} />
                    {qualityUploading ? 'Subiendo...' : 'Subir documento'}
                  </button>
                </form>
              )}
              {selectedQualityArea && (
                <div className="flex flex-wrap gap-2">
                  {[{ id: '', label: 'Todas las categorías' }, ...qualityCategories].map((category) => (
                    <button
                      key={category.id || 'todas'}
                      type="button"
                      onClick={() => setSelectedQualityCategory(category.id)}
                      className={`rounded-full border px-4 py-1.5 text-xs font-semibold transition ${
                        selectedQualityCategory === category.id
                          ? 'border-slate-900 bg-slate-900 text-white'
                          : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                      }`}
                    >
                      {category.label}
                    </button>
                  ))}
                </div>
              )}
              {qualityDocumentsLoading ? (
                <div className="rounded-3xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                  Cargando documentos...
                </div>
              ) : selectedQualityArea ? (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  {qualityDocuments
                    .filter((document) => document.area === selectedQualityArea
                      && (!selectedQualityCategory || document.categoria === selectedQualityCategory))
                    .map((document) => (
                      <div key={`${document.area}/${document.categoria}/${document.archivo}`} className="flex items-center justify-between gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex min-w-0 items-center gap-3">
                          <FileText className="shrink-0 text-orange-600" size={22} />
                          <div className="min-w-0">
                            <span className="text-xs font-bold text-blue-600">{document.areaNombre} · {document.categoriaNombre}</span>
                            <h3 className="break-words font-bold text-slate-800">{document.titulo}</h3>
                          </div>
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                          <button
                            type="button"
                            onClick={() => handleDownloadQualityDocument(document)}
                            className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-800"
                          >
                            <Download size={14} />
                            Descargar
                          </button>
                          {canManageQuality && (
                            <button
                              type="button"
                              onClick={() => handleDeleteQualityDocument(document)}
                              aria-label={`Eliminar ${document.titulo}`}
                              className="inline-flex items-center rounded-xl border border-red-200 p-2.5 text-red-700 hover:bg-red-50"
                            >
                              <Trash2 size={14} />
                            </button>
                          )}
                        </div>
                      </div>
                    ))}
                  {!qualityDocuments.some((document) => document.area === selectedQualityArea
                    && (!selectedQualityCategory || document.categoria === selectedQualityCategory)) && (
                    <p className="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                      No hay documentos para esta opción.
                    </p>
                  )}
                </div>
              ) : (
                <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                  Selecciona una opción para consultar sus documentos.
                </p>
              )}
            </>
          )}
        </section>
      )}

      {activeTab === 'documentacion' && (
        <div className="space-y-6">
          {/* Filters Bar */}
          <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-base font-bold text-slate-800 flex items-center gap-2">
                <Filter size={16} />
                Filtros de Búsqueda Documental (Por Empresa, Área y Categoría)
              </h3>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <label className="text-xs font-semibold text-slate-500 mb-1 block">Empresa</label>
                <select
                  value={selectedEmpresa}
                  onChange={(e) => setSelectedEmpresa(e.target.value)}
                  className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:border-orange-500"
                >
                  <option value="">Todas las Empresas</option>
                  {homeData.empresas.map((emp) => (
                    <option key={emp.Id} value={emp.Id}>{emp.Nombre}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-semibold text-slate-500 mb-1 block">Área Corporativa</label>
                <select
                  value={selectedArea}
                  onChange={(e) => setSelectedArea(e.target.value)}
                  className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:border-orange-500"
                >
                  <option value="">Todas las Áreas</option>
                  {homeData.areas.map((area) => (
                    <option key={area.Id} value={area.Id}>{area.Nombre}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-semibold text-slate-500 mb-1 block">Categoría de Calidad</label>
                <select
                  value={selectedCategoria}
                  onChange={(e) => setSelectedCategoria(e.target.value)}
                  className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:border-orange-500"
                >
                  <option value="">Todas las Categorías</option>
                  {homeData.categorias.map((cat) => (
                    <option key={cat.Id} value={cat.Id}>{cat.Nombre}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-semibold text-slate-500 mb-1 block">Palabra Clave</label>
                <div className="relative">
                  <input
                    type="text"
                    placeholder="Buscar..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="w-full rounded-xl border border-slate-300 pl-9 pr-3 py-2 text-sm focus:outline-none focus:border-orange-500"
                  />
                  <Search size={14} className="absolute left-3 top-3 text-slate-400" />
                </div>
              </div>
            </div>
          </div>

          {/* Document Table */}
          <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="overflow-x-auto">
              <table className="min-w-full text-sm">
                <thead>
                  <tr className="text-left text-slate-500 border-b border-slate-200">
                    <th className="pb-3 font-semibold">Código</th>
                    <th className="pb-3 font-semibold">Título del Documento</th>
                    <th className="pb-3 font-semibold">Empresa</th>
                    <th className="pb-3 font-semibold">Área</th>
                    <th className="pb-3 font-semibold">Categoría</th>
                    <th className="pb-3 font-semibold">Versión</th>
                    <th className="pb-3 font-semibold">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {documentos.length === 0 ? (
                    <tr>
                      <td colSpan="7" className="py-8 text-center text-slate-400">
                        No se encontraron documentos con los filtros seleccionados.
                      </td>
                    </tr>
                  ) : (
                    documentos.map((doc) => (
                      <tr key={doc.Id} className="border-b border-slate-100 hover:bg-slate-50">
                        <td className="py-3.5 font-mono text-xs text-orange-700 font-bold">{doc.CodigoDocumento || 'N/A'}</td>
                        <td className="py-3.5 font-bold text-slate-800">{doc.Titulo}</td>
                        <td className="py-3.5 text-slate-600">{doc.EmpresaNombre}</td>
                        <td className="py-3.5 text-slate-600">{doc.AreaNombre}</td>
                        <td className="py-3.5">
                          <span className="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full text-xs font-semibold">
                            {doc.CategoriaNombre}
                          </span>
                        </td>
                        <td className="py-3.5 font-semibold text-slate-700">v{doc.Version}</td>
                        <td className="py-3.5">
                          <div className="flex gap-2">
                            <a
                              href={doc.RutaArchivo}
                              target="_blank"
                              rel="noreferrer"
                              className="inline-flex items-center gap-1 rounded-lg bg-orange-50 px-2.5 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-100"
                            >
                              <Download size={13} />
                              Descargar
                            </a>
                            {canManageDocuments && (
                              <button
                                onClick={() => {
                                  setSelectedDoc(doc);
                                  setVersionFormData({ Version: doc.Version, RutaArchivo: doc.RutaArchivo });
                                  setShowVersionModal(true);
                                }}
                                className="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                              >
                                <History size={13} />
                                Actualizar Versión
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Tab: Organigrama */}
      {activeTab === 'organigrama' && (
        <OrganigramaSection canEdit={canManageQuality} />
      )}

      {activeTab === 'preguntas' && <FaqSection />}

      {activeTab === 'pildoras' && (
        <section className="space-y-5">
          <div className="flex flex-wrap gap-2">
            {platformNames.map((platform) => (
              <button
                key={platform}
                type="button"
                onClick={() => setActivePillPlatform(platform)}
                className={`rounded-full px-5 py-2.5 text-sm font-bold transition ${
                  activePillPlatform === platform
                    ? 'bg-orange-600 text-white shadow-sm'
                    : 'bg-white text-slate-700 hover:bg-orange-50'
                }`}
              >
                {platform}
              </button>
            ))}
          </div>
          {mediaError && (
            <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {mediaError}
            </div>
          )}
          {(mediaCatalog.pildoras?.[activePillPlatform] ?? []).length > 0 ? (
            <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
              {mediaCatalog.pildoras[activePillPlatform].map((video, index) => (
                <article key={`${video.archivo ?? video.titulo}-${index}`} className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                  {video.youtube ? (
                    <iframe
                      title={video.titulo}
                      loading="lazy"
                      className="aspect-video w-full bg-slate-950"
                      src={`https://www.youtube.com/embed/${video.youtube.video ?? 'videoseries'}?list=${video.youtube.lista ?? ''}`.replace(/\?list=$/, '')}
                      allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                      allowFullScreen
                    />
                  ) : (
                  <video controls preload="metadata" className="aspect-video w-full bg-slate-950">
                    <source
                      src={`/media/intranet/pildoras/${activePillPlatform}/${encodeURIComponent(video.archivo)}`}
                    />
                    Tu navegador no admite la reproducción de video.
                  </video>
                  )}
                  <div className="space-y-1 p-4">
                    <h3 className="font-bold text-slate-800">{video.titulo}</h3>
                    {video.descripcion && <p className="text-sm text-slate-600">{video.descripcion}</p>}
                  </div>
                </article>
              ))}
            </div>
          ) : (
            !mediaError && (
              <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <Video size={28} className="mx-auto mb-3 text-slate-400" />
                <h3 className="font-semibold text-slate-800">Aún no hay videos de {activePillPlatform}</h3>
                <p className="mt-1 text-sm text-slate-500">
                  Guarda los archivos en <code>frontend/public/media/intranet/pildoras/{activePillPlatform}/</code> y agrégalos al catálogo videos.json.
                </p>
              </div>
            )
          )}
        </section>
      )}

      {/* Tab: Manuales */}
      {activeTab === 'manuales' && (
        <section className="space-y-4">
          <div className="flex flex-wrap gap-2">
            {manualCategories.map((category) => (
              <button
                key={category}
                type="button"
                onClick={() => setActiveManualCategory(category)}
                className={`rounded-full px-5 py-2.5 text-sm font-bold transition ${
                  activeManualCategory === category
                    ? 'bg-orange-600 text-white shadow-sm'
                    : 'bg-white text-slate-700 hover:bg-orange-50'
                }`}
              >
                {category}
              </button>
            ))}
          </div>
          {mediaError && (
            <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {mediaError}
            </div>
          )}
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {visibleManuals.map((manual) => (
            <div key={manual.id} className="flex items-center justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
              <div className="space-y-1">
                <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-600">{manual.tipo}</span>
                <h4 className="text-lg font-bold text-slate-800">{manual.titulo}</h4>
                {manual.version && <p className="text-xs text-slate-400">Versión: {manual.version}</p>}
              </div>
              <a href={manual.url} target="_blank" rel="noreferrer" className="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                Ver Manual
              </a>
            </div>
          ))}
          </div>
          {visibleManuals.length === 0 && !mediaError && (
            <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
              No hay manuales en esta categoría. Agrega los PDF en <code>frontend/public/media/intranet/manuales/pdf/</code> y regístralos en manuales.json.
            </p>
          )}
        </section>
      )}

      {/* Tab: Links */}
      {activeTab === 'links' && (
        <section className="space-y-4">
          {mediaError && (
            <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {mediaError}
            </div>
          )}
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {mediaCatalog.links.map((link) => (
            <a
              key={link.id}
              href={link.url}
              target="_blank"
              rel="noopener noreferrer"
              className="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:border-orange-500 hover:shadow-md"
            >
              <div className="flex h-48 items-center justify-center bg-slate-50 p-3">
                <img
                  src={`/media/intranet/aplicativos/${link.imagen}`}
                  alt={link.titulo}
                  className="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105"
                  onError={(event) => {
                    event.currentTarget.style.visibility = 'hidden';
                  }}
                />
              </div>
              <div className="flex items-center justify-between px-5 py-4">
                <span className="font-bold text-slate-800">{link.titulo}</span>
                <ExternalLink size={16} className="text-slate-400 transition group-hover:text-orange-600" />
              </div>
            </a>
          ))}
          </div>
          <p className="text-center text-xs text-slate-500">
            Para agregar aplicaciones, coloca sus imágenes en <code>frontend/public/media/intranet/aplicativos/images/</code> y edita links.json.
          </p>
        </section>
      )}

      {/* Modal Cargar Documento */}
      {showDocModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="max-w-md w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-xl font-bold text-slate-900">Cargar Nuevo Documento</h3>
              <button onClick={() => setShowDocModal(false)} className="p-2 rounded-lg bg-slate-100 text-slate-600"><X size={18} /></button>
            </div>
            <div className="space-y-3">
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Empresa</label>
                <select value={docFormData.EmpresaId} onChange={(e) => setDocFormData({...docFormData, EmpresaId: parseInt(e.target.value)})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm">
                  {homeData.empresas.map((e) => <option key={e.Id} value={e.Id}>{e.Nombre}</option>)}
                </select>
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Área</label>
                <select value={docFormData.AreaId} onChange={(e) => setDocFormData({...docFormData, AreaId: parseInt(e.target.value)})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm">
                  {homeData.areas.map((a) => <option key={a.Id} value={a.Id}>{a.Nombre}</option>)}
                </select>
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Categoría de Calidad</label>
                <select value={docFormData.CategoriaId} onChange={(e) => setDocFormData({...docFormData, CategoriaId: parseInt(e.target.value)})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm">
                  {homeData.categorias.map((c) => <option key={c.Id} value={c.Id}>{c.Nombre}</option>)}
                </select>
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Título</label>
                <input type="text" value={docFormData.Titulo} onChange={(e) => setDocFormData({...docFormData, Titulo: e.target.value})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Código Documento</label>
                <input type="text" placeholder="Ej: FOR-CAL-001" value={docFormData.CodigoDocumento} onChange={(e) => setDocFormData({...docFormData, CodigoDocumento: e.target.value})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Ruta / Enlace Archivo</label>
                <input type="text" placeholder="https://..." value={docFormData.RutaArchivo} onChange={(e) => setDocFormData({...docFormData, RutaArchivo: e.target.value})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
              </div>
              <button onClick={handleSaveDoc} className="w-full py-3 bg-orange-600 text-white rounded-xl font-semibold text-sm hover:bg-orange-700">Guardar Documento</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Nueva Noticia */}
      {showNoticiaModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="max-w-md w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-xl font-bold text-slate-900">Publicar Noticia</h3>
              <button onClick={() => setShowNoticiaModal(false)} className="p-2 rounded-lg bg-slate-100 text-slate-600"><X size={18} /></button>
            </div>
            <div className="space-y-3">
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Título</label>
                <input type="text" value={noticiaFormData.Titulo} onChange={(e) => setNoticiaFormData({...noticiaFormData, Titulo: e.target.value})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Resumen</label>
                <input type="text" value={noticiaFormData.Resumen} onChange={(e) => setNoticiaFormData({...noticiaFormData, Resumen: e.target.value})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Contenido</label>
                <textarea rows={4} value={noticiaFormData.Contenido} onChange={(e) => setNoticiaFormData({...noticiaFormData, Contenido: e.target.value})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
              </div>
              <div>
                <label htmlFor="noticia-imagen" className="text-xs font-semibold text-slate-600 mb-1 block">Imagen (JPG, PNG o WEBP, máx. 5 MB)</label>
                <input id="noticia-imagen" type="file" accept="image/png,image/jpeg,image/webp" onChange={handleNoticiaImageChange} className="w-full rounded-xl border border-slate-300 p-2 text-sm" />
                {noticiaImagePreview && <img src={noticiaImagePreview} alt="Vista previa" className="mt-2 h-32 w-full rounded-xl object-cover" />}
              </div>
              <button onClick={handleSaveNoticia} className="w-full py-3 bg-slate-900 text-white rounded-xl font-semibold text-sm hover:bg-slate-800">Publicar Noticia</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Actualizar Version */}
      {showVersionModal && selectedDoc && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="max-w-md w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-xl font-bold text-slate-900">Actualizar Versión</h3>
              <button onClick={() => setShowVersionModal(false)} className="p-2 rounded-lg bg-slate-100 text-slate-600"><X size={18} /></button>
            </div>
            <p className="text-sm text-slate-600">Documento: <strong>{selectedDoc.Titulo}</strong></p>
            <div className="space-y-3">
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Nueva Versión</label>
                <input type="text" value={versionFormData.Version} onChange={(e) => setVersionFormData({...versionFormData, Version: e.target.value})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
              </div>
              <div>
                <label className="text-xs font-semibold text-slate-600 mb-1 block">Nueva Ruta Archivo</label>
                <input type="text" value={versionFormData.RutaArchivo} onChange={(e) => setVersionFormData({...versionFormData, RutaArchivo: e.target.value})} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
              </div>
              <button onClick={handleSaveVersion} className="w-full py-3 bg-orange-600 text-white rounded-xl font-semibold text-sm hover:bg-orange-700">Actualizar Versión</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default IntranetHomePage;