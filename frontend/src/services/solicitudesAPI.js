import api from './api';

export const solicitudesAPI = {
  getTipos: () => api.get('/solicitudes/tipos'),
  getMisSolicitudes: () => api.get('/solicitudes/mis-solicitudes'),
  getPendientes: (nivel) => api.get(`/solicitudes/pendientes/${nivel}`),
  getDetalle: (id) => api.get(`/solicitudes/${id}`),
  crearSolicitud: (data) => api.post('/solicitudes', data),
  aprobarSolicitud: (id, comentario) => api.post(`/solicitudes/${id}/aprobar`, { Comentario: comentario }),
  rechazarSolicitud: (id, motivo) => api.post(`/solicitudes/${id}/rechazar`, { Motivo: motivo }),
};
