ALTER TABLE gestion_activos
    ADD COLUMN IF NOT EXISTS EmpresaId INT NULL AFTER CategoriaId,
    ADD COLUMN IF NOT EXISTS TipoPropiedad ENUM('propio', 'arriendo') NULL AFTER EmpresaId,
    ADD COLUMN IF NOT EXISTS ProveedorId INT NULL AFTER TipoPropiedad,
    ADD COLUMN IF NOT EXISTS NumeroFacturaContrato VARCHAR(150) NULL AFTER ProveedorId,
    ADD COLUMN IF NOT EXISTS FechaInicio DATE NULL AFTER NumeroFacturaContrato,
    ADD COLUMN IF NOT EXISTS FechaFin DATE NULL AFTER FechaInicio,
    ADD COLUMN IF NOT EXISTS Placa VARCHAR(100) NULL AFTER FechaFin,
    ADD COLUMN IF NOT EXISTS Observaciones TEXT NULL AFTER Placa;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gestion_activos' AND COLUMN_NAME = 'Nombre') > 0,
    'UPDATE gestion_activos SET Descripcion = Nombre WHERE (Descripcion IS NULL OR TRIM(Descripcion) = '''') AND Nombre IS NOT NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gestion_activos' AND COLUMN_NAME = 'Notas') > 0,
    'UPDATE gestion_activos SET Observaciones = Notas WHERE Observaciones IS NULL AND Notas IS NOT NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gestion_activos' AND COLUMN_NAME = 'FechaCompra') > 0,
    'UPDATE gestion_activos SET FechaInicio = FechaCompra WHERE FechaInicio IS NULL AND FechaCompra IS NOT NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gestion_activos' AND COLUMN_NAME = 'FechaGarantia') > 0,
    'UPDATE gestion_activos SET FechaFin = FechaGarantia WHERE FechaFin IS NULL AND FechaGarantia IS NOT NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gestion_activos' AND COLUMN_NAME = 'Proveedor') > 0,
    'INSERT INTO gestion_proveedores (Nombre, Categoria, Estado)
     SELECT DISTINCT TRIM(a.Proveedor), ''hardware'', 1
     FROM gestion_activos a
     WHERE a.Proveedor IS NOT NULL AND TRIM(a.Proveedor) <> ''''
       AND NOT EXISTS (SELECT 1 FROM gestion_proveedores p WHERE p.Nombre = TRIM(a.Proveedor))',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gestion_activos' AND COLUMN_NAME = 'Proveedor') > 0,
    'UPDATE gestion_activos a
     INNER JOIN gestion_proveedores p ON p.Nombre = TRIM(a.Proveedor)
     SET a.ProveedorId = p.Id
     WHERE a.ProveedorId IS NULL AND a.Proveedor IS NOT NULL AND TRIM(a.Proveedor) <> ''''',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

ALTER TABLE gestion_activos
    DROP COLUMN IF EXISTS Nombre,
    DROP COLUMN IF EXISTS Ubicacion,
    DROP COLUMN IF EXISTS ValorCompra,
    DROP COLUMN IF EXISTS ValorActual,
    DROP COLUMN IF EXISTS FechaCompra,
    DROP COLUMN IF EXISTS FechaGarantia,
    DROP COLUMN IF EXISTS Proveedor,
    DROP COLUMN IF EXISTS Notas;

ALTER TABLE gestion_tickets
    ADD COLUMN IF NOT EXISTS EmpresaId INT NULL AFTER SolicitanteId;

ALTER TABLE gestion_mantenimiento
    ADD COLUMN IF NOT EXISTS Anio SMALLINT NULL AFTER ActivoId,
    ADD COLUMN IF NOT EXISTS Semestre TINYINT NULL AFTER Anio,
    ADD COLUMN IF NOT EXISTS Ejecutor ENUM('ti', 'proveedor') NULL AFTER Semestre,
    ADD COLUMN IF NOT EXISTS ProveedorId INT NULL AFTER Ejecutor;

ALTER TABLE gestion_licencias
    ADD COLUMN IF NOT EXISTS EmpresaId INT NULL AFTER Id,
    ADD COLUMN IF NOT EXISTS AreaId INT NULL AFTER EmpresaId;

ALTER TABLE gestion_licencias_asignaciones
    MODIFY COLUMN UsuarioId INT NULL;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_activos'
       AND CONSTRAINT_NAME = 'fk_gestion_activos_empresa') = 0,
    'ALTER TABLE gestion_activos ADD CONSTRAINT fk_gestion_activos_empresa FOREIGN KEY (EmpresaId) REFERENCES gcp_empresas(Id) ON DELETE RESTRICT',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_activos'
       AND CONSTRAINT_NAME = 'fk_gestion_activos_proveedor') = 0,
    'ALTER TABLE gestion_activos ADD CONSTRAINT fk_gestion_activos_proveedor FOREIGN KEY (ProveedorId) REFERENCES gestion_proveedores(Id) ON DELETE SET NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_tickets'
       AND CONSTRAINT_NAME = 'fk_gestion_tickets_empresa') = 0,
    'ALTER TABLE gestion_tickets ADD CONSTRAINT fk_gestion_tickets_empresa FOREIGN KEY (EmpresaId) REFERENCES gcp_empresas(Id) ON DELETE SET NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_mantenimiento'
       AND CONSTRAINT_NAME = 'fk_gestion_mantenimiento_proveedor') = 0,
    'ALTER TABLE gestion_mantenimiento ADD CONSTRAINT fk_gestion_mantenimiento_proveedor FOREIGN KEY (ProveedorId) REFERENCES gestion_proveedores(Id) ON DELETE SET NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_licencias'
       AND CONSTRAINT_NAME = 'fk_gestion_licencias_empresa') = 0,
    'ALTER TABLE gestion_licencias ADD CONSTRAINT fk_gestion_licencias_empresa FOREIGN KEY (EmpresaId) REFERENCES gcp_empresas(Id) ON DELETE RESTRICT',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_licencias'
       AND CONSTRAINT_NAME = 'fk_gestion_licencias_area') = 0,
    'ALTER TABLE gestion_licencias ADD CONSTRAINT fk_gestion_licencias_area FOREIGN KEY (AreaId) REFERENCES gcp_areas(Id) ON DELETE RESTRICT',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_licencias_asignaciones'
       AND CONSTRAINT_NAME = 'fk_licencias_asign_usuario') > 0,
    'ALTER TABLE gestion_licencias_asignaciones DROP FOREIGN KEY fk_licencias_asign_usuario',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_licencias_asignaciones'
       AND CONSTRAINT_NAME = 'fk_licencias_asign_usuario') = 0,
    'ALTER TABLE gestion_licencias_asignaciones ADD CONSTRAINT fk_licencias_asign_usuario FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios(Id) ON DELETE SET NULL',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'gestion_mantenimiento'
       AND INDEX_NAME = 'uq_mantenimiento_periodo') = 0,
    'ALTER TABLE gestion_mantenimiento ADD UNIQUE KEY uq_mantenimiento_periodo (ActivoId, Anio, Semestre)',
    'SET @migration_noop = 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @app_id = (SELECT Id FROM gcp_aplicaciones WHERE Codigo = 'intranet' LIMIT 1);
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado)
SELECT @app_id, 'intranet', 'Administrar Directorio', 'Editar los contactos del directorio', 'intranet.administrar_directorio', 1
WHERE @app_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM gcp_permisos WHERE CodigoUnico = 'intranet.administrar_directorio');

INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT r.Id, p.Id
FROM gcp_roles r
INNER JOIN gcp_permisos p ON p.CodigoUnico = 'intranet.administrar_directorio'
WHERE r.Codigo = 'admin'
  AND NOT EXISTS (
      SELECT 1 FROM gcp_rolespermisos rp
      WHERE rp.RolId = r.Id AND rp.PermisoId = p.Id
  );
