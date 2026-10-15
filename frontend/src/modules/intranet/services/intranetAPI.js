import api from '../../../services/api';

export const intranetAPI = {
  getDirectorio: () => api.get('/intranet/directorio'),
  updateDirectorio: (id, data) => api.put(`/intranet/directorio/${id}`, data),
  getNoticias: () => api.get('/intranet/noticias'),
  getPublishedNoticias: () => api.get('/intranet/noticias/published'),
  getNoticiasByCategory: (categoria) => api.get(`/intranet/noticias/category/${categoria}`),
  getNoticia: (id) => api.get(`/intranet/noticias/${id}`),
  markNoticiaAsViewed: (id, usuarioId) => api.post(`/intranet/noticias/${id}/view`, { UsuarioId: usuarioId }),
  createNoticia: (data) => api.post('/intranet/noticias', data),
  updateNoticia: (id, data) => api.put(`/intranet/noticias/${id}`, data),
  changeNoticiaStatus: (id, estado) => api.patch(`/intranet/noticias/${id}/status`, { Estado: estado }),

  // Documentos
  getDocumentos: () => api.get('/intranet/documentos'),
  getDocumentosByCategory: (categoria) => api.get(`/intranet/documentos/category/${categoria}`),
  getDocumento: (id) => api.get(`/intranet/documentos/${id}`),
  downloadDocumento: (id) => api.post(`/intranet/documentos/${id}/download`),
  createDocumento: (data) => api.post('/intranet/documentos', data),
  updateDocumento: (id, data) => api.put(`/intranet/documentos/${id}`, data),
  changeDocumentoStatus: (id, estado) => api.patch(`/intranet/documentos/${id}/status`, { Estado: estado }),

  // Eventos
  getEventos: () => api.get('/intranet/eventos'),
  getEventosByDateRange: (fechaInicio, fechaFin) => api.post('/intranet/eventos/daterange', { FechaInicio: fechaInicio, FechaFin: fechaFin }),
  getUpcomingEventos: (limit = 10) => api.post('/intranet/eventos/upcoming', { limit }),
  getEvento: (id) => api.get(`/intranet/eventos/${id}`),
  createEvento: (data) => api.post('/intranet/eventos', data),
  updateEvento: (id, data) => api.put(`/intranet/eventos/${id}`, data),
  changeEventoStatus: (id, estado) => api.patch(`/intranet/eventos/${id}/status`, { Estado: estado }),
};