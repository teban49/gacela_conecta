-- Elimina gcp_areas: cargos y licencias dependen del rol.

ALTER TABLE gcp_cargos ADD COLUMN IF NOT EXISTS RolId INT NULL;
ALTER TABLE gestion_licencias ADD COLUMN IF NOT EXISTS RolId INT NULL;

SET @has_areas = (SELECT COUNT(*) FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcp_areas');

SET @map_sql = "UPDATE gcp_cargos c
    INNER JOIN gcp_areas ar ON ar.Id = c.AreaId
    SET c.RolId = (SELECT r.Id FROM gcp_roles r WHERE r.Codigo = CASE ar.Nombre
        WHEN 'Gerencia' THEN 'gerencia'
        WHEN 'Directivos' THEN 'directivos'
        WHEN 'Sistemas' THEN 'admin'
        WHEN 'Contabilidad' THEN 'contabilidad'
        WHEN 'Tesorería' THEN 'tesoreria'
        WHEN 'Operaciones y Torre de Control' THEN 'operaciones'
        WHEN 'Mantenimiento' THEN 'operaciones'
        WHEN 'Jurídica' THEN 'juridica'
        WHEN 'Gestión Humana' THEN 'rrhh'
        ELSE 'usuario' END LIMIT 1)
    WHERE c.RolId IS NULL";
SET @sql = IF(@has_areas = 1 AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcp_cargos' AND COLUMN_NAME = 'AreaId') = 1, @map_sql, 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @lic_sql = "UPDATE gestion_licencias l
    INNER JOIN gcp_areas ar ON ar.Id = l.AreaId
    SET l.RolId = (SELECT r.Id FROM gcp_roles r WHERE r.Codigo = CASE ar.Nombre
        WHEN 'Gerencia' THEN 'gerencia'
        WHEN 'Directivos' THEN 'directivos'
        WHEN 'Sistemas' THEN 'admin'
        WHEN 'Contabilidad' THEN 'contabilidad'
        WHEN 'Tesorería' THEN 'tesoreria'
        WHEN 'Operaciones y Torre de Control' THEN 'operaciones'
        WHEN 'Mantenimiento' THEN 'operaciones'
        WHEN 'Jurídica' THEN 'juridica'
        WHEN 'Gestión Humana' THEN 'rrhh'
        ELSE 'usuario' END LIMIT 1)
    WHERE l.RolId IS NULL";
SET @sql = IF(@has_areas = 1 AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gestion_licencias' AND COLUMN_NAME = 'AreaId') = 1, @lic_sql, 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE gcp_cargos SET RolId = (SELECT Id FROM gcp_roles WHERE Codigo = 'usuario' LIMIT 1) WHERE RolId IS NULL;
UPDATE gestion_licencias SET RolId = (SELECT Id FROM gcp_roles WHERE Codigo = 'admin' LIMIT 1) WHERE RolId IS NULL;

ALTER TABLE gcp_cargos DROP FOREIGN KEY IF EXISTS fk_cargos_area;
ALTER TABLE gestion_licencias DROP FOREIGN KEY IF EXISTS fk_gestion_licencias_area;
ALTER TABLE gcp_cargos DROP INDEX IF EXISTS uk_area_codigo;
ALTER TABLE gcp_cargos DROP INDEX IF EXISTS idx_area;
ALTER TABLE gcp_cargos DROP COLUMN IF EXISTS AreaId;
ALTER TABLE gestion_licencias DROP COLUMN IF EXISTS AreaId;

ALTER TABLE gcp_cargos MODIFY COLUMN RolId INT NOT NULL;
ALTER TABLE gcp_cargos ADD UNIQUE KEY IF NOT EXISTS uk_rol_codigo (RolId, Codigo);
ALTER TABLE gcp_cargos DROP FOREIGN KEY IF EXISTS fk_cargos_rol;
ALTER TABLE gcp_cargos ADD CONSTRAINT fk_cargos_rol FOREIGN KEY (RolId) REFERENCES gcp_roles(Id) ON DELETE RESTRICT;

DROP TABLE IF EXISTS gcp_areas;

-- Re-copia el organigrama de Flota (empresa 1) a las otras empresas, sin gerente ni Tesorería.
-- Solo se ejecuta la primera vez (cuando gcp_areas todavía existía) para no pisar ediciones posteriores.
SET @del_sql = IF(@has_areas = 1,
    "DELETE d FROM GCP_Intranet_Organigrama_Puestos d INNER JOIN GCP_Intranet_Empresas e ON e.Id = d.EmpresaId WHERE d.Nivel = 'area' AND e.Codigo <> 'flota_magdalena'",
    'SELECT 1');
PREPARE s FROM @del_sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @ins_sql = IF(@has_areas = 1,
    "INSERT INTO GCP_Intranet_Organigrama_Puestos (EmpresaId, Nivel, Area, Fila, Nombre, Cargo, Orden)
     SELECT dest.Id, p.Nivel, p.Area, p.Fila, p.Nombre, p.Cargo, p.Orden
     FROM GCP_Intranet_Organigrama_Puestos p
     INNER JOIN GCP_Intranet_Empresas src ON src.Id = p.EmpresaId AND src.Codigo = 'flota_magdalena'
     INNER JOIN GCP_Intranet_Empresas dest ON dest.Codigo <> 'flota_magdalena' AND dest.Estado = 1
     WHERE p.Nivel = 'area' AND p.Estado = 1 AND p.Area <> 'tesoreria'",
    'SELECT 1');
PREPARE s FROM @ins_sql; EXECUTE s; DEALLOCATE PREPARE s;
