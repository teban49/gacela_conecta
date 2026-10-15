INSERT IGNORE INTO gcp_rolespermisos (RolId, PermisoId)
SELECT r.Id, p.Id
FROM gcp_roles r
CROSS JOIN gcp_permisos p
WHERE r.Estado = 1
  AND p.Estado = 1
  AND p.CodigoUnico IN ('solicitudes.ver', 'solicitudes.crear');
