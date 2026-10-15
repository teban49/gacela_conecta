-- Migration: Create Gestión Tecnológica Tables
-- Description: Tablas para el módulo de Gestión Tecnológica (activos, inventario, incidencias)
-- Version: 1.0
-- Date: 2026-08-08

-- Tabla de Categorías de Activos
CREATE TABLE IF NOT EXISTS gestion_activos_categorias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion TEXT,
    Codigo VARCHAR(50) UNIQUE,
    Icono VARCHAR(50),
    Color VARCHAR(7),
    Estado TINYINT(1) DEFAULT 1,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (Codigo),
    INDEX idx_estado (Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Activos Tecnológicos
CREATE TABLE IF NOT EXISTS gestion_activos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    CategoriaId INT NOT NULL,
    CodigoInventario VARCHAR(50) UNIQUE,
    Nombre VARCHAR(255) NOT NULL,
    Descripcion TEXT,
    Marca VARCHAR(100),
    Modelo VARCHAR(100),
    NumeroSerie VARCHAR(100),
    FechaCompra DATE,
    FechaGarantia DATE,
    EstadoActivo ENUM('nuevo', 'en_uso', 'mantenimiento', 'dañado', 'obsoleto', 'retirado') DEFAULT 'nuevo',
    Ubicacion VARCHAR(255),
    Departamento VARCHAR(100),
    AsignadoA INT, -- ID del usuario asignado
    ValorCompra DECIMAL(10,2),
    ValorActual DECIMAL(10,2),
    Proveedor VARCHAR(255),
    Notas TEXT,
    Estado TINYINT(1) DEFAULT 1,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo_inventario (CodigoInventario),
    INDEX idx_categoria (CategoriaId),
    INDEX idx_asignado (AsignadoA),
    INDEX idx_estado_activo (EstadoActivo),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_activos_categoria FOREIGN KEY (CategoriaId) REFERENCES gestion_activos_categorias(Id) ON DELETE RESTRICT,
    CONSTRAINT fk_activos_asignado FOREIGN KEY (AsignadoA) REFERENCES gcp_usuarios(Id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Historial de Mantenimiento
CREATE TABLE IF NOT EXISTS gestion_mantenimiento (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    ActivoId INT NOT NULL,
    Tipo ENUM('preventivo', 'correctivo', 'predictivo') DEFAULT 'correctivo',
    Descripcion TEXT NOT NULL,
    TecnicoId INT NOT NULL,
    FechaProgramada DATE,
    FechaRealizacion DATETIME,
    Costo DECIMAL(10,2),
    Estado ENUM('programado', 'en_proceso', 'completado', 'cancelado') DEFAULT 'programado',
    Resultado TEXT,
    ArchivoAdjunto VARCHAR(500),
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (ActivoId),
    INDEX idx_tecnico (TecnicoId),
    INDEX idx_fecha_programada (FechaProgramada),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_mantenimiento_activo FOREIGN KEY (ActivoId) REFERENCES gestion_activos(Id) ON DELETE CASCADE,
    CONSTRAINT fk_mantenimiento_tecnico FOREIGN KEY (TecnicoId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Incidencias Tecnológicas
CREATE TABLE IF NOT EXISTS gestion_incidencias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Ticket VARCHAR(20) UNIQUE,
    ActivoId INT,
    Tipo ENUM('hardware', 'software', 'red', 'seguridad', 'otro') DEFAULT 'hardware',
    Prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    Estado ENUM('abierta', 'en_proceso', 'resuelta', 'cerrada') DEFAULT 'abierta',
    Titulo VARCHAR(255) NOT NULL,
    Descripcion TEXT NOT NULL,
    ReportadoPor INT NOT NULL,
    AsignadoA INT,
    FechaReporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaResolucion DATETIME,
    TiempoResolucionMinutos INT,
    Solucion TEXT,
    CausaRaiz TEXT,
    ArchivoAdjunto VARCHAR(500),
    Satisfaccion INT,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ticket (Ticket),
    INDEX idx_activo (ActivoId),
    INDEX idx_reportado_por (ReportadoPor),
    INDEX idx_asignado (AsignadoA),
    INDEX idx_estado (Estado),
    INDEX idx_prioridad (Prioridad),
    CONSTRAINT fk_incidencias_activo FOREIGN KEY (ActivoId) REFERENCES gestion_activos(Id) ON DELETE SET NULL,
    CONSTRAINT fk_incidencias_reportado FOREIGN KEY (ReportadoPor) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE,
    CONSTRAINT fk_incidencias_asignado FOREIGN KEY (AsignadoA) REFERENCES gcp_usuarios(Id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Licencias de Software
CREATE TABLE IF NOT EXISTS gestion_licencias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(255) NOT NULL,
    Tipo ENUM('comercial', 'opensource', 'personalizado') DEFAULT 'comercial',
    Proveedor VARCHAR(255),
    NumeroLicencia VARCHAR(100),
    Cantidad INT DEFAULT 1,
    CantidadUsada INT DEFAULT 0,
    FechaCompra DATE,
    FechaVencimiento DATE,
    Costo DECIMAL(10,2),
    RenovacionAutomatica TINYINT(1) DEFAULT 0,
    Notas TEXT,
    Estado TINYINT(1) DEFAULT 1,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre (Nombre),
    INDEX idx_fecha_vencimiento (FechaVencimiento),
    INDEX idx_estado (Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Asignación de Licencias
CREATE TABLE IF NOT EXISTS gestion_licencias_asignaciones (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    LicenciaId INT NOT NULL,
    UsuarioId INT NOT NULL,
    ActivoId INT,
    FechaAsignacion DATE DEFAULT (CURRENT_DATE),
    FechaDesasignacion DATE,
    Estado ENUM('activa', 'revocada', 'expirada') DEFAULT 'activa',
    Notas TEXT,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_licencia (LicenciaId),
    INDEX idx_usuario (UsuarioId),
    INDEX idx_activo (ActivoId),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_licencias_asign_licencia FOREIGN KEY (LicenciaId) REFERENCES gestion_licencias(Id) ON DELETE CASCADE,
    CONSTRAINT fk_licencias_asign_usuario FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios(Id) ON DELETE CASCADE,
    CONSTRAINT fk_licencias_asign_activo FOREIGN KEY (ActivoId) REFERENCES gestion_activos(Id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Proveedores
CREATE TABLE IF NOT EXISTS gestion_proveedores (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(255) NOT NULL,
    Ruc VARCHAR(20),
    Contacto VARCHAR(100),
    Email VARCHAR(100),
    Telefono VARCHAR(20),
    Direccion TEXT,
    Categoria ENUM('hardware', 'software', 'servicios', 'mantenimiento', 'otro') DEFAULT 'servicios',
    Estado TINYINT(1) DEFAULT 1,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre (Nombre),
    INDEX idx_categoria (Categoria),
    INDEX idx_estado (Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;