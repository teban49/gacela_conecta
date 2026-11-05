import api from '../../../services/api';

export const horarioAPI = {
  getReport: (desde, hasta) => api.get('/horario', { params: { desde, hasta } }),
  importAttendance: (registros) => api.post('/horario/importar', { registros }),
};
