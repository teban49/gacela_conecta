-- Migration: GCP_Intranet_Tables
-- Description: Tablas para el módulo de Intranet Corporativa, Gestión Documental, Organigrama, Noticias, Manuales, Píldoras y Links.

CREATE TABLE IF NOT EXISTS GCP_Intranet_Empresas (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL UNIQUE,
    Codigo VARCHAR(50) NOT NULL UNIQUE,
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS GCP_Intranet_Areas (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(50) NOT NULL,
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS GCP_Intranet_Categorias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(50) NOT NULL,
    Estado TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS GCP_Intranet_Documentos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    EmpresaId INT NOT NULL,
    AreaId INT NOT NULL,
    CategoriaId INT NOT NULL,
    Titulo VARCHAR(200) NOT NULL,
    CodigoDocumento VARCHAR(50),
    Version VARCHAR(20) DEFAULT '1.0',
    RutaArchivo VARCHAR(255) NOT NULL,
    TipoArchivo VARCHAR(50),
    TamanoBytes BIGINT DEFAULT 0,
    Descripcion TEXT,
    CreadoPorUsuarioId INT NOT NULL,
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (EmpresaId) REFERENCES GCP_Intranet_Empresas(Id),
    FOREIGN KEY (AreaId) REFERENCES GCP_Intranet_Areas(Id),
    FOREIGN KEY (CategoriaId) REFERENCES GCP_Intranet_Categorias(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

