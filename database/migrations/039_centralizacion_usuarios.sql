-- Centralización de datos de personas en gcp_usuarios, acceso por usuario/contraseña y solicitudes en nombre de terceros.

ALTER TABLE gcp_usuarios
    MODIFY COLUMN EmailCorporativo VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS Usuario VARCHAR(60) NULL AFTER EmailCorporativo,
    ADD COLUMN IF NOT EXISTS PasswordHash VARCHAR(255) NULL AFTER Usuario,
    ADD COLUMN IF NOT EXISTS FechaNacimiento DATE NULL AFTER Cedula,
    ADD COLUMN IF NOT EXISTS Extension VARCHAR(20) NULL AFTER Telefono,
    ADD COLUMN IF NOT EXISTS Direccion VARCHAR(255) NULL AFTER Extension,
    ADD COLUMN IF NOT EXISTS Area VARCHAR(100) NULL AFTER Direccion,
    ADD COLUMN IF NOT EXISTS PerfilConfirmadoEn DATETIME NULL AFTER Estado,
    ADD UNIQUE INDEX IF NOT EXISTS uq_usuarios_usuario (Usuario),
    ADD INDEX IF NOT EXISTS idx_usuarios_cedula (Cedula);

CREATE TABLE IF NOT EXISTS gcp_loginintentos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Usuario VARCHAR(150) NOT NULL,
    Ip VARCHAR(45) NULL,
    Exitoso TINYINT(1) NOT NULL DEFAULT 0,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_loginintentos (Usuario, Fecha),
    INDEX idx_loginintentos_ip (Ip, Fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rol para conductores: solo solicitudes y dotación.
INSERT INTO gcp_roles (Nombre, Codigo, Descripcion, NivelAcceso, Estado)
SELECT 'Conductor', 'conductor', 'Acceso únicamente a solicitudes y dotación', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM gcp_roles WHERE Codigo = 'conductor');

INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT r.Id, p.Id
FROM gcp_roles r
INNER JOIN gcp_permisos p ON p.CodigoUnico IN ('dashboard.ver', 'solicitudes.ver', 'solicitudes.crear')
WHERE r.Codigo = 'conductor'
  AND NOT EXISTS (SELECT 1 FROM gcp_rolespermisos rp WHERE rp.RolId = r.Id AND rp.PermisoId = p.Id);

UPDATE gcp_cargos SET RolId = (SELECT Id FROM gcp_roles WHERE Codigo = 'conductor') WHERE Nombre = 'Conductor';

-- Quién registró la solicitud cuando un agencista la hace en nombre de un taquillero.
ALTER TABLE gcp_solicitudes
    ADD COLUMN IF NOT EXISTS CreadoPorId INT NULL,
    ADD INDEX IF NOT EXISTS idx_solicitudes_creadopor (CreadoPorId),
    ADD CONSTRAINT fk_solicitudes_creadopor FOREIGN KEY (CreadoPorId) REFERENCES gcp_usuarios (Id) ON DELETE SET NULL;

ALTER TABLE gcp_dotacionsolicitudes
    ADD COLUMN IF NOT EXISTS CreadoPorId INT NULL,
    ADD INDEX IF NOT EXISTS idx_dotacion_creadopor (CreadoPorId),
    ADD CONSTRAINT fk_dotacion_creadopor FOREIGN KEY (CreadoPorId) REFERENCES gcp_usuarios (Id) ON DELETE SET NULL;

-- El directorio deja de ser una tabla propia: se conserva una copia y se expone como vista de gcp_usuarios.
RENAME TABLE directorio_gacela_conecta TO directorio_gacela_conecta_legacy;

UPDATE gcp_usuarios u
INNER JOIN directorio_gacela_conecta_legacy l ON LOWER(TRIM(l.email)) = LOWER(u.EmailCorporativo)
SET u.Direccion = COALESCE(u.Direccion, NULLIF(TRIM(l.address), '')),
    u.Extension = COALESCE(u.Extension, NULLIF(TRIM(l.extension), '')),
    u.Area = COALESCE(u.Area, NULLIF(TRIM(l.area_agencia), '')),
    u.FechaNacimiento = COALESCE(u.FechaNacimiento, l.fecha_nacimiento),
    u.Telefono = COALESCE(u.Telefono, NULLIF(TRIM(l.phone), ''));

CREATE OR REPLACE VIEW directorio_gacela_conecta AS
SELECT u.Id AS id,
       u.Nombres AS first_name,
       u.Apellidos AS last_name,
       u.Direccion AS address,
       u.Telefono AS phone,
       u.EmailCorporativo AS email,
       u.Extension AS extension,
       COALESCE(NULLIF(u.Area, ''), a.Nombre) AS area_agencia,
       c.Nombre AS cargo,
       (SELECT GROUP_CONCAT(e.Nombre ORDER BY e.Id SEPARATOR ', ')
          FROM gcp_usuariosempresas ue
          INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId
         WHERE ue.UsuarioId = u.Id) AS empresa,
       u.FechaNacimiento AS fecha_nacimiento
FROM gcp_usuarios u
INNER JOIN gcp_agencias a ON a.Id = u.AgenciaId
INNER JOIN gcp_cargos c ON c.Id = u.CargoId
WHERE u.Estado = 1
  AND u.EmailCorporativo LIKE '%@%.%';
