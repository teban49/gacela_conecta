-- Organigrama por empresa: puestos editables por área.
-- EmpresaId NULL = el puesto aparece en el organigrama de todas las empresas.
CREATE TABLE IF NOT EXISTS GCP_Intranet_Organigrama_Puestos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    EmpresaId INT NULL,
    Nivel VARCHAR(20) NOT NULL COMMENT 'nivel1, nivel2, gerencia, area',
    Area VARCHAR(50) NULL,
    Nombre VARCHAR(150) NOT NULL,
    Cargo VARCHAR(150) NULL,
    Orden INT NOT NULL DEFAULT 0,
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_org_puestos_empresa (EmpresaId, Nivel, Area)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO GCP_Intranet_Organigrama_Puestos (EmpresaId, Nivel, Area, Nombre, Cargo, Orden)
SELECT NULL, seed.Nivel, NULL, seed.Nombre, NULL, seed.Orden
FROM (
    SELECT 'nivel1' AS Nivel, 'Fruto Mejía' AS Nombre, 1 AS Orden
    UNION ALL SELECT 'nivel1', 'María Mejía', 2
    UNION ALL SELECT 'nivel2', 'María Nuñez', 1
    UNION ALL SELECT 'nivel2', 'Margarita Nuñez', 2
) seed
WHERE NOT EXISTS (SELECT 1 FROM GCP_Intranet_Organigrama_Puestos WHERE Nivel IN ('nivel1', 'nivel2'));

INSERT INTO GCP_Intranet_Organigrama_Puestos (EmpresaId, Nivel, Area, Nombre, Cargo, Orden)
SELECT e.Id, 'gerencia', NULL,
       CASE e.Codigo
           WHEN 'flota_magdalena' THEN 'John Pino'
           WHEN 'expreso_paz_rio' THEN 'Víctor Romero'
           WHEN 'rapido_duitama' THEN 'Diego Romero'
       END,
       'Gerente', 1
FROM GCP_Intranet_Empresas e
WHERE e.Codigo IN ('flota_magdalena', 'expreso_paz_rio', 'rapido_duitama')
  AND NOT EXISTS (SELECT 1 FROM GCP_Intranet_Organigrama_Puestos WHERE Nivel = 'gerencia');
