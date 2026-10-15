-- Seed: Empresas, Áreas y Categorías de Intranet

INSERT INTO GCP_Intranet_Empresas (Nombre, Codigo, Estado) VALUES
('Flota Magdalena', 'flota_magdalena', 1),
('Rápido Duitama', 'rapido_duitama', 1),
('Expreso Paz de Río', 'expreso_paz_rio', 1)
ON DUPLICATE KEY UPDATE Nombre=VALUES(Nombre);

INSERT INTO GCP_Intranet_Areas (Nombre, Codigo, Estado) VALUES
('Vinculaciones', 'vinculaciones', 1),
('Mantenimiento', 'mantenimiento', 1),
('Torre de Control', 'torre_control', 1),
('Calidad', 'calidad', 1),
('Tecnología', 'tecnologia', 1),
('Gerencia', 'gerencia', 1),
('Servicio al Cliente', 'servicio_cliente', 1),
('Gestión Humana', 'gestion_humana', 1),
('Compras y Proveedores', 'compras_proveedores', 1)
ON DUPLICATE KEY UPDATE Nombre=VALUES(Nombre);

INSERT INTO GCP_Intranet_Categorias (Nombre, Codigo, Estado) VALUES
('Caracterización', 'caracterizacion', 1),
('Desempeño', 'desempeno', 1),
('Formatos', 'formatos', 1),
('Matrices', 'matrices', 1),
('Procedimientos', 'procedimientos', 1)
ON DUPLICATE KEY UPDATE Nombre=VALUES(Nombre);
