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
  getLaboralCompanies: (beneficiarioId) => api.get('/solicitudes/laborales/empresas', {
    params: beneficiarioId ? { BeneficiarioId: beneficiarioId } : {},
  }),
  getLaboralBeneficiaries: () => api.get('/solicitudes/laborales/beneficiarios'),
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

  // Liquidación de nómina tras la aprobación final
  getPayrollRequests: () => api.get('/solicitudes/laborales/nomina'),
  uploadPayrollDocument: (id, file) => {
    const body = new FormData();
    body.append('Documento', file);
    return api.post(`/solicitudes/laborales/${id}/nomina`, body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  reviewPayroll: (id, Decision, Comentario) =>
    api.post(`/solicitudes/laborales/${id}/nomina/revision`, { Decision, Comentario }),
  markPayrollPaid: (id) => api.post(`/solicitudes/laborales/${id}/nomina/pagar`),
  downloadPayrollDocument: (id, documentId) =>
    api.get(`/solicitudes/laborales/${id}/nomina/${documentId}`, { responseType: 'blob' }),
};