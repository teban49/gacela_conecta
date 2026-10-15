SET @gestion_app_id = (
    SELECT AplicacionId
    FROM gcp_permisos
    WHERE CodigoUnico = 'gestion.ver'
    LIMIT 1
);

INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado)
SELECT @gestion_app_id, 'gestion', 'Ver incidencias', 'Consultar y crear incidencias propias', 'gestion.incidencias.ver', 1
WHERE @gestion_app_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM gcp_permisos WHERE CodigoUnico = 'gestion.incidencias.ver'
  );

INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT role.Id, permission.Id
FROM gcp_roles role
INNER JOIN gcp_permisos permission
    ON permission.CodigoUnico = 'gestion.incidencias.ver'
WHERE role.Estado = 1
  AND (
      role.Codigo = 'usuario'
      OR EXISTS (
          SELECT 1
          FROM gcp_rolespermisos role_permission
          INNER JOIN gcp_permisos existing_permission ON existing_permission.Id = role_permission.PermisoId
          WHERE role_permission.RolId = role.Id
            AND existing_permission.CodigoUnico IN (
                'gestion.ver',
                'gestion.administrar_incidencias'
            )
      )
  )
  AND NOT EXISTS (
      SELECT 1
      FROM gcp_rolespermisos existing_role_permission
      WHERE existing_role_permission.RolId = role.Id
        AND existing_role_permission.PermisoId = permission.Id
  );

INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT role.Id, permission.Id
FROM gcp_roles role
INNER JOIN gcp_permisos permission
    ON permission.CodigoUnico IN ('intranet.ver', 'solicitudes.ver', 'solicitudes.crear')
WHERE role.Estado = 1
  AND role.Codigo = 'usuario'
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
  AND (
      permission.CodigoUnico = 'gestion.ver'
      OR permission.CodigoUnico LIKE 'gestion.administrar_%'
  );

DELETE role_permission
FROM gcp_rolespermisos role_permission
INNER JOIN gcp_roles role ON role.Id = role_permission.RolId
INNER JOIN gcp_permisos permission ON permission.Id = role_permission.PermisoId
WHERE role.Codigo = 'usuario'
  AND (
      (permission.CodigoUnico LIKE 'intranet.%' AND permission.CodigoUnico <> 'intranet.ver')
      OR (permission.CodigoUnico LIKE 'solicitudes.%'
          AND permission.CodigoUnico NOT IN ('solicitudes.ver', 'solicitudes.crear'))
  );
