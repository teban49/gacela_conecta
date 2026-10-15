import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8001';

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;
    
    if (error.response?.status === 401) {
      // Token expired or invalid, clear local storage
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user_data');
      window.location.href = '/login';
    }
    
    if (error.response?.status === 403) {
      // Forbidden - no permissions
      const message = error.response?.data?.message || 'No tienes permisos para realizar esta acción';
      alert(message);
    }
    
    if (error.response?.status === 422) {
      // Validation error
      const errors = error.response?.data?.errors || {};
      const errorMessages = Object.values(errors).flat();
      if (errorMessages.length > 0) {
        alert(errorMessages.join('\n'));
      }
    }
    
    if (error.response?.status === 404) {
      const message = error.response?.data?.message || 'Recurso no encontrado';
      alert(message);
    }
    
    if (error.response?.status === 500) {
      const message = error.response?.data?.message || 'Error interno del servidor';
      alert(message);
    }
    
    return Promise.reject(error);
  }
);

export const authAPI = {
  requestMagicLink: (email) => api.post('/auth/magic-link', { email }),
  verifyToken: (token) => api.post('/auth/verify-token', { token }),
  getMe: () => api.get('/auth/me'),
  logout: () => api.post('/auth/logout'),
};

export default api;
