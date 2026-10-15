CREATE TABLE IF NOT EXISTS gestion_activos_asignaciones (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    ActivoId INT NOT NULL,
    UsuarioId INT NOT NULL,
    AsignadoPorUsuarioId INT NOT NULL,
    Estado ENUM('pendiente', 'aceptada', 'rechazada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    FechaSolicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaRespuesta DATETIME NULL,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_asignacion_usuario_estado (UsuarioId, Estado),
    INDEX idx_asignacion_activo_estado (ActivoId, Estado),
    CONSTRAINT fk_gestion_asignacion_activo FOREIGN KEY (ActivoId) REFERENCES gestion_activos(Id) ON DELETE CASCADE,
    CONSTRAINT fk_gestion_asignacion_usuario FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE,
    CONSTRAINT fk_gestion_asignacion_solicitante FOREIGN KEY (AsignadoPorUsuarioId) REFERENCES gcp_usuarios(Id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE gestion_tickets
SET Estado = CASE
    WHEN Estado = 'en_revision' THEN 'en_proceso'
    WHEN Estado IN ('resuelta', 'cancelada') THEN 'cerrada'
    ELSE Estado
END
WHERE Estado IN ('en_revision', 'resuelta', 'cancelada');

UPDATE gestion_tickets_seguimiento
SET EstadoAnterior = CASE
        WHEN EstadoAnterior = 'en_revision' THEN 'en_proceso'
        WHEN EstadoAnterior IN ('resuelta', 'cancelada') THEN 'cerrada'
        ELSE EstadoAnterior
    END,
    EstadoNuevo = CASE
        WHEN EstadoNuevo = 'en_revision' THEN 'en_proceso'
        WHEN EstadoNuevo IN ('resuelta', 'cancelada') THEN 'cerrada'
        ELSE EstadoNuevo
    END
WHERE EstadoAnterior IN ('en_revision', 'resuelta', 'cancelada')
   OR EstadoNuevo IN ('en_revision', 'resuelta', 'cancelada');

ALTER TABLE gestion_tickets
    MODIFY COLUMN Estado ENUM('nueva', 'en_proceso', 'cerrada') DEFAULT 'nueva';

ALTER TABLE gestion_tickets_seguimiento
    MODIFY COLUMN EstadoAnterior ENUM('nueva', 'en_proceso', 'cerrada') NULL,
    MODIFY COLUMN EstadoNuevo ENUM('nueva', 'en_proceso', 'cerrada') NULL;

INSERT INTO gestion_activos
    (CategoriaId, EmpresaId, TipoPropiedad, CodigoInventario, FechaInicio, Placa, Observaciones, EstadoActivo, Estado)
SELECT categoria.Id, empresa.Id, 'propio', 'DEMO-MANTENIMIENTO-SEMESTRAL',
       DATE_SUB(CURRENT_DATE, INTERVAL 2 YEAR), 'DEMO-MANTENIMIENTO-SEMESTRAL',
       'Activo de demostración para validar los mantenimientos semestrales pendientes.',
       'nuevo', 1
FROM (SELECT Id FROM gestion_activos_categorias WHERE Estado = 1 ORDER BY Id LIMIT 1) categoria
CROSS JOIN (SELECT Id FROM gcp_empresas WHERE Estado = 1 ORDER BY Id LIMIT 1) empresa
WHERE NOT EXISTS (
    SELECT 1 FROM gestion_activos WHERE CodigoInventario = 'DEMO-MANTENIMIENTO-SEMESTRAL'
);
