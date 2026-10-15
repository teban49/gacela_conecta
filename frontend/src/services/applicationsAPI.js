import api from './api';

export const applicationsAPI = {
  getApplications: () => api.get('/security/applications'),
  getApplicationById: (id) => api.get(`/security/applications/${id}`),
};