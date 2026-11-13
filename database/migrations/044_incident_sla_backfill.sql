UPDATE gestion_tickets
SET FechaResolucion = COALESCE(FechaActualizacion, FechaCreacion)
WHERE Tipo = 'incidencia'
  AND Estado = 'pendiente'
  AND FechaResolucion IS NULL
  AND FechaVencimiento IS NULL;

UPDATE gestion_tickets
SET FechaVencimiento = CASE Prioridad
    WHEN 'baja' THEN DATE_ADD(FechaCreacion, INTERVAL 1 MONTH)
    WHEN 'media' THEN DATE_ADD(FechaCreacion, INTERVAL 21 DAY)
    WHEN 'alta' THEN DATE_ADD(FechaCreacion, INTERVAL 14 DAY)
    WHEN 'critica' THEN DATE_ADD(FechaCreacion, INTERVAL 7 DAY)
    ELSE FechaVencimiento
END
WHERE Tipo = 'incidencia' AND FechaVencimiento IS NULL;
