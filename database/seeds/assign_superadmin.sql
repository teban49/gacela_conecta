-- Seed: Asignar Super Admin a usuarios ID 1 y 2
-- Description: Asignar todos los permisos al rol Administrador y asignar ese rol a los usuarios 1 y 2
-- Version: 1.0
-- Date: 2026-08-05

-- Asignar TODOS los permisos al rol Administrador (ID = 1)
INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT 1, Id FROM gcp_permisos
WHERE Id NOT IN (SELECT PermisoId FROM gcp_rolespermisos WHERE RolId = 1);

-- Asignar rol Administrador (ID = 1) al usuario ID = 1
UPDATE gcp_usuarios SET RolId = 1 WHERE Id = 1;

-- Asignar rol Administrador (ID = 1) al usuario ID = 2
UPDATE gcp_usuarios SET RolId = 1 WHERE Id = 2;
