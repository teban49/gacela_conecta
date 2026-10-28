-- Migration: Create Intranet Tables
-- Description: Tablas para el módulo de Intranet (noticias, documentos, eventos, foro)
-- Version: 1.0
-- Date: 2026-08-08

-- Tabla de Noticias/Anuncios
CREATE TABLE IF NOT EXISTS intranet_noticias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Titulo VARCHAR(255) NOT NULL,
    Contenido TEXT NOT NULL,
    Resumen VARCHAR(500),
    Categoria ENUM('general', 'rrhh', 'ti', 'operaciones', 'ventas', 'finanzas') DEFAULT 'general',
    Prioridad ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    Imagen VARCHAR(255),
    Estado ENUM('borrador', 'publicada', 'archivada') DEFAULT 'borrador',
    AutorId INT NOT NULL,
    FechaPublicacion DATETIME,
    FechaExpiracion DATETIME,
    VistaPorDefecto TINYINT(1) DEFAULT 0,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_autor (AutorId),
    INDEX idx_categoria (Categoria),
    INDEX idx_estado (Estado),
    INDEX idx_fecha_publicacion (FechaPublicacion),
    CONSTRAINT fk_noticias_autor FOREIGN KEY (AutorId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Documentos Compartidos
CREATE TABLE IF NOT EXISTS intranet_documentos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(255) NOT NULL,
    Descripcion TEXT,
    RutaArchivo VARCHAR(500) NOT NULL,
    Tamaño BIGINT,
    TipoMime VARCHAR(100),
    Categoria ENUM('general', 'manuales', 'politicas', 'formularios', 'plantillas', 'rrhh', 'ti') DEFAULT 'general',
    Extension VARCHAR(10),
    Version VARCHAR(20),
    AutorId INT NOT NULL,
    Descargable TINYINT(1) DEFAULT 1,
    RequierePermiso TINYINT(1) DEFAULT 0,
    PermisoRequerido VARCHAR(50),
    Estado ENUM('activo', 'inactivo', 'eliminado') DEFAULT 'activo',
    Descargas INT DEFAULT 0,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_autor (AutorId),
    INDEX idx_categoria (Categoria),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_documentos_autor FOREIGN KEY (AutorId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Eventos/Calendario
CREATE TABLE IF NOT EXISTS intranet_eventos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Titulo VARCHAR(255) NOT NULL,
    Descripcion TEXT,
    FechaInicio DATETIME NOT NULL,
    FechaFin DATETIME NOT NULL,
    Ubicacion VARCHAR(255),
    Tipo ENUM('reunion', 'capacitacion', 'feriado', 'aniversario', 'evento_social', 'otro') DEFAULT 'reunion',
    Prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
    TodoElDia TINYINT(1) DEFAULT 0,
    Recordatorio INT DEFAULT 30, -- minutos antes
    Estado ENUM('planeado', 'confirmado', 'cancelado', 'completado') DEFAULT 'planeado',
    OrganizadorId INT NOT NULL,
    Repetir ENUM('no', 'diario', 'semanal', 'mensual', 'anual') DEFAULT 'no',
    FechaFinRepeticion DATETIME,
    ParticipantesIds TEXT, -- JSON array de IDs de usuarios
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_organizador (OrganizadorId),
    INDEX idx_fecha_inicio (FechaInicio),
    INDEX idx_tipo (Tipo),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_eventos_organizador FOREIGN KEY (OrganizadorId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Vistas de Noticias
CREATE TABLE IF NOT EXISTS intranet_noticias_vistas (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    NoticiaId INT NOT NULL,
    UsuarioId INT NOT NULL,
    FechaVisualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vista (NoticiaId, UsuarioId),
    INDEX idx_noticia (NoticiaId),
    INDEX idx_usuario (UsuarioId),
    CONSTRAINT fk_vistas_noticia FOREIGN KEY (NoticiaId) REFERENCES intranet_noticias(Id) ON DELETE CASCADE,
    CONSTRAINT fk_vistas_usuario FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;