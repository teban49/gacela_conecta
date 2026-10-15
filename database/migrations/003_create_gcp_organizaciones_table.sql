-- Migration: GCP_Organizaciones
-- Description: Tablas de estructura organizacional (Grupo > Empresa > Ciudad > Agencia > Área > Cargo)
-- Version: 1.0
-- Date: 2026-07-23

-- Tabla: GCP_Grupos
CREATE TABLE IF NOT EXISTS GCP_Grupos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(20) NOT NULL UNIQUE,
    Nit VARCHAR(20),
    Direccion VARCHAR(200),
    Telefono VARCHAR(20),
    Email VARCHAR(150),
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (Codigo),
    INDEX idx_estado (Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos organizacionales de la GCP';

-- Tabla: GCP_Empresas
CREATE TABLE IF NOT EXISTS GCP_Empresas (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    GrupoId INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(20) NOT NULL,
    Nit VARCHAR(20),
    Direccion VARCHAR(200),
    Telefono VARCHAR(20),
    Email VARCHAR(150),
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_grupo_codigo (GrupoId, Codigo),
    INDEX idx_grupo (GrupoId),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_empresas_grupo FOREIGN KEY (GrupoId) REFERENCES GCP_Grupos(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Empresas de la GCP';

-- Tabla: GCP_Ciudades
CREATE TABLE IF NOT EXISTS GCP_Ciudades (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    EmpresaId INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(20) NOT NULL,
    Departamento VARCHAR(100),
    Pais VARCHAR(100) DEFAULT 'Colombia',
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_empresa_codigo (EmpresaId, Codigo),
    INDEX idx_empresa (EmpresaId),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_ciudades_empresa FOREIGN KEY (EmpresaId) REFERENCES GCP_Empresas(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ciudades de la GCP';

-- Tabla: GCP_Agencias
CREATE TABLE IF NOT EXISTS GCP_Agencias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    CiudadId INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(20) NOT NULL,
    Direccion VARCHAR(200),
    Telefono VARCHAR(20),
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ciudad_codigo (CiudadId, Codigo),
    INDEX idx_ciudad (CiudadId),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_agencias_ciudad FOREIGN KEY (CiudadId) REFERENCES GCP_Ciudades(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Agencias de la GCP';

-- Tabla: GCP_Areas
CREATE TABLE IF NOT EXISTS GCP_Areas (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    AgenciaId INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(20) NOT NULL,
    Descripcion TEXT,
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_agencia_codigo (AgenciaId, Codigo),
    INDEX idx_agencia (AgenciaId),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_areas_agencia FOREIGN KEY (AgenciaId) REFERENCES GCP_Agencias(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Áreas de la GCP';

-- Tabla: GCP_Cargos
CREATE TABLE IF NOT EXISTS GCP_Cargos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    AreaId INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(20) NOT NULL,
    Descripcion TEXT,
    NivelJerarquico INT DEFAULT 1,
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_area_codigo (AreaId, Codigo),
    INDEX idx_area (AreaId),
    INDEX idx_estado (Estado),
    CONSTRAINT fk_cargos_area FOREIGN KEY (AreaId) REFERENCES GCP_Areas(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cargos de la GCP';
