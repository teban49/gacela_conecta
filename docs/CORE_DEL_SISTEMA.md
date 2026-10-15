# Documentación Técnica del Core del Sistema Gacela Conecta

## Tabla de Contenidos
1. [Arquitectura General](#arquitectura-general)
2. [Autenticación y Autorización](#autenticación-y-autorización)
3. [Sistema de Permisos RBAC](#sistema-de-permisos-rbac)
4. [Middleware y Protección de Rutas](#middleware-y-protección-de-rutas)
5. [Sesiones y Invalidación Inmediata](#sesiones-e-invalidación-inmediata)
6. [Sidebar Dinámico](#sidebar-dinámico)
7. [Patrones de Desarrollo](#patrones-de-desarrollo)
8. [Estructura de Archivos](#estructura-de-archivos)

---

## Arquitectura General

El sistema sigue una arquitectura de capas con separación clara entre frontend y backend:

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (React)                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Components  │  │   Services   │  │    Contexts  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP/REST API
┌───────────────────────────┴─────────────────────────────────┐
│                    Backend (PHP)                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │→│   Services   │→│  Repositories│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│         ↓              ↓                  ↓                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Middleware  │  │   Response   │  │   Database   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Principios de Diseño

1. **Core Estable**: Todos los módulos dependen del Core, nunca al contrario
2. **Seguridad Primero**: Cada petición está protegida por middleware
3. **Invalidación Inmediata**: Cambios en permisos/estado se reflejan en tiempo real
4. **Escalabilidad**: Patrones reutilizables para nuevos módulos

---

## Autenticación y Autorización

### Flujo de Autenticación

```
1. Usuario solicita magic-link
   ↓
2. Sistema genera token JWT temporal
   ↓
3. Usuario ingresa con magic-link
   ↓
4. Sistema valida token y genera sesión JWT
   ↓
5. Frontend almacena token en localStorage
   ↓
6. Cada petición incluye token en header Authorization
```

### JWT Service

**Ubicación**: `backend/app/Core/JwtService.php`

**Funciones clave**:
- `encode()`: Genera token JWT con payload
- `decode()`: Decodifica y valida token
- `validate()`: Verifica firma y expiración
- `generateMagicLinkToken()`: Token temporal para magic-link

**Payload del Token**:
```json
{
  "sub": 1,
  "email": "usuario@empresa.com",
  "roles": ["admin"],
  "permissions": ["dashboard.ver", "seguridad.ver"],
  "iat": 1234567890,
  "exp": 1234571490
}
```

### AuthContext (Frontend)

**Ubicación**: `frontend/src/contexts/AuthContext.jsx`

**Funcionalidades**:
- Gestión de estado de autenticación
- Validación periódica de sesión (cada 5 minutos)
- Invalidación inmediata si usuario es desactivado
- Refresh de datos de usuario

**Métodos expuestos**:
```javascript
{
  user,           // Datos del usuario
  loading,        // Estado de carga
  isAuthenticated,// Estado de autenticación
  login,          // Iniciar sesión
  logout,         // Cerrar sesión
  hasPermission, // Verificar permiso específico
  hasRole,        // Verificar rol específico
  refreshUserData,// Forzar refresh de datos
}
```

---

## Sistema de Permisos RBAC

### Estructura de Base de Datos

```
gcp_aplicaciones
  ↓
gcp_permisos (AplicacionId)
  ↓
gcp_rolespermisos (PermisoId, RolId)
  ↓
gcp_usuarios.RolId
  ↓
gcp_usuarios
```

### Cálculo de Permisos Efectivos

**Backend**: `backend/app/Models/User.php`

```php
public function getUserPermissions(int $userId): array
{
    // SuperAdmins tienen todos los permisos
    if (in_array($userId, [1, 2])) {
        return getAllPermissions();
    }
    
    // Permisos por roles
    SELECT DISTINCT p.CodigoUnico
    FROM gcp_permisos p
    INNER JOIN gcp_rolespermisos rp ON p.Id = rp.PermisoId
    INNER JOIN gcp_usuarios u ON rp.RolId = u.RolId
    WHERE u.Id = :userId AND p.Estado = 1
}
```

**Frontend**: `frontend/src/contexts/AuthContext.jsx`

```javascript
const hasPermission = (permission) => {
  if (!user || !user.permissions) return false;
  return user.permissions.includes(permission);
};
```

### Permisos del Sistema

**Formato**: `{modulo}.{accion}`

Ejemplos:
- `dashboard.ver` - Ver dashboard
- `seguridad.administrar_usuarios` - Administrar usuarios
- `intranet.administrar_noticias` - Administrar noticias
- `solicitudes.crear` - Crear solicitudes
- `gestion.administrar_activos` - Administrar activos

---

## Middleware y Protección de Rutas

### Middleware JWT

**Ubicación**: `backend/app/Middleware/JwtMiddleware.php`

**Funcionalidades**:
1. Extrae token del header Authorization
2. Valida firma y expiración del token
3. Verifica estado del usuario en cada petición
4. Carga roles y permisos actualizados
5. Establece usuario en Auth global

**Validación de Estado**:
```php
if (!$user['Estado']) {
    Response::unauthorized('Usuario desactivado. Contacte al administrador.');
    return;
}
```

### Middleware de Permisos

**Ubicación**: `backend/app/Middleware/PermissionMiddleware.php`

**Funcionalidades**:
1. Verifica permiso específico por ruta
2. Bypass para administradores (admin, superadmin)
3. Respuesta 403 Forbidden si no tiene permiso

**Uso en Rutas**:
```php
Router::get('/api/v1/security/users', function() {
    $controller->getUsers();
}, ['jwt', 'seguridad.ver']);
```

### Protección Frontend

**Ubicación**: `frontend/src/router/PermissionRoute.jsx`

**Funcionalidades**:
1. Verifica permiso antes de renderizar componente
2. Muestra pantalla de acceso denegado si no tiene permiso
3. Permite acceso total a administradores

```javascript
<PermissionRoute requiredPermission="seguridad.ver">
    <SecurityPage />
</PermissionRoute>
```

---

## Sesiones e Invalidación Inmediata

### Validación Periódica

**Frontend**: Cada 5 minutos

```javascript
useEffect(() => {
  const interval = setInterval(validateSession, 5 * 60 * 1000);
  return () => clearInterval(interval);
}, []);
```

### Invalidación Inmediata

**Escenario**: Administrador desactiva usuario

**Flujo**:
1. Admin cambia `Estado = 0` en base de datos
2. Usuario hace próxima petición (max 5 min después)
3. `JwtMiddleware` detecta estado inactivo
4. Responde 401 Unauthorized
5. Frontend limpia localStorage y redirige a login

**Backend**: `validateSessionToken()`
```php
if (!$user['Estado']) {
    return null; // Invalida sesión inmediatamente
}
```

**Frontend**: API Interceptor
```javascript
if (error.response?.status === 401) {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    window.location.href = '/login';
}
```

---

## Sidebar Dinámico

### Generación desde Base de Datos

**Frontend**: `frontend/src/components/Sidebar.jsx`

**Flujo**:
1. Obtiene aplicaciones desde `/api/v1/security/applications`
2. Filtra por `Estado = true` y `Visible = true`
3. Ordena por `OrdenMenu`
4. Verifica permisos del usuario para cada aplicación
5. Renderiza solo aplicaciones con permiso

**Código de Iconos**:
```javascript
const iconMap = {
    'layout-dashboard': LayoutDashboard,
    'book': BookOpen,
    'clipboard-list': ClipboardList,
    'package': Package,
    'shield': Shield,
    // ...
};
```

**Fallback**: Si API falla, usa menú hardcoded como respaldo

---

## Patrones de Desarrollo

### Patrón Repository-Service-Controller

**Repository**: Acceso a datos
```php
class UserRepository extends BaseRepository
{
    protected string $table = 'gcp_usuarios';
    
    public function getUserRoles(int $userId): array { }
    public function syncRoles(int $userId, array $roleIds): bool { }
}
```

**Service**: Lógica de negocio
```php
class UserService extends BaseService
{
    public function syncRoles(int $id, array $roleIds): array
    {
        // Validaciones
        // Lógica de negocio
        // Llamada a repository
    }
}
```

**Controller**: Manejo de HTTP
```php
class UserController
{
    public function syncRoles(int $id): void
    {
        $data = Request::json();
        $result = $this->service->syncRoles($id, $data['Roles']);
        Response::success($result['data'], $result['message']);
    }
}
```

### Patrón BaseService

**Ubicación**: `backend/app/Services/BaseService.php`

**Propósito**: Evitar duplicación de código en CRUDs

**Métodos heredados**:
- `getAll()`
- `getById()`
- `create()`
- `update()`
- `changeStatus()`

**Personalización**:
```php
class ApplicationService extends BaseService
{
    protected function getCreateSuccessMessage(): string
    {
        return 'Aplicación creada correctamente.';
    }
}
```

---

## Estructura de Archivos

### Backend

```
backend/
├── app/
│   ├── Controllers/
│   │   ├── Security/
│   │   │   ├── ApplicationController.php
│   │   │   ├── PermissionController.php
│   │   │   ├── RoleController.php
│   │   │   └── UserController.php
│   │   └── Auth/
│   │       └── AuthController.php
│   ├── Core/
│   │   ├── Auth.php
│   │   ├── Database.php
│   │   ├── JwtService.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   └── Router.php
│   ├── Middleware/
│   │   ├── JwtMiddleware.php
│   │   └── PermissionMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   └── AuthToken.php
│   ├── Repositories/
│   │   ├── BaseRepository.php
│   │   └── Security/
│   │       ├── UserRepository.php
│   │       ├── RoleRepository.php
│   │       ├── PermissionRepository.php
│   │       └── ApplicationRepository.php
│   └── Services/
│       ├── BaseService.php
│       ├── Auth/
│       │   └── AuthService.php
│       └── Security/
│           ├── UserService.php
│           ├── RoleService.php
│           ├── PermissionService.php
│           └── ApplicationService.php
└── routes/
    ├── auth.php
    └── security.php
```

### Frontend

```
frontend/
├── src/
│   ├── components/
│   │   ├── MainLayout.jsx
│   │   └── Sidebar.jsx
│   ├── contexts/
│   │   └── AuthContext.jsx
│   ├── modules/
│   │   ├── security/
│   │   │   ├── pages/
│   │   │   │   └── SecurityPage.jsx
│   │   │   └── services/
│   │   │       └── securityAPI.js
│   │   └── auth/
│   │       └── pages/
│   │           ├── LoginPage.jsx
│   │           └── VerifyPage.jsx
│   ├── router/
│   │   ├── index.jsx
│   │   ├── PermissionRoute.jsx
│   │   └── PrivateRoute.jsx
│   └── services/
│       ├── api.js
│       └── applicationsAPI.js
```

---

## Guía para Desarrollar Nuevos Módulos

### 1. Crear Tablas de Base de Datos

```sql
CREATE TABLE modulo_entidades (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(255) NOT NULL,
    Estado TINYINT(1) DEFAULT 1,
    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. Crear Repository

```php
class ModuloRepository extends BaseRepository
{
    protected string $table = 'modulo_entidades';
    
    public function customMethod(): array { }
}
```

### 3. Crear Service

```php
class ModuloService extends BaseService
{
    protected function getRepository(): ModuloRepository
    {
        return new ModuloRepository();
    }
    
    public function customMethod(): array { }
}
```

### 4. Crear Controller

```php
class ModuloController
{
    private ModuloService $service;
    
    public function __construct()
    {
        $this->service = new ModuloService();
    }
    
    public function index(): void
    {
        Response::success($this->service->getAll());
    }
}
```

### 5. Definir Rutas

```php
Router::get('/api/v1/modulo/entidades', function() {
    $controller = new ModuloController();
    $controller->index();
}, ['jwt', 'modulo.ver']);
```

### 6. Crear Frontend API Service

```javascript
export const moduloAPI = {
  getEntities: () => api.get('/modulo/entidades'),
  createEntity: (data) => api.post('/modulo/entidades', data),
  // ...
};
```

### 7. Crear Frontend Componente

```javascript
const ModuloPage = () => {
  const { hasPermission } = useAuth();
  const [entities, setEntities] = useState([]);
  
  useEffect(() => {
    loadEntities();
  }, []);
  
  // CRUD implementation
};
```

### 8. Agregar al Router

```javascript
{
  path: '/modulo',
  element: (
    <PermissionRoute requiredPermission="modulo.ver">
      <ModuloPage />
    </PermissionRoute>
  ),
}
```

### 9. Crear Permisos

```sql
INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado)
VALUES (1, 'modulo', 'Ver', 'Ver módulo', 'modulo.ver', 1);
```

### 10. Asignar Permisos al Rol Admin

```sql
INSERT INTO gcp_rolespermisos (RolId, PermisoId)
SELECT 1, Id FROM gcp_permisos WHERE CodigoUnico = 'modulo.ver';
```

---

## Checklist de Validación del Core

### Seguridad
- [x] Autenticación JWT funcional
- [x] Invalidación inmediata de sesión
- [x] Validación de estado en cada petición
- [x] Middleware de permisos
- [x] Protección de rutas Frontend y Backend

### Usuarios
- [x] CRUD completo
- [x] Asignación de roles
- [x] Consulta de permisos efectivos
- [x] Activación/Desactivación

### Roles
- [x] CRUD completo
- [x] Asignación de permisos
- [x] Consulta de usuarios asociados
- [x] Activación/Desactivación

### Permisos
- [x] CRUD completo
- [x] Asignación por aplicación
- [x] Código único
- [x] Estado

### Aplicaciones
- [x] CRUD completo
- [x] Control de visibilidad
- [x] Control de orden
- [x] Iconos y colores personalizados

### UI
- [x] Sidebar dinámico desde BD
- [x] Pantallas de acceso denegado
- [x] Manejo de errores HTTP
- [x] Interfaz completa sin placeholders

---

## Conclusión

El Core del sistema Gacela Conecta está completamente funcional y estable. Todos los módulos futuros deben:

1. **Depender del Core**: Usar servicios de autenticación, autorización y permisos existentes
2. **Seguir Patrones**: Repository-Service-Controller y BaseService
3. **Respetar Seguridad**: Proteger todas las rutas con middleware apropiados
4. **Usar Permisos**: Sistema RBAC granular para control de acceso
5. **Invalidación Inmediata**: Reforzar validación de estado en operaciones críticas

El sistema está listo para el desarrollo de módulos de negocio con una base sólida, segura y escalable.