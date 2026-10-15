-- Provide basic equipment categories for the initial technology inventory.
INSERT INTO gestion_activos_categorias
    (Nombre, Descripcion, Codigo, Icono, Color, Estado)
SELECT
    'Computador',
    'Equipos de cómputo para el trabajo.',
    'computador',
    'monitor',
    '#2563eb',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM gestion_activos_categorias WHERE Codigo = 'computador'
);

INSERT INTO gestion_activos_categorias
    (Nombre, Descripcion, Codigo, Icono, Color, Estado)
SELECT
    'Celular',
    'Teléfonos móviles corporativos.',
    'celular',
    'smartphone',
    '#16a34a',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM gestion_activos_categorias WHERE Codigo = 'celular'
);
