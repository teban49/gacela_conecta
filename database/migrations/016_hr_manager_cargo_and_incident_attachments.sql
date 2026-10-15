ALTER TABLE gcp_usuarios
    ADD COLUMN IF NOT EXISTS CargoJefeDirectoId INT NULL AFTER CargoId;

ALTER TABLE gcp_solicitudes
    ADD COLUMN IF NOT EXISTS AprobadorCargoId INT NULL AFTER UsuarioId;

ALTER TABLE gestion_tickets
    ADD COLUMN IF NOT EXISTS ArchivoAdjuntoNombre VARCHAR(255) NULL AFTER ArchivoAdjunto;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcp_usuarios' AND COLUMN_NAME = 'JefeDirectoId') > 0,
    'UPDATE gcp_usuarios u INNER JOIN gcp_usuarios j ON j.Id = u.JefeDirectoId SET u.CargoJefeDirectoId = j.CargoId WHERE u.JefeDirectoId IS NOT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcp_solicitudes' AND COLUMN_NAME = 'AprobadorJefeId') > 0,
    'UPDATE gcp_solicitudes s INNER JOIN gcp_usuarios j ON j.Id = s.AprobadorJefeId SET s.AprobadorCargoId = j.CargoId WHERE s.AprobadorJefeId IS NOT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

ALTER TABLE gcp_usuarios
    DROP COLUMN IF EXISTS JefeDirectoId;

ALTER TABLE gcp_solicitudes
    DROP COLUMN IF EXISTS AprobadorJefeId;
