ALTER TABLE gcp_solicitudes
    ADD COLUMN IF NOT EXISTS EmpresaSolicitudId INT NULL;

CREATE TABLE IF NOT EXISTS gcp_dotacionsolicitudes (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UsuarioId INT NOT NULL,
    EmpresaId INT NOT NULL,
    CedulaExpedidaEn VARCHAR(120) NOT NULL,
    Estado VARCHAR(32) NOT NULL DEFAULT 'Pendiente_Coordinador',
    NivelActual TINYINT UNSIGNED NOT NULL DEFAULT 2,
    JefeGestionHumanaId INT NULL,
    CoordinadorId INT NOT NULL,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_dotacion_usuario (UsuarioId, FechaCreacion),
    KEY idx_dotacion_jefe_estado (JefeGestionHumanaId, Estado),
    KEY idx_dotacion_coordinador_estado (CoordinadorId, Estado),
    CONSTRAINT fk_dotacion_usuario FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios(Id),
    CONSTRAINT fk_dotacion_empresa FOREIGN KEY (EmpresaId) REFERENCES gcp_empresas(Id),
    CONSTRAINT fk_dotacion_jefe FOREIGN KEY (JefeGestionHumanaId) REFERENCES gcp_usuarios(Id),
    CONSTRAINT fk_dotacion_coordinador FOREIGN KEY (CoordinadorId) REFERENCES gcp_usuarios(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gcp_dotacionitems (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    SolicitudId INT NOT NULL,
    Cantidad SMALLINT UNSIGNED NOT NULL,
    Referencia VARCHAR(150) NOT NULL,
    KEY idx_dotacionitems_solicitud (SolicitudId),
    CONSTRAINT fk_dotacionitems_solicitud FOREIGN KEY (SolicitudId)
        REFERENCES gcp_dotacionsolicitudes(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gcp_dotacionhistorial (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    SolicitudId INT NOT NULL,
    UsuarioId INT NOT NULL,
    Nivel TINYINT UNSIGNED NOT NULL,
    Accion VARCHAR(32) NOT NULL,
    Comentario VARCHAR(500) NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_dotacionhistorial_solicitud (SolicitudId, Fecha),
    CONSTRAINT fk_dotacionhistorial_solicitud FOREIGN KEY (SolicitudId)
        REFERENCES gcp_dotacionsolicitudes(Id) ON DELETE CASCADE,
    CONSTRAINT fk_dotacionhistorial_usuario FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
