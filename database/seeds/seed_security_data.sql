-- Seed: Datos iniciales de seguridad
-- Description: Insertar aplicaciones, permisos y roles iniciales
-- Version: 1.0
-- Date: 2026-08-05

-- Desactivar restricciones de clave foránea temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar datos existentes
TRUNCATE TABLE gcp_rolespermisos;
TRUNCATE TABLE gcp_permisos;
TRUNCATE TABLE gcp_roles;
TRUNCATE TABLE gcp_aplicaciones;

-- Insertar Aplicaciones
INSERT INTO gcp_aplicaciones (Nombre, Codigo, Ruta, Color, Icono, OrdenMenu, Visible, Estado) VALUES
('Dashboard', 'dashboard', '/dashboard', '#6366f1', 'layout-dashboard', 1, 1, 1),
('Intranet', 'intranet', '/intranet', '#8b5cf6', 'newspaper', 2, 1, 1),
('Incidencias', 'incidencias', '/incidencias', '#ef4444', 'alert-triangle', 3, 1, 1),
('Solicitudes', 'solicitudes', '/solicitudes', '#f59e0b', 'file-text', 4, 1, 1),
('Inventario', 'inventario', '/inventario', '#10b981', 'package', 5, 1, 1),
('Fondo de Reposición', 'fondo_reposicion', '/fondo-reposicion', '#06b6d4', 'wallet', 6, 1, 1),
('Seguridad', 'seguridad', '/seguridad', '#ec4899', 'shield', 7, 1, 1),
('Configuración', 'configuracion', '/configuracion', '#64748b', 'settings', 8, 1, 1);

-- Insertar Permisos para cada aplicación
-- Dashboard
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES
(1, 'dashboard', 'Ver', 'Ver dashboard', 'dashboard.ver', 1),
(1, 'dashboard', 'Administrar', 'Administrar dashboard', 'dashboard.administrar', 1);

-- Intranet
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES
(2, 'intranet', 'Ver', 'Ver intranet', 'intranet.ver', 1),
(2, 'intranet', 'Crear Noticias', 'Crear noticias en intranet', 'intranet.crear_noticias', 1),
(2, 'intranet', 'Editar Noticias', 'Editar noticias en intranet', 'intranet.editar_noticias', 1),
(2, 'intranet', 'Eliminar Noticias', 'Eliminar noticias en intranet', 'intranet.eliminar_noticias', 1),
(2, 'intranet', 'Administrar', 'Administrar intranet', 'intranet.administrar', 1);

-- Incidencias
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES
(3, 'incidencias', 'Ver', 'Ver incidencias', 'incidencias.ver', 1),
(3, 'incidencias', 'Crear', 'Crear incidencias', 'incidencias.crear', 1),
(3, 'incidencias', 'Editar', 'Editar incidencias', 'incidencias.editar', 1),
(3, 'incidencias', 'Eliminar', 'Eliminar incidencias', 'incidencias.eliminar', 1),
(3, 'incidencias', 'Asignar', 'Asignar incidencias', 'incidencias.asignar', 1),
(3, 'incidencias', 'Cerrar', 'Cerrar incidencias', 'incidencias.cerrar', 1),
(3, 'incidencias', 'Exportar', 'Exportar incidencias', 'incidencias.exportar', 1),
(3, 'incidencias', 'Administrar', 'Administrar incidencias', 'incidencias.administrar', 1);

-- Solicitudes
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES
(4, 'solicitudes', 'Ver', 'Ver solicitudes', 'solicitudes.ver', 1),
(4, 'solicitudes', 'Crear', 'Crear solicitudes', 'solicitudes.crear', 1),
(4, 'solicitudes', 'Editar', 'Editar solicitudes', 'solicitudes.editar', 1),
(4, 'solicitudes', 'Eliminar', 'Eliminar solicitudes', 'solicitudes.eliminar', 1),
(4, 'solicitudes', 'Aprobar', 'Aprobar solicitudes', 'solicitudes.aprobar', 1),
(4, 'solicitudes', 'Rechazar', 'Rechazar solicitudes', 'solicitudes.rechazar', 1),
(4, 'solicitudes', 'Exportar', 'Exportar solicitudes', 'solicitudes.exportar', 1),
(4, 'solicitudes', 'Administrar', 'Administrar solicitudes', 'solicitudes.administrar', 1);

