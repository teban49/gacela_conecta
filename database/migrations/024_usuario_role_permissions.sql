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
WHERE role.Codigo = 'usuario'
  AND role.Estado = 1
  AND permission.Estado = 1
  AND NOT EXISTS (
      SELECT 1
      FROM gcp_rolespermisos existing_role_permission
      WHERE existing_role_permission.RolId = role.Id
        AND existing_role_permission.PermisoId = permission.Id
  );

DELETE role_permission
FROM gcp_rolespermisos role_permission
INNER JOIN gcp_roles role ON role.Id = role_permission.RolId
INNER JOIN gcp_permisos permission ON permission.Id = role_permission.PermisoId
WHERE role.Codigo = 'usuario'
  AND permission.CodigoUnico NOT IN (
      'dashboard.ver',
      'intranet.ver',
      'solicitudes.ver',
      'solicitudes.crear',
      'gestion.incidencias.ver'
  );
