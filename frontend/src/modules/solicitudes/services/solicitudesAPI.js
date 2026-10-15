import api from '../../../services/api';

export const solicitudesAPI = {
  // Categorías
  getCategorias: () => api.get('/solicitudes/categorias'),
  getActiveCategorias: () => api.get('/solicitudes/categorias/active'),
  getCategoria: (id) => api.get(`/solicitudes/categorias/${id}`),
  createCategoria: (data) => api.post('/solicitudes/categorias', data),
  updateCategoria: (id, data) => api.put(`/solicitudes/categorias/${id}`, data),
  changeCategoriaStatus: (id, estado) => api.patch(`/solicitudes/categorias/${id}/status`, { Estado: estado }),

  // Solicitudes
  getSolicitudes: () => api.get('/solicitudes'),
  getSolicitudesByStatus: (estado) => api.get(`/solicitudes/status/${estado}`),
  getSolicitudesByAssignee: (usuarioId) => api.get(`/solicitudes/assignee/${usuarioId}`),
  getSolicitudesByRequester: (usuarioId) => api.get(`/solicitudes/requester/${usuarioId}`),
  getSolicitud: (id) => api.get(`/solicitudes/${id}`),
  createSolicitud: (data) => api.post('/solicitudes', data),
  updateSolicitud: (id, data) => api.put(`/solicitudes/${id}`, data),
  changeSolicitudStatus: (id, estado) => api.patch(`/solicitudes/${id}/status`, { Estado: estado }),
  assignSolicitud: (id, usuarioId) => api.post(`/solicitudes/${id}/assign`, { UsuarioId: usuarioId }),

  // HR requests with sequential manager, HR, and management approvals
  getLaboralTypes: () => api.get('/solicitudes/laborales/tipos'),
  getMyLaboralRequests: () => api.get('/solicitudes/laborales/mis-solicitudes'),
  getPendingLaboralRequests: () => api.get('/solicitudes/laborales/pendientes'),
  getLaboralRequest: (id) => api.get(`/solicitudes/laborales/${id}`),
  createLaboralRequest: (data) => api.post('/solicitudes/laborales', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  decideLaboralRequest: (id, Decision, Comentario) =>
    api.post(`/solicitudes/laborales/${id}/decision`, { Decision, Comentario }),
  downloadLaboralAttachment: (id, attachmentId) =>
    api.get(`/solicitudes/laborales/${id}/adjuntos/${attachmentId}`, { responseType: 'blob' }),
};