# Guía de Instalación - Nuevos Módulos

## Pasos para la Implementación Completa

### 1. Ejecutar Migraciones de Base de Datos

Los archivos de migración se encuentran en `database/migrations/`:

- `009_create_intranet_tables.sql`
- `010_create_solicitudes_tables.sql`
- `011_create_gestion_tecnologica_tables.sql`

**Opción A: Ejecución Manual via Cliente MySQL**
```bash
mysql -u root -p gacela_conecta_beta < database/migrations/009_create_intranet_tables.sql
mysql -u root -p gacela_conecta_beta < database/migrations/010_create_solicitudes_tables.sql
mysql -u root -p gacela_conecta_beta < database/migrations/011_create_gestion_tecnologica_tables.sql
```

**Opción B: Ejecución via Script PHP**
```bash
cd backend/scripts
php run_migrations.php
```

### 2. Ejecutar Scripts de Permisos

Los scripts de permisos se encuentran en `backend/scripts/`:

- `add_intranet_permissions.php`
- `add_solicitudes_permissions.php`
- `add_gestion_permissions.php`

**Ejecución:**
```bash
cd backend/scripts
php add_intranet_permissions.php
php add_solicitudes_permissions.php
php add_gestion_permissions.php
```

### 3. Verificar Integración Frontend

Los componentes ya están integrados en:
- `frontend/src/router/index.jsx` - Rutas configuradas
- `frontend/src/components/Sidebar.jsx` - Menú actualizado

### 4. Verificar Rutas Backend

Los archivos de rutas ya están creados y se cargan automáticamente:
- `backend/routes/intranet.php`
- `backend/routes/solicitudes.php`
- `backend/routes/gestion.php`

## Verificación

### Verificar Tablas Creadas
```sql
SHOW TABLES LIKE 'intranet_%';
SHOW TABLES LIKE 'solicitudes_%';
SHOW TABLES LIKE 'gestion_%';
```

### Verificar Permisos Creados
```sql
SELECT * FROM gcp_permisos WHERE CodigoUnico LIKE 'intranet.%';
SELECT * FROM gcp_permisos WHERE CodigoUnico LIKE 'solicitudes.%';
SELECT * FROM gcp_permisos WHERE CodigoUnico LIKE 'gestion.%';
```

### Verificar Permisos Asignados al Rol Admin
```sql
SELECT rp.*, p.CodigoUnico 
FROM gcp_rolespermisos rp 
JOIN gcp_permisos p ON p.Id = rp.PermisoId 
WHERE rp.RolId = 1 
AND p.CodigoUnico IN ('intranet.ver', 'solicitudes.ver', 'gestion.ver');
```

## Arquitectura Implementada

### Módulo INTRANET
- **Base de Datos**: 8 tablas (noticias, documentos, eventos, foro)
- **Backend**: 3 repositories, 3 services, 3 controllers
- **Frontend**: 1 API service, 1 componente principal
- **Permisos**: 5 permisos específicos

### Módulo SOLICITUDES
- **Base de Datos**: 7 tablas (solicitudes, categorías, seguimiento, etc.)
- **Backend**: 2 repositories, 2 services, 2 controllers
- **Frontend**: 1 API service, 1 componente principal
- **Permisos**: 4 permisos específicos

### Módulo GESTIÓN TECNOLÓGICA
- **Base de Datos**: 7 tablas (activos, mantenimiento, incidencias, licencias)
- **Backend**: 1 repository, 1 service, 1 controller
- **Frontend**: 1 API service, 1 componente principal
- **Permisos**: 5 permisos específicos

## Solución de Problemas

### Si las migraciones fallan:
1. Verificar credenciales en `.env`
2. Asegurar que la base de datos exista
3. Verificar permisos del usuario MySQL

### Si los scripts de permisos fallan:
1. Verificar que las aplicaciones existan en `gcp_aplicaciones`
2. Asegurar que el rol administrador (Id=1) exista
3. Revisar conexión a la base de datos

### Si el frontend no muestra los módulos:
1. Verificar que los componentes estén importados correctamente
2. Revisar permisos del usuario autenticado
3. Verificar que las rutas API respondan correctamente

## Próximos Pasos

1. **Testing**: Realizar pruebas funcionales de cada módulo
2. **Documentación**: Crear manuales de usuario para cada módulo
3. **Training**: Capacitar a los usuarios en el uso de los nuevos módulos
4. **Monitoring**: Configurar monitoreo de errores y rendimiento