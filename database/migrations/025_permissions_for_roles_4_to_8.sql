INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT role.Id, permission.Id
FROM gcp_roles role
INNER JOIN gcp_permisos permission
    ON permission.CodigoUnico IN (
        'dashboard.ver',
        'intranet.ver',
        'solicitudes.ver',
        'solicitudes.crear',
        'gestion.incidencias.ver'
    )
WHERE role.Id BETWEEN 4 AND 8
  AND role.Estado = 1
  AND permission.Estado = 1
  AND NOT EXISTS (
      SELECT 1
      FROM gcp_rolespermisos existing_role_permission
      WHERE existing_role_permission.RolId = role.Id
        AND existing_role_permission.PermisoId = permission.Id
  );
