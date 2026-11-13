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
  uploadLibraryDocument: (kind, values) => {
    const body = new FormData();
    body.append('Empresa', values.Empresa);
    body.append('Categoria', values.Categoria ?? '');
    body.append('Titulo', values.Titulo);
    body.append('Archivo', values.Archivo);
    return api.post(`/intranet/libraries/${encodeURIComponent(kind)}/documents`, body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  deleteLibraryDocument: (kind, values) => api.delete(
    `/intranet/libraries/${encodeURIComponent(kind)}/documents`,
    { data: values },
  ),
  verCalidadDocumento: (empresaId, area, archivo, categoria = '') => api.get(
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
  deleteNoticia: (id) => api.delete(`/intranet/noticias/${id}`),
  saveManual: (id, values) => {
    const body = new FormData();
    body.append('Titulo', values.Titulo);
    body.append('Categoria', values.Categoria);
    body.append('Version', values.Version ?? '');
    if (values.Archivo) body.append('Archivo', values.Archivo);
    return api.post(id ? `/intranet/manuales/${encodeURIComponent(id)}` : '/intranet/manuales', body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  deleteManual: (id) => api.delete(`/intranet/manuales/${encodeURIComponent(id)}`),
  changeDocumentoStatus: (id, estado) => api.patch(`/intranet/documentos/${id}/status`, { Estado: estado }),
};