-- Inventario
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES
(5, 'inventario', 'Ver', 'Ver inventario', 'inventario.ver', 1),
(5, 'inventario', 'Crear', 'Crear items de inventario', 'inventario.crear', 1),
(5, 'inventario', 'Editar', 'Editar items de inventario', 'inventario.editar', 1),
(5, 'inventario', 'Eliminar', 'Eliminar items de inventario', 'inventario.eliminar', 1),
(5, 'inventario', 'Exportar', 'Exportar inventario', 'inventario.exportar', 1),
(5, 'inventario', 'Importar', 'Importar inventario', 'inventario.importar', 1),
(5, 'inventario', 'Administrar', 'Administrar inventario', 'inventario.administrar', 1);

-- Fondo de Reposición
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES
(6, 'fondo_reposicion', 'Ver', 'Ver fondo de reposición', 'fondo_reposicion.ver', 1),
(6, 'fondo_reposicion', 'Crear', 'Crear solicitudes de fondo', 'fondo_reposicion.crear', 1),
(6, 'fondo_reposicion', 'Editar', 'Editar solicitudes de fondo', 'fondo_reposicion.editar', 1),
(6, 'fondo_reposicion', 'Eliminar', 'Eliminar solicitudes de fondo', 'fondo_reposicion.eliminar', 1),
(6, 'fondo_reposicion', 'Aprobar', 'Aprobar solicitudes de fondo', 'fondo_reposicion.aprobar', 1),
(6, 'fondo_reposicion', 'Rechazar', 'Rechazar solicitudes de fondo', 'fondo_reposicion.rechazar', 1),
(6, 'fondo_reposicion', 'Exportar', 'Exportar fondo de reposición', 'fondo_reposicion.exportar', 1),
(6, 'fondo_reposicion', 'Administrar', 'Administrar fondo de reposición', 'fondo_reposicion.administrar', 1);

-- Seguridad
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES
(7, 'seguridad', 'Ver', 'Ver módulo de seguridad', 'seguridad.ver', 1),
(7, 'seguridad', 'Administrar Aplicaciones', 'Administrar aplicaciones', 'seguridad.administrar_aplicaciones', 1),
(7, 'seguridad', 'Administrar Permisos', 'Administrar permisos', 'seguridad.administrar_permisos', 1),
(7, 'seguridad', 'Administrar Roles', 'Administrar roles', 'seguridad.administrar_roles', 1),
(7, 'seguridad', 'Administrar Usuarios', 'Administrar usuarios', 'seguridad.administrar_usuarios', 1),
(7, 'seguridad', 'Administrar', 'Administrar seguridad completa', 'seguridad.administrar', 1);

-- Configuración
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES
(8, 'configuracion', 'Ver', 'Ver configuración', 'configuracion.ver', 1),
(8, 'configuracion', 'Editar', 'Editar configuración', 'configuracion.editar', 1),
(8, 'configuracion', 'Administrar', 'Administrar configuración', 'configuracion.administrar', 1);

-- Insertar Roles
INSERT INTO gcp_roles (Nombre, Codigo, Descripcion, NivelAcceso, Estado) VALUES
('Administrador', 'admin', 'Acceso total al sistema', 10, 1),
('Analista TI', 'analista_ti', 'Soporte técnico y desarrollo', 5, 1),
('Supervisor TI', 'supervisor_ti', 'Supervisión de área TI', 7, 1),
('Gerencia', 'gerencia', 'Nivel gerencial', 8, 1),
('Recursos Humanos', 'rrhh', 'Gestión de personal', 6, 1),
('Operaciones', 'operaciones', 'Operaciones del negocio', 4, 1),
('Tesorería', 'tesoreria', 'Gestión financiera', 5, 1),
('Usuario Estándar', 'usuario', 'Acceso básico', 1, 1);

UPDATE gcp_usuarios
SET RolId = (SELECT Id FROM gcp_roles WHERE Codigo = 'usuario' LIMIT 1);

-- Reactivar restricciones de clave foránea
SET FOREIGN_KEY_CHECKS = 1;
