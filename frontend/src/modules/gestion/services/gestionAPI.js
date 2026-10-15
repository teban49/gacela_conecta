import api from '../../../services/api';

export const gestionAPI = {
  // Incidencias / IT tickets
  getIncidencias: () => api.get('/gestion/incidencias'),
  getCategoriasIncidencias: () => api.get('/gestion/incidencias/categorias'),
  getContextoIncidencias: () => api.get('/gestion/incidencias/contexto'),
  getUsuariosIncidencias: () => api.get('/gestion/incidencias/usuarios'),
  getIncidencia: (id) => api.get(`/gestion/incidencias/${id}`),
  createIncidencia: (data) => api.post('/gestion/incidencias', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  updateIncidencia: (id, data) => api.patch(`/gestion/incidencias/${id}`, data),
  cerrarIncidencia: (id, encuesta) => api.post(`/gestion/incidencias/${id}/cerrar`, encuesta),
  downloadIncidenciaAdjunto: (id) => api.get(`/gestion/incidencias/${id}/adjunto`, { responseType: 'blob' }),

  // Activos
  getActivos: () => api.get('/gestion/activos'),
  getCategoriasActivos: () => api.get('/gestion/activos/categorias'),
  getEmpresas: () => api.get('/gestion/empresas'),
  getProveedores: () => api.get('/gestion/proveedores'),
  createProveedor: (data) => api.post('/gestion/proveedores', data),
  getActivosByCategory: (categoriaId) => api.get(`/gestion/activos/category/${categoriaId}`),
  getActivosByAssignee: (usuarioId) => api.get(`/gestion/activos/assignee/${usuarioId}`),
  getAssignableUsers: () => api.get('/gestion/usuarios'),
  getMisAsignacionesPendientes: () => api.get('/gestion/activos/asignaciones-pendientes'),
  aceptarAsignacion: (id) => api.post(`/gestion/activos/asignaciones/${id}/aceptar`),
  rechazarAsignacion: (id) => api.post(`/gestion/activos/asignaciones/${id}/rechazar`),
  getActivo: (id) => api.get(`/gestion/activos/${id}`),
  createActivo: (data) => api.post('/gestion/activos', data),
  updateActivo: (id, data) => api.put(`/gestion/activos/${id}`, data),
  changeActivoStatus: (id, estado) => api.patch(`/gestion/activos/${id}/status`, { Estado: estado }),
  changeActivoAssetStatus: (id, estadoActivo) => api.patch(`/gestion/activos/${id}/asset-status`, { EstadoActivo: estadoActivo }),
  assignActivo: (id, usuarioId) => api.post(`/gestion/activos/${id}/assign`, { UsuarioId: usuarioId }),
  unassignActivo: (id) => api.delete(`/gestion/activos/${id}/assignment`),
  retireActivo: (id) => api.post(`/gestion/activos/${id}/retire`),

  // Mantenimiento semestral
  getMantenimientosPendientes: () => api.get('/gestion/mantenimientos/pendientes'),
  completarMantenimiento: (activoId, anio, semestre, data) => api.post(`/gestion/mantenimientos/${activoId}/anio/${anio}/semestre/${semestre}/completar`, data),

  // Licencias
  getLicencias: () => api.get('/gestion/licencias'),
  getOpcionesLicencias: () => api.get('/gestion/licencias/opciones'),
  createLicencia: (data) => api.post('/gestion/licencias', data),
  updateLicencia: (id, data) => api.put(`/gestion/licencias/${id}`, data),
};