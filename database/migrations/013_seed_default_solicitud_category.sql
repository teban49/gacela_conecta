-- Add a usable default category when the ticket module has none configured.
INSERT INTO gestion_tickets_categorias
    (Nombre, Descripcion, Codigo, Icono, Color, Prioridad, Estado, TiempoRespuestaHoras)
SELECT
    'General',
    'Solicitudes generales de soporte y servicio.',
    CASE
        WHEN EXISTS (
            SELECT 1
            FROM gestion_tickets_categorias
            WHERE Codigo = 'general'
        ) THEN NULL
        ELSE 'general'
    END,
    'ticket',
    '#f97316',
    0,
    1,
    24
WHERE NOT EXISTS (
    SELECT 1
    FROM gestion_tickets_categorias
    WHERE Estado = 1
);
