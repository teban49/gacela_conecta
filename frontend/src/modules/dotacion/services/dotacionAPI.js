import api from '../../../services/api';

export const dotacionAPI = {
  getDashboard: () => api.get('/dotacion'),
  getRequest: (id) => api.get(`/dotacion/${id}`),
  createRequest: (data) => api.post('/dotacion', data),
  decideRequest: (id, Decision, Comentario) =>
    api.post(`/dotacion/${id}/decision`, { Decision, Comentario }),
  markReceived: (id) => api.post(`/dotacion/${id}/recibido`),
  getDeliveries: (params) => api.get('/dotacion/entregas', { params }),
};
