-- Cédula de ciudadanía del usuario (se usa automáticamente en los documentos que la requieren).
ALTER TABLE gcp_usuarios
    ADD COLUMN IF NOT EXISTS Cedula VARCHAR(20) NULL AFTER Apellidos;

-- Datos adicionales de la solicitud de vacaciones.
ALTER TABLE gcp_solicitudes
    ADD COLUMN IF NOT EXISTS FechaReintegro DATE NULL,
    ADD COLUMN IF NOT EXISTS DiasDinero INT NULL,
    ADD COLUMN IF NOT EXISTS DiasDisfrute INT NULL,
    ADD COLUMN IF NOT EXISTS PeriodoPendiente VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS EstadoNomina VARCHAR(30) NULL
        COMMENT 'Pendiente_Liquidacion, Pendiente_Revision_Nomina, Con_Errores, Pendiente_Pago, Pagada';

-- Documentos del proceso de liquidación de nómina (cada subida es una versión).
CREATE TABLE IF NOT EXISTS gcp_solicitudnomina (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    SolicitudId INT NOT NULL,
    UsuarioId INT NOT NULL,
    NombreArchivo VARCHAR(255) NOT NULL,
    RutaArchivo VARCHAR(255) NOT NULL,
    FechaSubida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_solicitudnomina_solicitud (SolicitudId),
    CONSTRAINT fk_solicitudnomina_solicitud FOREIGN KEY (SolicitudId) REFERENCES gcp_solicitudes (Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Centro de notificaciones persistente.
CREATE TABLE IF NOT EXISTS gcp_notificaciones (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UsuarioId INT NOT NULL,
    Tipo VARCHAR(50) NOT NULL,
    Titulo VARCHAR(200) NOT NULL,
    Mensaje VARCHAR(500) NULL,
    Enlace VARCHAR(255) NULL,
    Leida TINYINT NOT NULL DEFAULT 0,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notificaciones_usuario (UsuarioId, Leida),
    CONSTRAINT fk_notificaciones_usuario FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios (Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
