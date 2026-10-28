-- Roles definitivos. Los permisos se asignan por rol; todo rol distinto de admin
-- recibe el mismo conjunto base que el usuario estándar (solo lectura).

INSERT INTO gcp_roles (Nombre, Codigo, Descripcion, NivelAcceso, Estado)
SELECT seed.Nombre, seed.Codigo, seed.Descripcion, base.NivelAcceso, 1
FROM (
    SELECT 'Directivos' AS Nombre, 'directivos' AS Codigo, 'Consulta la información de todas las empresas sin editar' AS Descripcion
    UNION ALL SELECT 'Vinculaciones', 'vinculaciones', 'Consulta la información de sus empresas sin editar'
    UNION ALL SELECT 'Jurídica', 'juridica', 'Consulta la información de sus empresas sin editar'
    UNION ALL SELECT 'Contabilidad', 'contabilidad', 'Consulta la información de sus empresas sin editar'
) seed
INNER JOIN gcp_roles base ON base.Codigo = 'usuario'
WHERE NOT EXISTS (SELECT 1 FROM gcp_roles r WHERE r.Codigo = seed.Codigo);

UPDATE gcp_roles SET Nombre = 'Recursos Humanos' WHERE Codigo = 'rrhh';
UPDATE gcp_roles SET Nombre = 'Tesorería' WHERE Codigo = 'tesoreria';

-- Usuarios de roles que se eliminan: usuariop pasa a usuario_pruebas, el resto a usuario estándar.
UPDATE gcp_usuarios
SET RolId = (SELECT Id FROM (SELECT Id FROM gcp_roles WHERE Codigo = 'usuario_pruebas') x)
WHERE RolId IN (SELECT Id FROM (SELECT Id FROM gcp_roles WHERE Codigo = 'usuariop') y);

UPDATE gcp_usuarios
SET RolId = (SELECT Id FROM (SELECT Id FROM gcp_roles WHERE Codigo = 'usuario') x)
WHERE RolId IN (
    SELECT Id FROM (
        SELECT Id FROM gcp_roles
        WHERE Codigo NOT IN ('admin', 'gerencia', 'directivos', 'operaciones', 'vinculaciones', 'juridica',
                             'tesoreria', 'contabilidad', 'rrhh', 'usuario', 'usuario_pruebas')
    ) y
);

DELETE FROM gcp_roles
WHERE Codigo NOT IN ('admin', 'gerencia', 'directivos', 'operaciones', 'vinculaciones', 'juridica',
                     'tesoreria', 'contabilidad', 'rrhh', 'usuario', 'usuario_pruebas');

-- Permisos base idénticos al usuario estándar para todos los roles no administradores.
DELETE rp FROM gcp_rolespermisos rp
INNER JOIN gcp_roles r ON r.Id = rp.RolId
WHERE r.Codigo NOT IN ('admin', 'usuario');

INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT r.Id, rp.PermisoId
FROM gcp_roles r
INNER JOIN gcp_roles base ON base.Codigo = 'usuario'
INNER JOIN gcp_rolespermisos rp ON rp.RolId = base.Id
WHERE r.Codigo NOT IN ('admin', 'usuario');
