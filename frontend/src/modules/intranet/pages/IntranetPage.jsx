import React, { useState, useEffect } from 'react';
import { Plus, Edit, Trash2, Eye, Calendar, FileText, Newspaper, Calendar as CalendarIcon, Filter, Search } from 'lucide-react';
import { intranetAPI } from '../services/intranetAPI';

const IntranetPage = () => {
  const [activeTab, setActiveTab] = useState('noticias');
  const [noticias, setNoticias] = useState([]);
  const [documentos, setDocumentos] = useState([]);
  const [eventos, setEventos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');

  useEffect(() => {
    loadData();
  }, [activeTab]);

  const loadData = async () => {
    setLoading(true);
    try {
      if (activeTab === 'noticias') {
        const res = await intranetAPI.getPublishedNoticias();
        setNoticias(res.data?.data ?? []);
      } else if (activeTab === 'documentos') {
        const res = await intranetAPI.getDocumentos();
        setDocumentos(res.data?.data ?? []);
      } else if (activeTab === 'eventos') {
        const res = await intranetAPI.getUpcomingEventos(20);
        setEventos(res.data?.data ?? []);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    } finally {
      setLoading(false);
    }
  };

  const filteredNoticias = noticias.filter(n => 
    (selectedCategory === 'all' || n.Categoria === selectedCategory) &&
    (n.Titulo.toLowerCase().includes(searchTerm.toLowerCase()) || 
     n.Resumen?.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const filteredDocumentos = documentos.filter(d => 
    (selectedCategory === 'all' || d.Categoria === selectedCategory) &&
    (d.Nombre.toLowerCase().includes(searchTerm.toLowerCase()) || 
     d.Descripcion?.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const filteredEventos = eventos.filter(e => 
    (selectedCategory === 'all' || e.Tipo === selectedCategory) &&
    (e.Titulo.toLowerCase().includes(searchTerm.toLowerCase()) || 
     e.Descripcion?.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const getCategoryColor = (categoria) => {
    const colors = {
      general: 'bg-gray-100 text-gray-700',
      rrhh: 'bg-blue-100 text-blue-700',
      ti: 'bg-purple-100 text-purple-700',
      operaciones: 'bg-green-100 text-green-700',
      ventas: 'bg-orange-100 text-orange-700',
      finanzas: 'bg-yellow-100 text-yellow-700',
    };
    return colors[categoria] || 'bg-gray-100 text-gray-700';
  };

  const getPriorityColor = (prioridad) => {
    const colors = {
      baja: 'bg-green-50 text-green-700',
      media: 'bg-yellow-50 text-yellow-700',
      alta: 'bg-orange-50 text-orange-700',
      urgente: 'bg-red-50 text-red-700',
    };
    return colors[prioridad] || 'bg-gray-50 text-gray-700';
  };

  const renderNoticias = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-slate-800">Noticias y Anuncios</h2>
        <button className="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-700 transition">
          <Plus size={16} />
          Nueva Noticia
        </button>
      </div>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {filteredNoticias.map((noticia) => (
          <div key={noticia.Id} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition">
            {noticia.Imagen && (
              <div className="mb-4 rounded-xl overflow-hidden h-40 bg-slate-100">
                <img src={noticia.Imagen} alt={noticia.Titulo} className="w-full h-full object-cover" />
              </div>
            )}
            <div className="flex gap-2 mb-3">
              <span className={`rounded-full px-3 py-1 text-xs font-semibold ${getCategoryColor(noticia.Categoria)}`}>
                {noticia.Categoria}
              </span>
              <span className={`rounded-full px-3 py-1 text-xs font-semibold ${getPriorityColor(noticia.Prioridad)}`}>
                {noticia.Prioridad}
              </span>
            </div>
            <h3 className="text-lg font-bold text-slate-800 mb-2">{noticia.Titulo}</h3>
            <p className="text-sm text-slate-600 mb-4 line-clamp-2">{noticia.Resumen || noticia.Contenido}</p>
            <div className="flex justify-between items-center text-xs text-slate-500">
              <span>{noticia.AutorNombre} {noticia.AutorApellidos}</span>
              <span>{new Date(noticia.FechaPublicacion).toLocaleDateString()}</span>
            </div>
            <div className="flex gap-2 mt-4">
              <button className="flex-1 rounded-lg bg-purple-50 p-2 text-purple-600 hover:bg-purple-100 flex items-center justify-center gap-1 text-sm">
                <Eye size={14} />
                Leer
              </button>
              <button className="rounded-lg bg-blue-50 p-2 text-blue-600 hover:bg-blue-100">
                <Edit size={14} />
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  const renderDocumentos = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-slate-800">Documentos Compartidos</h2>
        <button className="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-700 transition">
          <Plus size={16} />
          Nuevo Documento
        </button>
      </div>

      <div className="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <table className="w-full">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-600">Nombre</th>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-600">Categoría</th>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-600">Versión</th>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-600">Descargas</th>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-600">Fecha</th>
              <th className="px-6 py-3 text-left text-xs font-semibold text-slate-600">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {filteredDocumentos.map((doc) => (
              <tr key={doc.Id} className="border-t border-slate-100 hover:bg-slate-50">
                <td className="px-6 py-4">
                  <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                      <FileText size={20} />
                    </div>
                    <div>
                      <div className="font-semibold text-slate-800">{doc.Nombre}</div>
                      <div className="text-xs text-slate-500">{doc.Descripcion}</div>
                    </div>
                  </div>
                </td>
                <td className="px-6 py-4">
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold ${getCategoryColor(doc.Categoria)}`}>
                    {doc.Categoria}
                  </span>
                </td>
                <td className="px-6 py-4 text-sm text-slate-600">{doc.Version}</td>
                <td className="px-6 py-4 text-sm text-slate-600">{doc.Descargas}</td>
                <td className="px-6 py-4 text-sm text-slate-600">{new Date(doc.FechaCreacion).toLocaleDateString()}</td>
                <td className="px-6 py-4">
                  <div className="flex gap-2">
                    <button className="rounded-lg bg-green-50 p-2 text-green-600 hover:bg-green-100" title="Descargar">
                      <Eye size={14} />
                    </button>
                    <button className="rounded-lg bg-blue-50 p-2 text-blue-600 hover:bg-blue-100" title="Editar">
                      <Edit size={14} />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );

  const renderEventos = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-slate-800">Calendario de Eventos</h2>
        <button className="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-700 transition">
          <Plus size={16} />
          Nuevo Evento
        </button>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        {filteredEventos.map((evento) => (
          <div key={evento.Id} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition">
            <div className="flex items-start gap-4">
              <div className="rounded-xl bg-purple-50 p-3 text-purple-600">
                <CalendarIcon size={24} />
              </div>
              <div className="flex-1">
                <div className="flex gap-2 mb-2">
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold ${getCategoryColor(evento.Tipo)}`}>
                    {evento.Tipo}
                  </span>
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold ${getPriorityColor(evento.Prioridad)}`}>
                    {evento.Prioridad}
                  </span>
                </div>
                <h3 className="text-lg font-bold text-slate-800 mb-2">{evento.Titulo}</h3>
                <p className="text-sm text-slate-600 mb-3">{evento.Descripcion}</p>
                <div className="space-y-1 text-xs text-slate-500">
                  <div className="flex items-center gap-2">
                    <Calendar size={12} />
                    <span>{new Date(evento.FechaInicio).toLocaleString()}</span>
                  </div>
                  {evento.Ubicacion && (
                    <div>📍 {evento.Ubicacion}</div>
                  )}
                  <div>👤 {evento.OrganizadorNombre} {evento.OrganizadorApellidos}</div>
                </div>
              </div>
            </div>
            <div className="flex gap-2 mt-4">
              <button className="flex-1 rounded-lg bg-purple-50 p-2 text-purple-600 hover:bg-purple-100 flex items-center justify-center gap-1 text-sm">
                Ver Detalles
              </button>
              <button className="rounded-lg bg-blue-50 p-2 text-blue-600 hover:bg-blue-100">
                <Edit size={14} />
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  return (
    <div className="p-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-slate-800 mb-2">Intranet</h1>
        <p className="text-slate-600">Comunicación interna, documentos y eventos</p>
      </div>

      <div className="flex gap-4 mb-6">
        <button
          onClick={() => setActiveTab('noticias')}
          className={`flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition ${
            activeTab === 'noticias' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          <Newspaper size={18} />
          Noticias
        </button>
        <button
          onClick={() => setActiveTab('documentos')}
          className={`flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition ${
            activeTab === 'documentos' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          <FileText size={18} />
          Documentos
        </button>
        <button
          onClick={() => setActiveTab('eventos')}
          className={`flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition ${
            activeTab === 'eventos' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          <Calendar size={18} />
          Eventos
        </button>
      </div>

      <div className="flex gap-4 mb-6">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" size={18} />
          <input
            type="text"
            placeholder="Buscar..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500"
          />
        </div>
        <select
          value={selectedCategory}
          onChange={(e) => setSelectedCategory(e.target.value)}
          className="px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500"
        >
          <option value="all">Todas las categorías</option>
          <option value="general">General</option>
          <option value="rrhh">Recursos Humanos</option>
          <option value="ti">Tecnología</option>
          <option value="operaciones">Operaciones</option>
          <option value="ventas">Ventas</option>
          <option value="finanzas">Finanzas</option>
        </select>
      </div>

      {loading ? (
        <div className="text-center py-12 text-slate-500">Cargando...</div>
      ) : (
        <>
          {activeTab === 'noticias' && renderNoticias()}
          {activeTab === 'documentos' && renderDocumentos()}
          {activeTab === 'eventos' && renderEventos()}
        </>
      )}
    </div>
  );
};

export default IntranetPage;