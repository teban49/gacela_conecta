ALTER TABLE gestion_activos_asignaciones
    ADD COLUMN IF NOT EXISTS Observacion TEXT NULL AFTER AsignadoPorUsuarioId;

ALTER TABLE gestion_tickets
    ADD COLUMN IF NOT EXISTS Subcategoria VARCHAR(150) NULL AFTER CategoriaId;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_mantenimiento'
       AND INDEX_NAME = 'uq_mantenimiento_periodo') > 0,
    'ALTER TABLE gestion_mantenimiento DROP INDEX uq_mantenimiento_periodo',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_mantenimiento'
       AND INDEX_NAME = 'uq_mantenimiento_fecha') = 0,
    'CREATE UNIQUE INDEX uq_mantenimiento_fecha ON gestion_mantenimiento (ActivoId, FechaProgramada)',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

CREATE TABLE IF NOT EXISTS gestion_tickets_subcategorias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    CategoriaId INT NOT NULL,
    Nombre VARCHAR(150) NOT NULL,
    Estado TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_gestion_subcategoria (CategoriaId, Nombre),
    CONSTRAINT fk_gestion_subcategoria_categoria
        FOREIGN KEY (CategoriaId) REFERENCES gestion_tickets_categorias(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE gestion_activos_categorias SET Estado = 0;
INSERT INTO gestion_activos_categorias (Nombre, Estado)
SELECT tipos.Nombre, 1
FROM (
    SELECT 'Accesorio Agenda Telefono IP' AS Nombre
    UNION ALL SELECT 'All In One'
    UNION ALL SELECT 'Celular'
    UNION ALL SELECT 'Computador portatil'
    UNION ALL SELECT 'Contadora de billetes'
    UNION ALL SELECT 'CPU'
    UNION ALL SELECT 'Diadema auriculares USB'
    UNION ALL SELECT 'DVR/NVR'
    UNION ALL SELECT 'Impresora multifuncional'
    UNION ALL SELECT 'Impresora termica'
    UNION ALL SELECT 'Lector biometrico'
    UNION ALL SELECT 'Lector de codigo de barras'
    UNION ALL SELECT 'Microfono 360'
    UNION ALL SELECT 'Microtik'
    UNION ALL SELECT 'Modulo extension grandstream'
    UNION ALL SELECT 'Monitor'
    UNION ALL SELECT 'PDA'
    UNION ALL SELECT 'Profundimetro digital para llantas'
    UNION ALL SELECT 'Radio comunicador'
    UNION ALL SELECT 'Router'
    UNION ALL SELECT 'Switch'
    UNION ALL SELECT 'Tablet'
    UNION ALL SELECT 'Telefono IP'
    UNION ALL SELECT 'Telefono IP de conferencia'
    UNION ALL SELECT 'TV'
    UNION ALL SELECT 'UPS'
) tipos
LEFT JOIN gestion_activos_categorias existente ON existente.Nombre = tipos.Nombre
WHERE existente.Id IS NULL;
UPDATE gestion_activos_categorias
SET Estado = 1
WHERE Nombre IN (
    'Accesorio Agenda Telefono IP', 'All In One', 'Celular', 'Computador portatil',
    'Contadora de billetes', 'CPU', 'Diadema auriculares USB', 'DVR/NVR',
    'Impresora multifuncional', 'Impresora termica', 'Lector biometrico',
    'Lector de codigo de barras', 'Microfono 360', 'Microtik',
    'Modulo extension grandstream', 'Monitor', 'PDA',
    'Profundimetro digital para llantas', 'Radio comunicador', 'Router',
    'Switch', 'Tablet', 'Telefono IP', 'Telefono IP de conferencia', 'TV', 'UPS'
);

UPDATE gestion_proveedores SET Estado = 0;
INSERT INTO gestion_proveedores (Nombre, Categoria, Estado)
SELECT proveedores.Nombre, 'hardware', 1
FROM (
    SELECT 'African.com' AS Nombre
    UNION ALL SELECT 'Alkosto'
    UNION ALL SELECT 'Cas System'
    UNION ALL SELECT 'Claro'
    UNION ALL SELECT 'Movistar'
    UNION ALL SELECT 'Sannic Tecnology'
    UNION ALL SELECT 'Sin identificar'
    UNION ALL SELECT 'Tigo/UNE'
) proveedores
LEFT JOIN gestion_proveedores existente ON existente.Nombre = proveedores.Nombre
WHERE existente.Id IS NULL;
UPDATE gestion_proveedores
SET Estado = 1
WHERE Nombre IN (
    'African.com', 'Alkosto', 'Cas System', 'Claro',
    'Movistar', 'Sannic Tecnology', 'Sin identificar', 'Tigo/UNE'
);

UPDATE gestion_tickets_categorias SET Estado = 0;
INSERT INTO gestion_tickets_categorias (Nombre, Estado)
SELECT categorias.Nombre, 1
FROM (
    SELECT 'Software' AS Nombre
    UNION ALL SELECT 'Hardware'
    UNION ALL SELECT 'Buses'
    UNION ALL SELECT 'Insumo'
    UNION ALL SELECT 'Información'
    UNION ALL SELECT 'Gestión de líneas Claro'
) categorias
LEFT JOIN gestion_tickets_categorias existente ON existente.Nombre = categorias.Nombre
WHERE existente.Id IS NULL;
UPDATE gestion_tickets_categorias
SET Estado = 1
WHERE Nombre IN ('Software', 'Hardware', 'Buses', 'Insumo', 'Información', 'Gestión de líneas Claro');

INSERT INTO gestion_tickets_subcategorias (CategoriaId, Nombre)
SELECT categoria.Id, subcategoria.Nombre
FROM gestion_tickets_categorias categoria
INNER JOIN (
    SELECT 'Software' AS Categoria, 'FICS' AS Nombre
    UNION ALL SELECT 'Software', 'PXP'
    UNION ALL SELECT 'Software', 'Creación/Modificación Usuario'
    UNION ALL SELECT 'Software', 'Desactivación de Usuarios'
    UNION ALL SELECT 'Buses', 'Wifi buses'
    UNION ALL SELECT 'Buses', 'Revisión usuario GPS Prosegur'
    UNION ALL SELECT 'Buses', 'Reposición de Simcard Wifi Bus'
    UNION ALL SELECT 'Buses', 'Soporte Wifi-Bus'
    UNION ALL SELECT 'Hardware', 'Monitor'
    UNION ALL SELECT 'Hardware', 'CPU'
    UNION ALL SELECT 'Hardware', 'Impresora'
    UNION ALL SELECT 'Hardware', 'Conectividad'
    UNION ALL SELECT 'Hardware', 'Solicitud elementos de trabajo'
    UNION ALL SELECT 'Hardware', 'Solicitud Periféricos'
    UNION ALL SELECT 'Hardware', 'Portátil'
    UNION ALL SELECT 'Hardware', 'Préstamo Periféricos'
    UNION ALL SELECT 'Hardware', 'All In One'
    UNION ALL SELECT 'Hardware', 'Asignación de Celular Corporativo'
    UNION ALL SELECT 'Hardware', 'Consumibles de impresora'
    UNION ALL SELECT 'Información', 'Estadística despachos'
    UNION ALL SELECT 'Información', 'Consulta de información pasajeros'
    UNION ALL SELECT 'Información', 'Cargue archivo plano contabilidad'
    UNION ALL SELECT 'Información', 'Requerimiento Gerencia Jurídica'
    UNION ALL SELECT 'Información', 'Requerimiento Gerencia Financiera'
    UNION ALL SELECT 'Información', 'Requerimiento Gerencia Asistente'
    UNION ALL SELECT 'Información', 'Capacitación Personal Nuevo'
    UNION ALL SELECT 'Información', 'Reportes'
    UNION ALL SELECT 'Gestión de líneas Claro', 'Servicio WIFI'
    UNION ALL SELECT 'Gestión de líneas Claro', 'Solicitud Nuevo Línea Celular'
    UNION ALL SELECT 'Gestión de líneas Claro', 'Trámites Líneas Claro'
) subcategoria ON subcategoria.Categoria = categoria.Nombre
WHERE categoria.Estado = 1
ON DUPLICATE KEY UPDATE Estado = 1;
