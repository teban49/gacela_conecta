import api from '../../../services/api';

export const securityAPI = {
  // Applications
  getApplications: () => api.get('/security/applications'),
  getApplicationById: (id) => api.get(`/security/applications/${id}`),
  createApplication: (data) => api.post('/security/applications', data),
  updateApplication: (id, data) => api.put(`/security/applications/${id}`, data),
  changeApplicationStatus: (id, status) => api.patch(`/security/applications/${id}/status`, { Estado: status }),
  changeApplicationVisibility: (id, visible) => api.patch(`/security/applications/${id}/visibility`, { Visible: visible }),

  // Permissions
  getPermissions: () => api.get('/security/permissions'),
  getPermissionsByApplication: (applicationId) => api.get(`/security/permissions/application/${applicationId}`),
  createPermission: (data) => api.post('/security/permissions', data),
  updatePermission: (id, data) => api.put(`/security/permissions/${id}`, data),
  changePermissionStatus: (id, status) => api.patch(`/security/permissions/${id}/status`, { Estado: status }),

  // Roles
  getRoles: () => api.get('/security/roles'),
  getRoleById: (roleId) => api.get(`/security/roles/${roleId}`),
  createRole: (data) => api.post('/security/roles', data),
  updateRole: (id, data) => api.put(`/security/roles/${id}`, data),
  changeRoleStatus: (id, status) => api.patch(`/security/roles/${id}/status`, { Estado: status }),
  syncRolePermissions: (roleId, permissionIds) => api.post(`/security/roles/${roleId}/permissions`, { Permisos: permissionIds }),

  // Users
  getUsers: () => api.get('/security/users'),
  getJobTitles: () => api.get('/security/job-titles'),
  getUserById: (id) => api.get(`/security/users/${id}`),
  getUserPermissions: (id) => api.get(`/security/users/${id}/permissions`),
  createUser: (data) => api.post('/security/users', data),
  updateUser: (id, data) => api.put(`/security/users/${id}`, data),
  changeUserStatus: (id, status) => api.patch(`/security/users/${id}/status`, { Estado: status }),
  setUserRole: (userId, roleId) => api.post(`/security/users/${userId}/roles`, { RolId: roleId }),
};
