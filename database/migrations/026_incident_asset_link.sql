-- Vincula cada incidencia con el activo del usuario solicitante
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gestion_tickets' AND COLUMN_NAME = 'ActivoId'
);
SET @ddl := IF(@col_exists = 0,
    'ALTER TABLE gestion_tickets ADD COLUMN ActivoId INT NULL, ADD INDEX idx_tickets_activo (ActivoId)',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
