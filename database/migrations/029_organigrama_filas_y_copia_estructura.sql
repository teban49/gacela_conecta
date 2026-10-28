-- Fila: puestos con la misma fila se muestran uno al lado del otro; filas distintas se apilan.
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'GCP_Intranet_Organigrama_Puestos'
      AND COLUMN_NAME = 'Fila'
);
SET @ddl = IF(@col_exists = 0,
    'ALTER TABLE GCP_Intranet_Organigrama_Puestos ADD COLUMN Fila INT NOT NULL DEFAULT 1 AFTER Area',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Los puestos existentes quedan uno debajo del otro, como estaban.
UPDATE GCP_Intranet_Organigrama_Puestos SET Fila = Orden WHERE Fila = 1 AND Orden > 1 AND Nivel = 'area';

-- Copia la estructura de áreas de Flota Magdalena a las demás empresas (sin gerente ni Tesorería),
-- solo si la empresa destino aún no tiene puestos de área.
INSERT INTO GCP_Intranet_Organigrama_Puestos (EmpresaId, Nivel, Area, Fila, Nombre, Cargo, Orden)
SELECT dest.Id, p.Nivel, p.Area, p.Fila, p.Nombre, p.Cargo, p.Orden
FROM GCP_Intranet_Organigrama_Puestos p
INNER JOIN GCP_Intranet_Empresas src ON src.Id = p.EmpresaId AND src.Codigo = 'flota_magdalena'
INNER JOIN GCP_Intranet_Empresas dest ON dest.Codigo <> 'flota_magdalena' AND dest.Estado = 1
WHERE p.Nivel = 'area' AND p.Estado = 1 AND p.Area <> 'tesoreria'
  AND NOT EXISTS (
      SELECT 1 FROM GCP_Intranet_Organigrama_Puestos x
      WHERE x.EmpresaId = dest.Id AND x.Nivel = 'area' AND x.Estado = 1
  );
