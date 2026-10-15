-- Migration: GCP_Aplicaciones
-- Description: Tabla de aplicaciones/módulos del sistema
-- Version: 1.0
-- Date: 2026-08-05

CREATE TABLE IF NOT EXISTS GCP_Aplicaciones (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(50) NOT NULL UNIQUE,
    Ruta VARCHAR(255),
    Color VARCHAR(7) DEFAULT '#6366f1' COMMENT 'Color hexadecimal',
    Icono VARCHAR(50),
    OrdenMenu INT DEFAULT 0,
    Visible TINYINT NOT NULL DEFAULT 1,
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (Codigo),
    INDEX idx_estado (Estado),
    INDEX idx_orden (OrdenMenu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Aplicaciones/Módulos de la GCP';
