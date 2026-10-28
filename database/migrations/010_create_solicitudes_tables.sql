-- Migration: Create Ticket Tables
-- Description: Tablas para tickets, seguimiento y categorías de Gestión Tecnológica
-- Version: 1.0
-- Date: 2026-08-08

-- Tabla de Categorías de Tickets
CREATE TABLE IF NOT EXISTS gestion_tickets_categorias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion TEXT,
    Codigo VARCHAR(50) UNIQUE,
    Icono VARCHAR(50),
    Color VARCHAR(7),
    Prioridad INT DEFAULT 0,
    Estado TINYINT(1) DEFAULT 1,
    AsignadoA INT, -- ID del usuario asignado por defecto
    TiempoRespuestaHoras INT DEFAULT 24,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (Codigo),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_categorias_asignado FOREIGN KEY (AsignadoA) REFERENCES gcp_usuarios(Id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Tickets
CREATE TABLE IF NOT EXISTS gestion_tickets (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Ticket VARCHAR(20) UNIQUE, -- Código de ticket generado automáticamente
    CategoriaId INT NOT NULL,
    SolicitanteId INT NOT NULL,
    AsignadoA INT,
    Prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    Estado ENUM('nueva', 'en_proceso', 'en_revision', 'resuelta', 'cerrada', 'cancelada') DEFAULT 'nueva',
    Tipo ENUM('soporte', 'solicitud', 'incidencia', 'mejora', 'otro') DEFAULT 'soporte',
    Titulo VARCHAR(255) NOT NULL,
    Descripcion TEXT NOT NULL,
    Ubicacion VARCHAR(255),
    ArchivoAdjunto VARCHAR(500),
    Canal ENUM('portal', 'email', 'telefono', 'presencial') DEFAULT 'portal',
    FechaVencimiento DATETIME,
    FechaResolucion DATETIME,
    Satisfaccion INT, -- 1-5 estrellas
    ComentarioSatisfaccion TEXT,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ticket (Ticket),
    INDEX idx_categoria (CategoriaId),
    INDEX idx_solicitante (SolicitanteId),
    INDEX idx_asignado (AsignadoA),
    INDEX idx_estado (Estado),
    INDEX idx_prioridad (Prioridad),
    INDEX idx_fecha_creacion (FechaCreacion),
    CONSTRAINT fk_gestion_tickets_categoria FOREIGN KEY (CategoriaId) REFERENCES gestion_tickets_categorias(Id) ON DELETE RESTRICT,
    CONSTRAINT fk_gestion_tickets_solicitante FOREIGN KEY (SolicitanteId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE,
    CONSTRAINT fk_gestion_tickets_asignado FOREIGN KEY (AsignadoA) REFERENCES gcp_usuarios(Id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Seguimiento de Tickets
CREATE TABLE IF NOT EXISTS gestion_tickets_seguimiento (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    SolicitudId INT NOT NULL,
    UsuarioId INT NOT NULL,
    EstadoAnterior ENUM('nueva', 'en_proceso', 'en_revision', 'resuelta', 'cerrada', 'cancelada'),
    EstadoNuevo ENUM('nueva', 'en_proceso', 'en_revision', 'resuelta', 'cerrada', 'cancelada'),
    Comentario TEXT,
    ArchivoAdjunto VARCHAR(500),
    TiempoInvertidoMinutos INT DEFAULT 0,
    TipoCambio ENUM('estado', 'asignacion', 'comentario', 'archivo', 'resolucion') DEFAULT 'comentario',
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_solicitud (SolicitudId),
    INDEX idx_usuario (UsuarioId),
    INDEX idx_fecha (FechaCreacion),
    CONSTRAINT fk_gestion_tickets_seguimiento_ticket FOREIGN KEY (SolicitudId) REFERENCES gestion_tickets(Id) ON DELETE CASCADE,
    CONSTRAINT fk_seguimiento_usuario FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

