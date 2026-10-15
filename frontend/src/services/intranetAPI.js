import api from './api';

export const intranetAPI = {
  getHomeData: () => api.get('/intranet/home'),
  getDocumentos: (params) => api.get('/intranet/documentos', { params }),
  createDocumento: (data) => api.post('/intranet/documentos', data),
  updateDocumentoVersion: (id, version, rutaArchivo) => api.put(`/intranet/documentos/${id}/version`, { Version: version, RutaArchivo: rutaArchivo }),
  getOrganigrama: () => api.get('/intranet/organigrama'),
  upsertOrganigramaNodo: (data) => api.post('/intranet/organigrama', data),
  createNoticia: (data) => api.post('/intranet/noticias', data),
};
