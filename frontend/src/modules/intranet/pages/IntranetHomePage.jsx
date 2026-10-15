import React, { useEffect, useState } from 'react';
import { Newspaper, FileText, Network, BookOpen, Video, ExternalLink, Plus, Search, Filter, Upload, Download, History, X, Building2, Layers, Users } from 'lucide-react';
import { intranetAPI } from '../../../services/intranetAPI';
import DirectoryPage from './DirectoryPage';
import { useAuth } from '../../../contexts/AuthContext';

export const IntranetHomePage = () => {
  const { hasPermission } = useAuth();
  const canManageDocuments = hasPermission('intranet.administrar_documentos');
  const canManageNews = hasPermission('intranet.administrar_noticias');
  const [activeTab, setActiveTab] = useState('noticias');
  const [loading, setLoading] = useState(true);
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
  const [organigrama, setOrganigrama] = useState([]);

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
    if (activeTab === 'documentacion' || activeTab === 'calidad') {
      fetchDocumentos();
    } else if (activeTab === 'organigrama') {
      fetchOrganigrama();
    }
  }, [activeTab, selectedEmpresa, selectedArea, selectedCategoria, searchTerm]);

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

  const fetchOrganigrama = async () => {
    try {
      const res = await intranetAPI.getOrganigrama();
      if (res.data?.success) {
        setOrganigrama(res.data.data);
      }
    } catch (err) {
      console.error('Error cargando organigrama:', err);
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

  const handleSaveNoticia = async () => {
    try {
      const res = await intranetAPI.createNoticia(noticiaFormData);
      if (res.data?.success) {
        alert(res.data.message || 'Noticia publicada');
        setShowNoticiaModal(false);
        fetchInitialData();
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Error publicando noticia');
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
            { id: 'documentacion', label: 'Repositorio Documental', icon: Layers },
            { id: 'directorio', label: 'Directorio', icon: Users },
            { id: 'organigrama', label: 'Organigrama Corporativo', icon: Network },
            { id: 'manuales', label: 'Manuales Técnicos', icon: BookOpen },
            { id: 'pildoras', label: 'Píldoras Capacitación', icon: Video },
            { id: 'links', label: 'Links Aplicativos', icon: ExternalLink },
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
                <Icon size={14} />
                {tab.label}
              </button>
            );
          })}
        </div>
      </div>

      {/* Tab: Noticias */}
      {activeTab === 'noticias' && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {homeData.noticias.length === 0 ? (
            <div className="col-span-full p-12 text-center text-slate-500 bg-white rounded-3xl border border-slate-200">
              No hay noticias o comunicados publicados actualmente.
            </div>
          ) : (
            homeData.noticias.map((noticia) => (
              <div key={noticia.Id} className="rounded-3xl border border-slate-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition">
                {noticia.ImagenUrl ? (
                  <img src={noticia.ImagenUrl} alt={noticia.Titulo} className="w-full h-48 object-cover" />
                ) : (
                  <div className="w-full h-48 bg-gradient-to-r from-orange-500 to-amber-500 flex items-center justify-center text-white text-3xl font-bold">
                    Grupo Gacela
                  </div>
                )}

                <div className="p-5 space-y-2">
                  <span className="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full">
                    {new Date(noticia.FechaPublicacion).toLocaleDateString()}
                  </span>
                  <h3 className="text-lg font-bold text-slate-900 leading-snug">{noticia.Titulo}</h3>
                  <p className="text-sm text-slate-600 line-clamp-3">{noticia.Resumen || noticia.Contenido}</p>
                </div>
              </div>
            ))
          )}
        </div>
      )}

      {activeTab === 'directorio' && <DirectoryPage embedded />}

      {/* Tab: Calidad & Documentacion */}
      {(activeTab === 'calidad' || activeTab === 'documentacion') && (
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
        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-xl font-bold text-slate-900">Estructura Organizacional y Jerarquía Corporativa</h3>
              <p className="text-sm text-slate-500">Representación de cargos, jefe inmediato y dependencias para flujos de aprobación.</p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {organigrama.length === 0 ? (
              <div className="col-span-full p-8 text-center text-slate-500">
                No hay registros cargados en el organigrama.
              </div>
            ) : (
              organigrama.map((nodo) => (
                <div key={nodo.Id} className="p-5 rounded-2xl border border-slate-200 bg-slate-50/70 space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-orange-700 bg-orange-100 px-2.5 py-0.5 rounded-full">
                      Nivel Jerárquico {nodo.NivelJerarquico}
                    </span>
                    <span className="text-xs font-medium text-slate-400">{nodo.Dependencia || 'General'}</span>
                  </div>
                  <h4 className="text-lg font-bold text-slate-900">{nodo.UsuarioNombre}</h4>
                  <p className="text-sm font-semibold text-slate-700">{nodo.CargoNombre}</p>
                  {nodo.JefeNombre && (
                    <div className="pt-2 border-t border-slate-200/60 text-xs text-slate-500 flex items-center gap-1">
                      <span className="font-semibold text-slate-600">Jefe Inmediato:</span> {nodo.JefeNombre}
                    </div>
                  )}
                </div>
              ))
            )}
          </div>
        </div>
      )}

      {/* Tab: Manuales */}
      {activeTab === 'manuales' && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {homeData.manuales.map((man) => (
            <div key={man.Id} className="p-5 rounded-3xl border border-slate-200 bg-white shadow-sm flex items-center justify-between">
              <div className="space-y-1">
                <span className="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full">{man.Tipo}</span>
                <h4 className="text-lg font-bold text-slate-800">{man.Titulo}</h4>
                <p className="text-xs text-slate-400">Versión: {man.Version}</p>
              </div>
              <a href={man.RutaArchivo} target="_blank" rel="noreferrer" className="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-semibold hover:bg-slate-800">
                Ver Manual
              </a>
            </div>
          ))}
        </div>
      )}

      {/* Tab: Links */}
      {activeTab === 'links' && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {homeData.links.map((link) => (
            <a
              key={link.Id}
              href={link.Url}
              target="_blank"
              rel="noreferrer"
              className="p-5 rounded-3xl border border-slate-200 bg-white shadow-sm hover:border-orange-500 hover:shadow-md transition flex items-center justify-between"
            >
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center font-bold">
                  🔗
                </div>
                <span className="font-bold text-slate-800">{link.Titulo}</span>
              </div>
              <ExternalLink size={16} className="text-slate-400" />
            </a>
          ))}
        </div>
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