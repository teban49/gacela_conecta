export const GESTION_EXTERNAL_URL = 'https://informationserver.flotamagdalena.com/index.php';

const roleCodes = (user) => (Array.isArray(user?.roles) ? user.roles : [])
  .flatMap((r) => (typeof r === 'object' ? [r.Codigo, r.Nombre] : [r]));

// Solo el administrador y el rol "usuario_pruebas" entran al módulo interno /gestion;
// el resto de roles usa el sistema externo.
export const usesExternalGestion = (user) => {
  if (!user) return false;
  const codes = roleCodes(user);
  if (codes.some((c) => ['admin', 'superadmin', 'Administrador', 'usuario_pruebas', 'Usuario Pruebas'].includes(c))) {
    return false;
  }
  return !(user.permissions ?? []).includes('gestion.ver');
};
