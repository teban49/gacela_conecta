-- One-time normalization for the active gacela_conecta_beta schema.
-- Run only after taking a database backup.

CREATE TABLE gcp_ciudades (
    Id INT NOT NULL AUTO_INCREMENT,
    Nombre VARCHAR(100) NOT NULL,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_gcp_ciudades_nombre (Nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO gcp_ciudades (Nombre)
SELECT DISTINCT TRIM(Ciudad)
FROM gcp_agencias
WHERE Ciudad IS NOT NULL AND TRIM(Ciudad) <> '';

ALTER TABLE gcp_agencias
    ADD COLUMN CiudadId INT NULL AFTER Id;

UPDATE gcp_agencias a
INNER JOIN gcp_ciudades c ON c.Nombre = TRIM(a.Ciudad)
SET a.CiudadId = c.Id;

ALTER TABLE gcp_agencias
    MODIFY COLUMN CiudadId INT NOT NULL,
    ADD CONSTRAINT fk_gcp_agencias_ciudad
        FOREIGN KEY (CiudadId) REFERENCES gcp_ciudades(Id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    DROP COLUMN Ciudad;

ALTER TABLE gcp_usuarios
    ADD CONSTRAINT fk_gcp_usuarios_agencia
        FOREIGN KEY (AgenciaId) REFERENCES gcp_agencias(Id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_gcp_usuarios_cargo
        FOREIGN KEY (CargoId) REFERENCES gcp_cargos(Id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

CREATE TABLE intranet_evento_participantes (
    EventoId INT NOT NULL,
    UsuarioId INT NOT NULL,
    FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (EventoId, UsuarioId),
    KEY idx_evento_participantes_usuario (UsuarioId),
    CONSTRAINT fk_evento_participantes_evento
        FOREIGN KEY (EventoId) REFERENCES intranet_eventos(Id)
        ON DELETE CASCADE,
    CONSTRAINT fk_evento_participantes_usuario
        FOREIGN KEY (UsuarioId) REFERENCES gcp_usuarios(Id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE intranet_eventos
    DROP COLUMN ParticipantesIds;

ALTER TABLE gcp_aplicaciones
    DROP COLUMN EsVisibleMenu;

ALTER TABLE gcp_authtokens DROP INDEX idx_token;
ALTER TABLE gcp_cargos DROP INDEX idx_area;
ALTER TABLE gcp_empresas DROP INDEX idx_grupo;
ALTER TABLE gcp_grupos DROP INDEX idx_codigo;
ALTER TABLE gcp_permisos DROP INDEX idx_codigo;
ALTER TABLE gcp_roles DROP INDEX idx_codigo;
ALTER TABLE gcp_rolespermisos DROP INDEX idx_rol;
ALTER TABLE gcp_usuarios DROP INDEX idx_email;
ALTER TABLE gcp_usuariosroles DROP INDEX idx_usuario;
ALTER TABLE gestion_activos DROP INDEX idx_codigo_inventario;
ALTER TABLE gestion_activos_categorias DROP INDEX idx_codigo;
ALTER TABLE gestion_incidencias DROP INDEX idx_ticket;
ALTER TABLE intranet_foro_votos DROP INDEX idx_respuesta;
ALTER TABLE intranet_noticias_vistas DROP INDEX idx_noticia;
ALTER TABLE gestion_tickets DROP INDEX idx_ticket;
ALTER TABLE gestion_tickets_categorias DROP INDEX idx_codigo;
ALTER TABLE gestion_tickets_etiquetas_rel DROP INDEX idx_solicitud;

DROP TABLE intranet_foro_votos;
DROP TABLE intranet_foro_respuestas;
DROP TABLE intranet_foro_temas;
DROP TABLE intranet_foro_categorias;
DROP TABLE gestion_tickets_etiquetas_rel;
DROP TABLE gestion_tickets_etiquetas;
DROP TABLE gestion_tickets_estadisticas;
DROP TABLE gestion_tickets_plantillas;
