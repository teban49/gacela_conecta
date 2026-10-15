ALTER TABLE gcp_usuarios
    ADD COLUMN IF NOT EXISTS RolId INT NULL AFTER CargoId;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcp_usuariosroles') > 0,
    'UPDATE gcp_usuarios u
     SET u.RolId = (
         SELECT ur.RolId
         FROM gcp_usuariosroles ur
         INNER JOIN gcp_roles r ON r.Id = ur.RolId
         WHERE ur.UsuarioId = u.Id
         ORDER BY CASE r.Codigo
             WHEN ''admin'' THEN 1
             WHEN ''gerencia'' THEN 2
             WHEN ''rrhh'' THEN 3
             WHEN ''usuario'' THEN 4
             ELSE 5
         END, ur.RolId
         LIMIT 1
     )
     WHERE u.RolId IS NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

UPDATE gcp_usuarios
SET RolId = (SELECT Id FROM gcp_roles WHERE Codigo = 'usuario' AND Estado = 1 LIMIT 1)
WHERE RolId IS NULL
  AND EXISTS (SELECT 1 FROM gcp_roles WHERE Codigo = 'usuario' AND Estado = 1);

ALTER TABLE gcp_usuarios
    MODIFY COLUMN RolId INT NOT NULL;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gcp_usuarios'
       AND CONSTRAINT_NAME = 'fk_gcp_usuarios_rol') = 0,
    'ALTER TABLE gcp_usuarios
     ADD CONSTRAINT fk_gcp_usuarios_rol
     FOREIGN KEY (RolId) REFERENCES gcp_roles(Id)
     ON UPDATE CASCADE ON DELETE RESTRICT',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

DROP TABLE IF EXISTS gcp_usuariosroles;
