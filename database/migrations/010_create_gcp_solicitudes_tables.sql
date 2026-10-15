-- Migration: GCP_Solicitudes_Tables
-- Description: Tablas para el módulo de Solicitudes Corporativas (Vacaciones, Licencias, Permisos, Certificaciones, etc.) con flujo de aprobación multinivel.

CREATE TABLE IF NOT EXISTS GCP_SolicitudTipos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL UNIQUE,
    Codigo VARCHAR(50) NOT NULL UNIQUE,
    RequiereAdjunto TINYINT NOT NULL DEFAULT 0,
    Estado TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO GCP_SolicitudTipos (Nombre, Codigo, RequiereAdjunto, Estado) VALUES
('Vacaciones', 'vacaciones', 0, 1),
('Licencia', 'licencia', 1, 1),
('Permiso Especial', 'permiso', 1, 1),
('Certificación Laboral', 'certificacion', 0, 1),
('Otros Procesos', 'otros', 0, 1)
ON DUPLICATE KEY UPDATE Nombre=VALUES(Nombre);

CREATE TABLE IF NOT EXISTS GCP_Solicitudes (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UsuarioId INT NOT NULL,
    TipoId INT NOT NULL,
    Asunto VARCHAR(200) NOT NULL,
    Descripcion TEXT NOT NULL,
    FechaInicio DATE NULL,
    FechaFin DATE NULL,
    EstadoSolicitud VARCHAR(30) NOT NULL DEFAULT 'Pendiente_Jefe' COMMENT 'Pendiente_Jefe, Pendiente_RRHH, Pendiente_Gerencia, Aprobada, Rechazada',
    NivelActual INT NOT NULL DEFAULT 1 COMMENT '1: Jefe, 2: RRHH, 3: Gerencia',
    MotivoRechazo TEXT NULL,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (UsuarioId) REFERENCES GCP_Usuarios(Id),
    FOREIGN KEY (TipoId) REFERENCES GCP_SolicitudTipos(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS GCP_SolicitudAdjuntos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    SolicitudId INT NOT NULL,
    NombreArchivo VARCHAR(255) NOT NULL,
    RutaArchivo VARCHAR(255) NOT NULL,
    FechaSubida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SolicitudId) REFERENCES GCP_Solicitudes(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS GCP_SolicitudHistorial (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    SolicitudId INT NOT NULL,
    UsuarioId INT NOT NULL,
    Accion VARCHAR(50) NOT NULL COMMENT 'Creada, Aprobada, Rechazada, Comentario',
    Comentario TEXT NULL,
    Nivel INT NOT NULL DEFAULT 1,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SolicitudId) REFERENCES GCP_Solicitudes(Id) ON DELETE CASCADE,
    FOREIGN KEY (UsuarioId) REFERENCES GCP_Usuarios(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
