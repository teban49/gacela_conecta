UPDATE gestion_tickets
SET Estado = 'pendiente'
WHERE Estado = 'en_proceso';

UPDATE gestion_tickets_seguimiento
SET EstadoAnterior = CASE WHEN EstadoAnterior = 'en_proceso' THEN 'pendiente' ELSE EstadoAnterior END,
    EstadoNuevo = CASE WHEN EstadoNuevo = 'en_proceso' THEN 'pendiente' ELSE EstadoNuevo END
WHERE EstadoAnterior = 'en_proceso'
   OR EstadoNuevo = 'en_proceso';

ALTER TABLE gestion_tickets
    MODIFY COLUMN Estado ENUM('nueva', 'pendiente', 'cerrada') DEFAULT 'nueva';

ALTER TABLE gestion_tickets_seguimiento
    MODIFY COLUMN EstadoAnterior ENUM('nueva', 'pendiente', 'cerrada') NULL,
    MODIFY COLUMN EstadoNuevo ENUM('nueva', 'pendiente', 'cerrada') NULL;

INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT r.Id, p.Id
FROM gcp_roles r
INNER JOIN gcp_permisos p ON p.CodigoUnico = 'gestion.administrar_incidencias'
WHERE r.Codigo IN ('admin', 'analista_ti', 'supervisor_ti')
  AND NOT EXISTS (
      SELECT 1
      FROM gcp_rolespermisos rp
      WHERE rp.RolId = r.Id AND rp.PermisoId = p.Id
  );
