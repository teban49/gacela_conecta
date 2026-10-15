-- Migration: GCP_AuthTokens
-- Description: Tabla para tokens de autenticación (Magic Links)
-- Version: 1.0
-- Date: 2026-07-23

CREATE TABLE IF NOT EXISTS GCP_AuthTokens (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UsuarioId INT NOT NULL,
    TokenHash VARCHAR(255) NOT NULL UNIQUE COMMENT 'Hash SHA-256 del token',
    Tipo VARCHAR(50) NOT NULL DEFAULT 'MAGIC_LINK' COMMENT 'MAGIC_LINK, PASSWORD_RESET, etc.',
    FechaExpiracion DATETIME NOT NULL,
    Usado TINYINT NOT NULL DEFAULT 0 COMMENT '0=No usado, 1=Usado',
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaUso DATETIME,
    IpAddress VARCHAR(45),
    UserAgent VARCHAR(255),
    INDEX idx_usuario (UsuarioId),
    INDEX idx_token (TokenHash),
    INDEX idx_expiracion (FechaExpiracion),
    INDEX idx_usado (Usado),
    CONSTRAINT fk_authtokens_usuario FOREIGN KEY (UsuarioId) REFERENCES GCP_Usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens de autenticación de la GCP';
