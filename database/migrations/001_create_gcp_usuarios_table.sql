-- Migration: GCP_Usuarios
-- Description: Tabla principal de usuarios de la Gacela Core Platform
-- Version: 1.0
-- Date: 2026-07-23

CREATE TABLE IF NOT EXISTS GCP_Usuarios (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    GrupoId INT NOT NULL,
    EmpresaId INT NOT NULL,
    CiudadId INT NOT NULL,
    AgenciaId INT NOT NULL,
    AreaId INT NOT NULL,
    CargoId INT NOT NULL,
    Nombres VARCHAR(100) NOT NULL,
    Apellidos VARCHAR(100) NOT NULL,
    EmailCorporativo VARCHAR(150) NOT NULL UNIQUE,
    Telefono VARCHAR(20),
    Estado TINYINT NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CreadoPor INT,
    ActualizadoPor INT,
    INDEX idx_grupo (GrupoId),
    INDEX idx_empresa (EmpresaId),
    INDEX idx_ciudad (CiudadId),
    INDEX idx_agencia (AgenciaId),
    INDEX idx_area (AreaId),
    INDEX idx_cargo (CargoId),
    INDEX idx_email (EmailCorporativo),
    INDEX idx_estado (Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios de la Gacela Core Platform';
