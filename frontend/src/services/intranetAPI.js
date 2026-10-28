import api from './api';

export const intranetAPI = {
  getHomeData: () => api.get('/intranet/home'),
  getDocumentos: (params) => api.get('/intranet/documentos', { params }),
  createDocumento: (data) => api.post('/intranet/documentos', data),
  updateDocumentoVersion: (id, version, rutaArchivo) => api.put(`/intranet/documentos/${id}/version`, { Version: version, RutaArchivo: rutaArchivo }),
  getOrganigramaPuestos: () => api.get('/intranet/organigrama/puestos'),
  createOrganigramaPuesto: (data) => api.post('/intranet/organigrama/puestos', data),
  updateOrganigramaPuesto: (id, data) => api.put(`/intranet/organigrama/puestos/${id}`, data),
  deleteOrganigramaPuesto: (id) => api.delete(`/intranet/organigrama/puestos/${id}`),
  getCalidadDocumentos: (empresaId) => api.get('/intranet/calidad/documentos', { params: { EmpresaId: empresaId } }),
  descargarCalidadDocumento: (empresaId, area, archivo, categoria = '') => api.get(
    `/intranet/calidad/empresas/${empresaId}/${encodeURIComponent(area)}/${encodeURIComponent(archivo)}`,
    { responseType: 'blob', params: { Categoria: categoria } },
  ),
  subirCalidadDocumento: (empresaId, area, categoria, archivo) => {
    const body = new FormData();
    body.append('Categoria', categoria);
    body.append('Archivo', archivo);
    return api.post(`/intranet/calidad/empresas/${empresaId}/${encodeURIComponent(area)}`, body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  eliminarCalidadDocumento: (empresaId, area, archivo, categoria = '') => api.delete(
    `/intranet/calidad/empresas/${empresaId}/${encodeURIComponent(area)}/${encodeURIComponent(archivo)}`,
    { params: { Categoria: categoria } },
  ),
  createNoticia: (data) => api.post('/intranet/noticias', data, data instanceof FormData
    ? { headers: { 'Content-Type': 'multipart/form-data' } }
    : undefined),
};
