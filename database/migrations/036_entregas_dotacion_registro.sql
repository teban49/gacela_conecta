-- Elimina entregas hist贸ricas sin art铆culos (no aportan informaci贸n)
DELETE e FROM entregas_dotacion e
LEFT JOIN entregas_dotacion_items i ON i.entrega_id = e.id
WHERE i.id IS NULL;

-- Vincula cada entrega con la solicitud que la origin贸 (evita duplicados al confirmar la recepci贸n)
ALTER TABLE entregas_dotacion
  ADD COLUMN solicitud_id INT NULL AFTER empresa_id,
  ADD UNIQUE KEY uq_entregas_solicitud (solicitud_id),
  ADD CONSTRAINT fk_entregas_solicitud FOREIGN KEY (solicitud_id) REFERENCES gcp_dotacionsolicitudes (Id) ON DELETE SET NULL;

UPDATE gcp_aplicaciones SET Icono = 'laptop' WHERE Codigo = 'gestion';

-- Registra las solicitudes ya recibidas antes de existir este v韓culo
INSERT INTO entregas_dotacion (empresa_id, solicitud_id, cedula, fecha_entrega)
SELECT s.EmpresaId, s.Id, REGEXP_REPLACE(u.Cedula, '[^0-9]', ''), DATE(s.FechaActualizacion)
FROM gcp_dotacionsolicitudes s
INNER JOIN gcp_usuarios u ON u.Id = s.UsuarioId
WHERE s.Estado = 'Recibida' AND NOT EXISTS (SELECT 1 FROM entregas_dotacion x WHERE x.solicitud_id = s.Id);

INSERT INTO entregas_dotacion_items (entrega_id, cantidad, referencia, talla)
SELECT e.id, i.Cantidad, i.Referencia, i.Talla
FROM entregas_dotacion e
INNER JOIN gcp_dotacionitems i ON i.SolicitudId = e.solicitud_id
WHERE NOT EXISTS (SELECT 1 FROM entregas_dotacion_items y WHERE y.entrega_id = e.id);
