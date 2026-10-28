const ALL_AREAS_ROLES = ['admin', 'gerencia', 'directivos'];

// Áreas de Calidad visibles por rol; "gestion-humana" la ven todos. Debe coincidir con RoleAccess.php.
const AREAS_BY_ROLE = {
  operaciones: ['mantenimiento', 'torre-control'],
  vinculaciones: ['vinculaciones'],
  juridica: [],
  tesoreria: ['compras-proveedores'],
  contabilidad: [],
  rrhh: ['calidad'],
  usuario: ['servicio-cliente'],
  usuario_pruebas: ['servicio-cliente'],
};

const roleCodes = (user) => (Array.isArray(user?.roles) ? user.roles : [])
  .map((r) => (typeof r === 'object' ? r.Codigo : r));

export const canViewQualityArea = (user, areaId) => {
  const roles = roleCodes(user);
  if (roles.some((r) => ALL_AREAS_ROLES.includes(r))) return true;
  if (areaId === 'gestion-humana') return true;
  return roles.some((r) => (AREAS_BY_ROLE[r] ?? []).includes(areaId));
};
