SET @drop_fecha_cambio_estado = IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_tickets'
       AND COLUMN_NAME = 'FechaCambioEstado') > 0,
    'ALTER TABLE gestion_tickets DROP COLUMN FechaCambioEstado',
    'SELECT 1'
);
PREPARE drop_fecha_cambio_estado_stmt FROM @drop_fecha_cambio_estado;
EXECUTE drop_fecha_cambio_estado_stmt;
DEALLOCATE PREPARE drop_fecha_cambio_estado_stmt;

UPDATE gcp_usuarios u
INNER JOIN gcp_cargos c ON c.Id = u.CargoId
SET u.Usuario = REGEXP_REPLACE(u.Cedula, '[^0-9]', '')
WHERE COALESCE(TRIM(u.EmailCorporativo), '') = ''
  AND LOWER(c.Nombre) NOT LIKE 'taquill%'
  AND REGEXP_REPLACE(COALESCE(u.Cedula, ''), '[^0-9]', '') <> ''
  AND (u.Usuario IS NULL OR u.Usuario <> REGEXP_REPLACE(u.Cedula, '[^0-9]', ''));
