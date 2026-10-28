-- Rol "Usuario Pruebas": mismos permisos que Usuario Estándar, pero entra al módulo interno /gestion
INSERT INTO gcp_roles (Nombre, Codigo, Descripcion, NivelAcceso, Estado)
SELECT 'Usuario Pruebas', 'usuario_pruebas', 'Igual que usuario estándar, con acceso al módulo interno de Gestión Tecnológica', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM gcp_roles WHERE Codigo = 'usuario_pruebas');

INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT nuevo.Id, rp.PermisoId
FROM gcp_roles nuevo
INNER JOIN gcp_roles estandar ON estandar.Codigo = 'usuario'
INNER JOIN gcp_rolespermisos rp ON rp.RolId = estandar.Id
WHERE nuevo.Codigo = 'usuario_pruebas'
  AND NOT EXISTS (
      SELECT 1 FROM gcp_rolespermisos existente
      WHERE existente.RolId = nuevo.Id AND existente.PermisoId = rp.PermisoId
  );

-- Fondo de Reposición ya no se desarrollará en este sistema
DELETE rp FROM gcp_rolespermisos rp
INNER JOIN gcp_permisos p ON p.Id = rp.PermisoId
WHERE p.CodigoUnico LIKE 'fondo\_reposicion.%';

DELETE FROM gcp_permisos WHERE CodigoUnico LIKE 'fondo\_reposicion.%';

DELETE FROM gcp_aplicaciones WHERE Codigo = 'fondo_reposicion';
