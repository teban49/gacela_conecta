-- Migration: GCP_RBAC
-- Description: Tablas para Role-Based Access Control (RBAC)
-- Version: 1.0
-- Date: 2026-07-23

-- Tabla: GCP_Roles
CREATE TABLE IF NOT EXISTS GCP_Roles (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Codigo VARCHAR(50) NOT NULL UNIQUE,
    Descripcion TEXT,
    NivelAcceso INT DEFAULT 1 COMMENT '1=Básico, 10=Administrador',
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (Codigo),
    INDEX idx_estado (Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles de la GCP';

-- Tabla: GCP_Permisos
CREATE TABLE IF NOT EXISTS GCP_Permisos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Modulo VARCHAR(100) NOT NULL COMMENT 'Ej: usuarios, empresas, reportes',
    Accion VARCHAR(100) NOT NULL COMMENT 'Ej: crear, leer, actualizar, eliminar',
    Descripcion TEXT,
    CodigoUnico VARCHAR(200) NOT NULL UNIQUE COMMENT 'Ej: usuarios.crear, empresas.leer',
    Estado TINYINT NOT NULL DEFAULT 1,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_modulo (Modulo),
    INDEX idx_codigo (CodigoUnico),
    INDEX idx_estado (Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permisos de la GCP';

-- Tabla: GCP_RolesPermisos
CREATE TABLE IF NOT EXISTS GCP_RolesPermisos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    RolId INT NOT NULL,
    PermisoId INT NOT NULL,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_rol_permiso (RolId, PermisoId),
    INDEX idx_rol (RolId),
    INDEX idx_permiso (PermisoId),
    CONSTRAINT fk_rolespermisos_rol FOREIGN KEY (RolId) REFERENCES GCP_Roles(Id) ON DELETE CASCADE,
    CONSTRAINT fk_rolespermisos_permiso FOREIGN KEY (PermisoId) REFERENCES GCP_Permisos(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignación de permisos a roles';

-- Tabla: GCP_UsuariosRoles
CREATE TABLE IF NOT EXISTS GCP_UsuariosRoles (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UsuarioId INT NOT NULL,
    RolId INT NOT NULL,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario_rol (UsuarioId, RolId),
    INDEX idx_usuario (UsuarioId),
    INDEX idx_rol (RolId),
    CONSTRAINT fk_usuariosroles_usuario FOREIGN KEY (UsuarioId) REFERENCES GCP_Usuarios(Id) ON DELETE CASCADE,
    CONSTRAINT fk_usuariosroles_rol FOREIGN KEY (RolId) REFERENCES GCP_Roles(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignación de roles a usuarios';
