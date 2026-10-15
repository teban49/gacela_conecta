-- Migration: GCP_AuditLog
-- Description: Tabla para registro de auditoría (Audit Engine)
-- Version: 1.0
-- Date: 2026-07-23

CREATE TABLE IF NOT EXISTS GCP_AuditLog (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UsuarioId INT,
    Accion VARCHAR(100) NOT NULL COMMENT 'Ej: login, crear_usuario, actualizar_empresa',
    Modulo VARCHAR(100) NOT NULL COMMENT 'Ej: auth, usuarios, empresas',
    EntidadId INT COMMENT 'ID de la entidad afectada',
    EntidadTipo VARCHAR(100) COMMENT 'Tipo de entidad afectada',
    DatosAntes JSON COMMENT 'Estado antes del cambio',
    DatosDespues JSON COMMENT 'Estado después del cambio',
    IpAddress VARCHAR(45),
    UserAgent VARCHAR(255),
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (UsuarioId),
    INDEX idx_accion (Accion),
    INDEX idx_modulo (Modulo),
    INDEX idx_entidad (EntidadId, EntidadTipo),
    INDEX idx_fecha (FechaCreacion),
    CONSTRAINT fk_audit_usuario FOREIGN KEY (UsuarioId) REFERENCES GCP_Usuarios(Id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bitácora de auditoría de la GCP';
