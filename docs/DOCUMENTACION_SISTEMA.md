# Documentación Completa del Sistema Gacela Conecta

## Tabla de Contenidos
1. [Arquitectura General](#arquitectura-general)
2. [Estructura de Base de Datos](#estructura-de-base-de-datos)
3. [Backend - PHP](#backend---php)
4. [Frontend - React](#frontend---react)
5. [Módulo de Seguridad](#módulo-de-seguridad)
6. [Módulo de Intranet](#módulo-de-intranet)
7. [Módulo de Solicitudes](#módulo-de-solicitudes)
8. [Módulo de Gestión Tecnológica](#módulo-de-gestión-tecnológica)
9. [Autenticación y Autorización](#autenticación-y-autorización)
10. [API Endpoints](#api-endpoints)

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
│                            │                                  │
│                      ┌─────┴─────┐                          │
│                      │  Models   │                          │
│                      └───────────┘                          │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────┴─────────────────────────────────┐
│                  Base de Datos (MySQL)                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │Usuarios  │  │  Roles   │  │Permisos │  │Aplicacio │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘    │
└─────────────────────────────────────────────────────────────┘
```

---

## Estructura de Base de Datos

### Tablas Principales

#### 1. `gcp_usuarios`
**Ubicación:** `database/migrations/001_create_gcp_usuarios_table.sql`

**Campos:**
- `Id` (INT, PK, AUTO_INCREMENT)
- `GrupoId` (INT) - ID del grupo corporativo
- `EmpresaId` (INT) - ID de la empresa
- `CiudadId` (INT) - ID de la ciudad
- `AgenciaId` (INT) - ID de la agencia
- `AreaId` (INT) - ID del área
- `CargoId` (INT) - ID del cargo
- `Nombres` (VARCHAR 100)
- `Apellidos` (VARCHAR 100)
- `EmailCorporativo` (VARCHAR 150, UNIQUE)
- `Telefono` (VARCHAR 20)
- `Estado` (TINYINT) - 1=Activo, 0=Inactivo
- `FechaCreacion` (DATETIME)
- `FechaActualizacion` (DATETIME)
- `CreadoPor` (INT)
- `ActualizadoPor` (INT)

**Índices:**
- IDX_grupo, IDX_empresa, IDX_ciudad, IDX_agencia, IDX_area, IDX_cargo, IDX_email, IDX_estado

#### 2. `gcp_aplicaciones`
**Ubicación:** `database/migrations/006_create_gcp_aplicaciones_table.sql`

**Campos:**
- `Id` (INT, PK, AUTO_INCREMENT)
- `Nombre` (VARCHAR 100) - Nombre de la aplicación/módulo
- `Codigo` (VARCHAR 50, UNIQUE) - Código identificador
- `Ruta` (VARCHAR 255) - Ruta URL de la aplicación
- `Color` (VARCHAR 7) - Color hexadecimal para UI
- `Icono` (VARCHAR 50) - Nombre del icono
- `OrdenMenu` (INT) - Orden de visualización en menú
- `Visible` (TINYINT) - 1=Visible, 0=Oculto
- `Estado` (TINYINT) - 1=Activo, 0=Inactivo
- `FechaCreacion` (DATETIME)
- `FechaActualizacion` (DATETIME)

**Índices:**
- IDX_codigo, IDX_estado, IDX_orden

#### 3. `gcp_permisos`
**Ubicación:** `database/migrations/004_create_gcp_rbac_tables.sql` (modificada en 007)

**Campos:**
- `Id` (INT, PK, AUTO_INCREMENT)
- `AplicacionId` (INT, FK → gcp_aplicaciones.Id)
- `Modulo` (VARCHAR 100) - Nombre del módulo
- `Accion` (VARCHAR 100) - Acción específica (crear, editar, eliminar, etc.)
- `Descripcion` (TEXT) - Descripción del permiso
- `CodigoUnico` (VARCHAR 200, UNIQUE) - Código único (ej: dashboard.ver)
- `Estado` (TINYINT) - 1=Activo, 0=Inactivo
- `FechaCreacion` (DATETIME)

**Índices:**
- IDX_modulo, IDX_codigo, IDX_estado, IDX_aplicacion

**Relaciones:**
- FK_permisos_aplicacion → gcp_aplicaciones(Id)

#### 4. `gcp_roles`
**Ubicación:** `database/migrations/004_create_gcp_rbac_tables.sql` (modificada en 008)

**Campos:**
- `Id` (INT, PK, AUTO_INCREMENT)
- `Nombre` (VARCHAR 100) - Nombre del rol
- `Codigo` (VARCHAR 50, UNIQUE) - Código identificador
- `Descripcion` (TEXT) - Descripción del rol
- `NivelAcceso` (INT) - Nivel de acceso (1=Básico, 10=Administrador)
- `Estado` (TINYINT) - 1=Activo, 0=Inactivo
- `FechaCreacion` (DATETIME)
- `FechaActualizacion` (DATETIME)

**Índices:**
- IDX_codigo, IDX_estado

#### 5. `gcp_rolespermisos`
**Ubicación:** `database/migrations/004_create_gcp_rbac_tables.sql`

**Campos:**
- `Id` (INT, PK, AUTO_INCREMENT)
- `RolId` (INT, FK → gcp_roles.Id)
- `PermisoId` (INT, FK → gcp_permisos.Id)
- `FechaCreacion` (DATETIME)

**Índices:**
- IDX_rol, IDX_permiso
- UK_rol_permiso (RolId, PermisoId) - UNIQUE

**Relaciones:**
- FK_rolespermisos_rol → gcp_roles(Id) ON DELETE CASCADE
- FK_rolespermisos_permiso → gcp_permisos(Id) ON DELETE CASCADE

#### 6. `gcp_usuarios.RolId`
Cada usuario tiene un único rol, referenciado directamente hacia `gcp_roles.Id`.

#### 7. `gcp_authtokens`
**Ubicación:** `database/migrations/002_create_gcp_authtokens_table.sql`

**Campos:**
- `Id` (INT, PK, AUTO_INCREMENT)
- `UsuarioId` (INT, FK → gcp_usuarios.Id)
- `Token` (VARCHAR 255)
- `Tipo` (VARCHAR 50) - Tipo de token (magic_link, refresh, etc.)
- `ExpiraEn` (DATETIME)
- `Usado` (TINYINT) - 1=Usado, 0=No usado
- `FechaCreacion` (DATETIME)

#### 8. `gcp_organizaciones`
**Ubicación:** `database/migrations/003_create_gcp_organizaciones_table.sql`

Contiene las tablas de organizaciones:
- `gcp_grupos`
- `gcp_empresas`
- `gcp_ciudades`
- `gcp_agencias`
- `gcp_areas`
- `gcp_cargos`

#### 9. `gcp_audit`
**Ubicación:** `database/migrations/005_create_gcp_audit_table.sql`

Tabla de auditoría para registrar acciones del sistema.

---

## Backend - PHP

### Estructura de Directorios

```
backend/
├── app/
│   ├── Controllers/
│   │   ├── Administration/
│   │   │   ├── CompanyController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── MenuController.php
│   │   │   ├── PositionController.php
│   │   │   ├── RoleController.php
│   │   │   └── UserController.php
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   └── Security/
│   │       ├── ApplicationController.php
│   │       ├── PermissionController.php
│   │       ├── RoleController.php
│   │       └── UserController.php
│   ├── Core/
│   │   ├── Application.php
│   │   ├── Auth.php
│   │   ├── Config.php
│   │   ├── Database.php
│   │   ├── JwtService.php
│   │   ├── Kernel.php
│   │   ├── Logger.php
│   │   ├── Mail.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Router.php
│   │   └── Validator.php
│   ├── Middleware/
│   │   ├── AuditMiddleware.php
│   │   ├── AuthMiddleware.php
│   │   ├── CorsMiddleware.php
│   │   ├── JwtMiddleware.php
│   │   └── PermissionMiddleware.php
│   ├── Models/
│   │   ├── AuthToken.php
│   │   ├── Company.php
│   │   ├── Department.php
│   │   ├── Menu.php
│   │   ├── Position.php
│   │   ├── Role.php
│   │   └── User.php
│   ├── Repositories/
│   │   ├── BaseRepository.php
│   │   ├── Security/
│   │   │   ├── ApplicationRepository.php
│   │   │   ├── PermissionRepository.php
│   │   │   ├── RoleRepository.php
│   │   │   └── UserRepository.php
│   │   ├── MenuRepository.php
│   │   ├── PositionRepository.php
│   │   ├── RoleRepository.php
│   │   └── UserRepository.php
│   ├── Services/
│   │   ├── Administration/
│   │   │   ├── CompanyService.php
│   │   │   ├── DepartmentService.php
│   │   │   ├── MenuService.php
│   │   │   ├── PositionService.php
│   │   │   ├── RoleService.php
│   │   │   └── UserService.php
│   │   ├── Auth/
│   │   │   └── AuthService.php
│   │   └── Security/
│   │       ├── ApplicationService.php
│   │       ├── PermissionService.php
│   │       ├── RoleService.php
│   │       └── UserService.php
│   └── Validators/
│       ├── AuthValidator.php
│       ├── CompanyValidator.php
│       ├── RoleValidator.php
│       └── UserValidator.php
├── config/
│   ├── app.php
│   ├── constants.php
│   ├── cors.php
│   ├── database.php
│   ├── filesystem.php
│   ├── jwt.php
│   └── mail.php
├── public/
│   └── index.php
├── routes/
│   ├── api.php
│   ├── auth.php
│   ├── companies.php
│   ├── departments.php
│   ├── menus.php
│   ├── positions.php
│   ├── roles.php
│   ├── security.php
│   └── users.php
├── scripts/
│   ├── migrate.php
│   ├── seed.php
│   ├── manual_seed.php
│   ├── alter_roles.php
│   ├── alter_permissions.php
│   ├── check_users.php
│   ├── check_applications.php
│   ├── check_permissions.php
│   ├── check_table_structure.php
│   ├── assign_admin_role.php
│   ├── debug_roles.php
│   ├── direct_check.php
│   └── test_seed.php
├── .env
├── composer.json
└── composer.lock
```

### Core

#### Database.php
**Ubicación:** `backend/app/Core/Database.php`

**Función:** Maneja la conexión a la base de datos usando PDO.

**Método principal:**
- `getConnection(): PDO` - Retorna una instancia PDO configurada

**Configuración:** Lee desde `config/database.php` y variables de entorno.

#### JwtService.php
**Ubicación:** `backend/app/Core/JwtService.php`

**Función:** Servicio para generar y validar tokens JWT.

**Métodos principales:**
- `init(array $config): void` - Inicializa la configuración JWT
- `encode(array $payload): string` - Genera un token JWT
- `decode(string $token): ?object` - Decodifica un token JWT
- `validate(string $token): bool` - Valida si un token es válido
- `generateMagicLinkToken(int $userId): string` - Genera token para magic link
- `hashToken(string $token): string` - Hashea un token

**Dependencias:** `firebase/php-jwt`

#### Auth.php
**Ubicación:** `backend/app/Core/Auth.php`

**Función:** Maneja la autenticación y estado del usuario.

**Métodos principales:**
- `setUser(array $user): void` - Establece el usuario autenticado
- `user(): ?array` - Retorna el usuario autenticado
- `id(): ?int` - Retorna el ID del usuario autenticado
- `check(): bool` - Verifica si hay usuario autenticado
- `logout(): void` - Cierra sesión

#### Request.php
**Ubicación:** `backend/app/Core/Request.php`

**Función:** Maneja las peticiones HTTP.

**Métodos principales:**
- `json(): array` - Retorna el body JSON de la petición
- `input(string $key, $default = null)` - Retorna un input específico
- `method(): string` - Retorna el método HTTP
- `uri(): string` - Retorna la URI de la petición

#### Response.php
**Ubicación:** `backend/app/Core/Response.php`

**Función:** Maneja las respuestas HTTP.

**Métodos principales:**
- `json(array $data, int $status = 200): void` - Envía respuesta JSON
- `success($data, string $message = ''): void` - Respuesta de éxito
- `error(string $message, int $status = 400): void` - Respuesta de error
- `unauthorized(string $message = 'Unauthorized'): void` - Respuesta 401
- `forbidden(string $message = 'Forbidden'): void` - Respuesta 403
- `notFound(string $message = 'Not Found'): void` - Respuesta 404

### Middleware

#### JwtMiddleware.php
**Ubicación:** `backend/app/Middleware/JwtMiddleware.php`

**Función:** Valida tokens JWT y verifica estado del usuario en cada petición.

**Flujo:**
1. Extrae token del header `Authorization: Bearer {token}`
2. Valida el token usando `JwtService::validate()`
3. Decodifica el token para obtener el `sub` (user ID)
4. Consulta estado actual del usuario en BD
5. Si el usuario está inactivo, retorna 403 Forbidden
6. Carga roles y permisos del usuario
7. Establece usuario en `Auth::setUser()`

**Método principal:**
- `handle(): void` - Ejecuta la validación

**Dependencias:**
- `JwtService`
- `UserRepository`
- `RoleRepository`
- `Auth`

#### PermissionMiddleware.php
**Ubicación:** `backend/app/Middleware/PermissionMiddleware.php`

**Función:** Valida permisos específicos para acceso a recursos.

**Métodos principales:**
- `handle(): void` - Valida permiso específico (instancia)
- `check(string $permission): bool` - Verifica si usuario tiene permiso
- `checkAny(array $permissions): bool` - Verifica si tiene alguno de los permisos
- `checkAll(array $permissions): bool` - Verifica si tiene todos los permisos

**Uso:**
```php
$middleware = new PermissionMiddleware('dashboard.ver');
$middleware->handle(); // Retorna 403 si no tiene permiso
```

### Repositories

#### BaseRepository.php
**Ubicación:** `backend/app/Repositories/BaseRepository.php`

**Función:** Clase base para todos los repositorios.

**Métodos principales:**
- `findById(int $id): ?array` - Busca por ID
- `getDb(): PDO` - Retorna conexión PDO
- `changeStatus(int $id, bool $status): bool` - Cambia estado genérico

#### ApplicationRepository.php
**Ubicación:** `backend/app/Repositories/Security/ApplicationRepository.php`

**Función:** Acceso a datos de aplicaciones.

**Métodos:**
- `getAll(): array` - Retorna todas las aplicaciones
- `getById(int $id): ?array` - Busca aplicación por ID
- `create(array $application): int` - Crea nueva aplicación
- `update(int $id, array $application): bool` - Actualiza aplicación
- `changeStatus(int $id, bool $status): bool` - Cambia estado
- `changeVisibility(int $id, bool $visible): bool` - Cambia visibilidad en menú

**Tabla:** `gcp_aplicaciones`

#### PermissionRepository.php
**Ubicación:** `backend/app/Repositories/Security/PermissionRepository.php`

**Función:** Acceso a datos de permisos.

**Métodos:**
- `getAll(): array` - Retorna todos los permisos con info de aplicación
- `getById(int $id): ?array` - Busca permiso por ID
- `getByApplication(int $applicationId): array` - Permisos de una aplicación
- `getByCode(string $code): ?array` - Busca por código único
- `create(array $permission): int` - Crea nuevo permiso
- `update(int $id, array $permission): bool` - Actualiza permiso
- `changeStatus(int $id, bool $status): bool` - Cambia estado

**Tabla:** `gcp_permisos`

#### RoleRepository.php
**Ubicación:** `backend/app/Repositories/Security/RoleRepository.php`

**Función:** Acceso a datos de roles.

**Métodos:**
- `getAll(): array` - Retorna todos los roles
- `getById(int $id): ?array` - Busca rol por ID
- `create(array $role): int` - Crea nuevo rol
- `update(int $id, array $role): bool` - Actualiza rol
- `changeStatus(int $id, bool $status): bool` - Cambia estado
- `getRolePermissions(int $roleId): array` - Permisos de un rol
- `syncPermissions(int $roleId, array $permissionIds): bool` - Sincroniza permisos

**Tablas:** `gcp_roles`, `gcp_rolespermisos`

#### UserRepository.php
**Ubicación:** `backend/app/Repositories/Security/UserRepository.php`

**Función:** Acceso a datos de usuarios.

**Métodos:**
- `getAll(): array` - Retorna todos los usuarios con roles
- `getById(int $id): ?array` - Busca usuario por ID
- `getUserRoles(int $userId): array` - Roles de un usuario
- `getUserPermissions(int $userId): array` - Permisos efectivos de un usuario
- `create(array $user): int` - Crea nuevo usuario
- `update(int $id, array $user): bool` - Actualiza usuario
- `changeStatus(int $id, bool $status): bool` - Cambia estado
- `setRole(int $userId, int $roleId): bool` - Asigna el rol único del usuario

**Tablas:** `gcp_usuarios`, `gcp_roles`, `gcp_rolespermisos`, `gcp_permisos`, `gcp_aplicaciones`

### Services

#### ApplicationService.php
**Ubicación:** `backend/app/Services/Security/ApplicationService.php`

**Función:** Lógica de negocio para aplicaciones.

**Métodos:**
- `getAll(): array` - Obtiene todas las aplicaciones
- `getById(int $id): ?array` - Obtiene aplicación por ID
- `create(array $data): array` - Crea nueva aplicación
- `update(int $id, array $data): array` - Actualiza aplicación
- `changeStatus(int $id, bool $status): array` - Cambia estado
- `changeVisibility(int $id, bool $visible): array` - Cambia visibilidad

**Dependencias:** `ApplicationRepository`

#### PermissionService.php
**Ubicación:** `backend/app/Services/Security/PermissionService.php`

**Función:** Lógica de negocio para permisos.

**Métodos:**
- `getAll(): array` - Obtiene todos los permisos
- `getByApplication(int $applicationId): array` - Permisos por aplicación
- `create(array $data): array` - Crea nuevo permiso
- `update(int $id, array $data): array` - Actualiza permiso
- `changeStatus(int $id, bool $status): array` - Cambia estado

**Dependencias:** `PermissionRepository`

#### RoleService.php
**Ubicación:** `backend/app/Services/Security/RoleService.php`

**Función:** Lógica de negocio para roles.

**Métodos:**
- `getAll(): array` - Obtiene todos los roles
- `getById(int $id): ?array` - Obtiene rol por ID
- `getByIdWithPermissions(int $id): array` - Rol con sus permisos
- `create(array $data): array` - Crea nuevo rol
- `update(int $id, array $data): array` - Actualiza rol
- `changeStatus(int $id, bool $status): array` - Cambia estado
- `syncPermissions(int $id, array $permissionIds): array` - Sincroniza permisos

**Dependencias:** `RoleRepository`

#### UserService.php
**Ubicación:** `backend/app/Services/Security/UserService.php`

**Función:** Lógica de negocio para usuarios.

**Métodos:**
- `getAll(): array` - Obtiene todos los usuarios
- `getById(int $id): ?array` - Obtiene usuario por ID
- `getUserRoles(int $id): array` - Roles de un usuario
- `getUserPermissions(int $userId): array` - Permisos efectivos
- `create(array $data): array` - Crea nuevo usuario
- `update(int $id, array $data): array` - Actualiza usuario
- `changeStatus(int $id, bool $status): array` - Cambia estado
- `syncRoles(int $id, array $roleIds): array` - Sincroniza roles

**Dependencias:** `UserRepository`

### Controllers

#### ApplicationController.php
**Ubicación:** `backend/app/Controllers/Security/ApplicationController.php`

**Función:** Maneja peticiones HTTP para aplicaciones.

**Métodos:**
- `index(): void` - GET /api/v1/security/applications
- `show(int $id): void` - GET /api/v1/security/applications/{id}
- `store(): void` - POST /api/v1/security/applications
- `update(int $id): void` - PUT /api/v1/security/applications/{id}
- `changeStatus(int $id): void` - PATCH /api/v1/security/applications/{id}/status
- `changeVisibility(int $id): void` - PATCH /api/v1/security/applications/{id}/visibility

**Dependencias:** `ApplicationService`, `Request`, `Response`

#### PermissionController.php
**Ubicación:** `backend/app/Controllers/Security/PermissionController.php`

**Función:** Maneja peticiones HTTP para permisos.

**Métodos:**
- `index(): void` - GET /api/v1/security/permissions
- `byApplication(int $applicationId): void` - GET /api/v1/security/permissions/application/{applicationId}
- `store(): void` - POST /api/v1/security/permissions
- `update(int $id): void` - PUT /api/v1/security/permissions/{id}
- `changeStatus(int $id): void` - PATCH /api/v1/security/permissions/{id}/status

**Dependencias:** `PermissionService`, `Request`, `Response`

#### RoleController.php
**Ubicación:** `backend/app/Controllers/Security/RoleController.php`

**Función:** Maneja peticiones HTTP para roles.

**Métodos:**
- `index(): void` - GET /api/v1/security/roles
- `show(int $id): void` - GET /api/v1/security/roles/{id}
- `store(): void` - POST /api/v1/security/roles
- `update(int $id): void` - PUT /api/v1/security/roles/{id}
- `changeStatus(int $id): void` - PATCH /api/v1/security/roles/{id}/status
- `syncPermissions(int $id): void` - POST /api/v1/security/roles/{id}/permissions

**Dependencias:** `RoleService`, `Request`, `Response`

#### UserController.php
**Ubicación:** `backend/app/Controllers/Security/UserController.php`

**Función:** Maneja peticiones HTTP para usuarios.

**Métodos:**
- `index(): void` - GET /api/v1/security/users
- `show(int $id): void` - GET /api/v1/security/users/{id}
- `store(): void` - POST /api/v1/security/users
- `update(int $id): void` - PUT /api/v1/security/users/{id}
- `changeStatus(int $id): void` - PATCH /api/v1/security/users/{id}/status
- `syncRoles(int $id): void` - POST /api/v1/security/users/{id}/roles
- `permissions(int $id): void` - GET /api/v1/security/users/{id}/permissions

**Dependencias:** `UserService`, `Request`, `Response`

### Rutas

#### security.php
**Ubicación:** `backend/routes/security.php`

**Rutas definidas:**

```php
// Aplicaciones
GET    /api/v1/security/applications
GET    /api/v1/security/applications/{id}
POST   /api/v1/security/applications
PUT    /api/v1/security/applications/{id}
PATCH  /api/v1/security/applications/{id}/status
PATCH  /api/v1/security/applications/{id}/visibility

// Permisos
GET    /api/v1/security/permissions
GET    /api/v1/security/permissions/application/{applicationId}
POST   /api/v1/security/permissions
PUT    /api/v1/security/permissions/{id}
PATCH  /api/v1/security/permissions/{id}/status

// Roles
GET    /api/v1/security/roles
GET    /api/v1/security/roles/{id}
POST   /api/v1/security/roles
PUT    /api/v1/security/roles/{id}
PATCH  /api/v1/security/roles/{id}/status
POST   /api/v1/security/roles/{id}/permissions

// Usuarios
GET    /api/v1/security/users
GET    /api/v1/security/users/{id}
POST   /api/v1/security/users
PUT    /api/v1/security/users/{id}
PATCH  /api/v1/security/users/{id}/status
POST   /api/v1/security/users/{id}/roles
GET    /api/v1/security/users/{id}/permissions
```

---

## Frontend - React

### Estructura de Directorios

```
frontend/
├── public/
├── src/
│   ├── components/
│   ├── contexts/
│   │   └── AuthContext.jsx
│   ├── hooks/
│   │   └── useAuth.js
│   ├── modules/
│   │   ├── auth/
│   │   │   ├── components/
│   │   │   │   ├── EmailInput.jsx
│   │   │   │   ├── LoginCard.jsx
│   │   │   │   ├── LoginFooter.jsx
│   │   │   │   └── LoginForm.jsx
│   │   │   ├── hooks/
│   │   │   │   └── useLogin.js
│   │   │   ├── pages/
│   │   │   │   ├── CheckEmailPage.jsx
│   │   │   │   ├── LoginPage.jsx
│   │   │   │   └── VerifyPage.jsx
│   │   │   ├── routes.jsx
│   │   │   ├── types/
│   │   │   │   └── auth.types.js
│   │   │   ├── utils/
│   │   │   │   └── auth.utils.js
│   │   │   └── index.js
│   │   ├── security/
│   │   │   ├── pages/
│   │   │   │   └── SecurityPage.jsx
│   │   │   ├── services/
│   │   │   │   └── securityAPI.js
│   │   │   └── index.js
│   │   ├── companies/
│   │   ├── dashboard/
│   │   ├── departments/
│   │   ├── menus/
│   │   ├── positions/
│   │   ├── roles/
│   │   └── users/
│   ├── router/
│   │   ├── GuestRoute.jsx
│   │   ├── PrivateRoute.jsx
│   │   └── index.jsx
│   ├── services/
│   │   ├── api.js
│   │   └── auth.service.js
│   ├── App.jsx
│   └── main.jsx
├── index.html
├── package.json
├── tailwind.config.js
├── postcss.config.js
└── vite.config.js
```

### Servicios

#### api.js
**Ubicación:** `frontend/src/services/api.js`

**Función:** Configuración base de Axios para peticiones HTTP.

**Configuración:**
- Base URL desde variables de entorno
- Interceptor para incluir token JWT en headers
- Manejo de errores

**Uso:**
```javascript
import api from './services/api';

api.get('/endpoint');
api.post('/endpoint', data);
api.put('/endpoint', data);
```

#### securityAPI.js
**Ubicación:** `frontend/src/modules/security/services/securityAPI.js`

**Función:** Servicio API específico para módulo de seguridad.

**Métodos:**

```javascript
// Aplicaciones
getApplications()
getApplicationById(id)
createApplication(data)
updateApplication(id, data)
changeApplicationStatus(id, status)
changeApplicationVisibility(id, visible)

// Permisos
getPermissions()
getPermissionsByApplication(applicationId)
createPermission(data)
updatePermission(id, data)
changePermissionStatus(id, status)

// Roles
getRoles()
getRoleById(roleId)
createRole(data)
updateRole(id, data)
changeRoleStatus(id, status)
syncRolePermissions(roleId, permissionIds)

// Usuarios
getUsers()
getUserById(id)
getUserPermissions(id)
createUser(data)
updateUser(id, data)
changeUserStatus(id, status)
syncUserRoles(userId, roleIds)
```

### Componentes

#### SecurityPage.jsx
**Ubicación:** `frontend/src/modules/security/pages/SecurityPage.jsx`

**Función:** Página principal de administración de seguridad.

**Características:**
- 4 tabs: Aplicaciones, Permisos, Roles, Usuarios
- CRUD completo para cada entidad
- Modales para crear/editar
- Interfaz visual para asignar permisos a roles (estilo Azure)
- Asignación de roles a usuarios con checkboxes
- Activación/desactivación de entidades
- Visualización de permisos efectivos por usuario

**Estado:**
```javascript
{
  activeTab: 'applications' | 'permissions' | 'roles' | 'users',
  applications: [],
  permissions: [],
  roles: [],
  users: [],
  rolePermissionsByRole: {},
  userRoleSelections: {},
  loading: boolean,
  // Modals
  showApplicationModal: boolean,
  showPermissionModal: boolean,
  showRoleModal: boolean,
  showUserModal: boolean,
  editingItem: object | null,
  formData: object
}
```

**Funciones principales:**
- `handleCreateApplication()` - Abre modal para crear aplicación
- `handleEditApplication(app)` - Abre modal para editar aplicación
- `handleSaveApplication()` - Guarda aplicación
- `handleToggleApplicationStatus(app)` - Activa/desactiva aplicación
- `handleToggleApplicationVisibility(app)` - Muestra/oculta en menú
- `handleCreatePermission(applicationId)` - Crea permiso para aplicación
- `handleEditPermission(permission)` - Edita permiso
- `handleSavePermission()` - Guarda permiso
- `handleTogglePermissionStatus(permission)` - Activa/desactiva permiso
- `handleCreateRole()` - Crea nuevo rol
- `handleEditRole(role)` - Edita rol
- `handleSaveRole()` - Guarda rol
- `handleToggleRoleStatus(role)` - Activa/desactiva rol
- `toggleRolePermission(roleId, permissionId)` - Toggle permiso en rol
- `saveRolePermissions(roleId)` - Guarda permisos del rol
- `toggleUserRole(userId, roleId)` - Toggle rol en usuario
- `saveUserRoles(userId)` - Guarda roles del usuario
- `handleToggleUserStatus(user)` - Activa/desactiva usuario
- `handleViewUserPermissions(user)` - Muestra permisos efectivos

**UI:**
- Diseño moderno con Tailwind CSS
- Iconos de Lucide React
- Colores por aplicación
- Badges de estado
- Modales con formularios
- Responsive design

---

## Módulo de Seguridad

### Flujo de Datos

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (React)                          │
│  SecurityPage.jsx → securityAPI.js → api.js                 │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP Request
┌───────────────────────────┴─────────────────────────────────┐
│                    Backend (PHP)                             │
│  Router → Controller → Service → Repository → Database      │
└─────────────────────────────────────────────────────────────┘
```

### Flujo de Autenticación

1. **Login:**
   - Usuario ingresa email
   - Frontend llama a `/api/v1/auth/magic-link`
   - Backend genera token, envía email
   - Usuario accede al link
   - Backend valida token, genera JWT
   - Frontend almacena JWT

2. **Peticiones Protegidas:**
   - Frontend incluye JWT en header `Authorization: Bearer {token}`
   - `JwtMiddleware` valida token
   - Verifica estado del usuario en BD
   - Carga roles y permisos
   - Establece usuario en `Auth`
   - Si usuario inactivo → 403 Forbidden

3. **Validación de Permisos:**
   - `PermissionMiddleware` verifica permiso específico
   - Métodos estáticos: `check()`, `checkAny()`, `checkAll()`
   - Si no tiene permiso → 403 Forbidden

### Gestión de Aplicaciones

**Crear Aplicación:**
1. Usuario abre modal "Nueva aplicación"
2. Completa formulario (Nombre, Código, Ruta, Color, Icono, Orden, Visible, Estado)
3. Frontend llama `securityAPI.createApplication(formData)`
4. Backend: `ApplicationController.store()` → `ApplicationService.create()` → `ApplicationRepository.create()`
5. Inserta en `gcp_aplicaciones`
6. Retorna ID de la aplicación creada
7. Frontend recarga lista de aplicaciones

**Editar Aplicación:**
1. Usuario hace clic en botón Editar de una aplicación
2. Modal se abre con datos existentes
3. Usuario modifica campos
4. Frontend llama `securityAPI.updateApplication(id, formData)`
5. Backend: `ApplicationController.update()` → `ApplicationService.update()` → `ApplicationRepository.update()`
6. Actualiza registro en `gcp_aplicaciones`
7. Frontend recarga lista

**Activar/Desactivar:**
1. Usuario hace clic en botón de estado
2. Frontend llama `securityAPI.changeApplicationStatus(id, !status)`
3. Backend: `ApplicationController.changeStatus()` → `ApplicationService.changeStatus()` → `ApplicationRepository.changeStatus()`
4. Actualiza campo `Estado` en BD
5. Frontend recarga lista

### Gestión de Permisos

**Crear Permiso:**
1. Usuario hace clic en "Nuevo permiso" en una aplicación
2. Modal se abre con `AplicacionId` preseleccionado
3. Usuario completa (Módulo, Acción, Código Único, Descripción, Estado)
4. Frontend llama `securityAPI.createPermission(formData)`
5. Backend: `PermissionController.store()` → `PermissionService.create()` → `PermissionRepository.create()`
6. Inserta en `gcp_permisos` con `AplicacionId`
7. Frontend recarga permisos

**Asignar Permisos a Rol:**
1. Usuario abre un rol
2. Ve permisos organizados por aplicación (estilo Azure)
3. Marca/desmarca checkboxes
4. Hace clic en "Guardar"
5. Frontend llama `securityAPI.syncRolePermissions(roleId, permissionIds)`
6. Backend: `RoleController.syncPermissions()` → `RoleService.syncPermissions()` → `RoleRepository.syncPermissions()`
7. Transacción:
   - DELETE FROM `gcp_rolespermisos` WHERE RolId = ?
   - INSERT INTO `gcp_rolespermisos` (RolId, PermisoId) VALUES (?, ?)
8. Frontend muestra confirmación

### Gestión de Roles

**Crear Rol:**
1. Usuario hace clic en "Nuevo rol"
2. Modal se abre
3. Usuario completa (Nombre, Código, Descripción, NivelAcceso, Estado)
4. Frontend llama `securityAPI.createRole(formData)`
5. Backend: `RoleController.store()` → `RoleService.create()` → `RoleRepository.create()`
6. Inserta en `gcp_roles`
7. Frontend recarga lista

**Asignar Permisos:**
Ver sección "Asignar Permisos a Rol" arriba.

### Gestión de Usuarios

**Asignar Roles:**
1. Usuario abre la sección de usuarios
2. Ve cada usuario con una lista de roles (opciones excluyentes)
3. Selecciona el único rol del usuario
4. Hace clic en "Guardar rol"
5. Frontend llama `securityAPI.setUserRole(userId, roleId)`
6. Backend: `UserController.setRole()` → `UserService.setRole()` → `UserRepository.setRole()`
7. Actualiza `gcp_usuarios.RolId`
8. Frontend muestra confirmación

**Ver Permisos Efectivos:**
1. Usuario hace clic en "Ver permisos" de un usuario
2. Frontend llama `securityAPI.getUserPermissions(userId)`
3. Backend: `UserController.permissions()` → `UserService.getUserPermissions()` → `UserRepository.getUserPermissions()`
4. Query:
   ```sql
   SELECT DISTINCT p.Id, p.CodigoUnico, p.Modulo, p.Accion, p.AplicacionId, a.Nombre AS AplicacionNombre
   FROM gcp_usuarios u
   INNER JOIN gcp_rolespermisos rp ON rp.RolId = u.RolId
   INNER JOIN gcp_permisos p ON p.Id = rp.PermisoId
   LEFT JOIN gcp_aplicaciones a ON a.Id = p.AplicacionId
   WHERE u.Id = :userId AND p.Estado = 1
   ORDER BY a.OrdenMenu ASC, a.Nombre ASC, p.CodigoUnico ASC
   ```
5. Retorna permisos efectivos (heredados de roles)
6. Frontend muestra en alert

**Activar/Desactivar Usuario:**
1. Usuario hace clic en botón de estado
2. Frontend llama `securityAPI.changeUserStatus(id, !status)`
3. Backend: `UserController.changeStatus()` → `UserService.changeStatus()` → `UserRepository.changeStatus()`
4. Actualiza campo `Estado` en `gcp_usuarios`
5. **Importante:** En la próxima petición, `JwtMiddleware` verificará el estado y bloqueará si está inactivo

---

## Autenticación y Autorización

### JWT (JSON Web Tokens)

**Configuración:** `backend/config/jwt.php`

```php
return [
    'secret' => env('JWT_SECRET'),
    'algorithm' => 'HS256',
    'expiration' => (int) env('JWT_EXPIRATION', 43200), // 12 horas
];
```

**Payload del Token:**
```json
{
    "sub": 1,           // User ID
    "email": "user@example.com",
    "iat": 1691234567,  // Issued At
    "exp": 1691277767   // Expiration
}
```

### Middleware Chain

```
Request → CorsMiddleware → JwtMiddleware → PermissionMiddleware → Controller
```

1. **CorsMiddleware:** Maneja CORS
2. **JwtMiddleware:** Valida token y estado de usuario
3. **PermissionMiddleware:** Valida permisos específicos
4. **Controller:** Ejecuta lógica de negocio

### Seguridad Implementada

1. **Validación de estado en cada petición:**
   - Usuario desactivado es bloqueado inmediatamente
   - No necesita cerrar sesión para aplicar cambios

2. **Permisos granulares:**
   - Cada acción tiene un permiso específico
   - Ej: `dashboard.ver`, `inventario.crear`, `usuarios.eliminar`

3. **Jerarquía de roles:**
   - NivelAcceso: 1 (Básico) a 10 (Administrador)
   - Puede usarse para lógica adicional

4. **Herencia de permisos:**
   - Usuario → Roles → Permisos
   - Un usuario puede tener múltiples roles
   - Permisos efectivos = unión de permisos de todos sus roles

5. **Middleware de autenticación y autorización:**
   - **JwtMiddleware**: Valida tokens JWT y verifica estado de usuario en cada petición
   - **PermissionMiddleware**: Verifica permisos específicos por ruta
   - Validación automática de estado de usuario (activos/inactivos)
   - Administradores (roles admin/superadmin) tienen acceso total

6. **Permisos del sistema de seguridad:**
   - `seguridad.ver` - Ver módulo de seguridad
   - `seguridad.administrar_aplicaciones` - Administrar aplicaciones
   - `seguridad.administrar_permisos` - Administrar permisos
   - `seguridad.administrar_roles` - Administrar roles
   - `seguridad.administrar_usuarios` - Administrar usuarios
   - `seguridad.administrar` - Administración completa de seguridad

7. **Permisos del módulo INTRANET:**
   - `intranet.ver` - Ver módulo de intranet
   - `intranet.administrar_noticias` - Administrar noticias y anuncios
   - `intranet.administrar_documentos` - Administrar documentos compartidos
   - `intranet.administrar_eventos` - Administrar calendario de eventos
   - `intranet.administrar_foro` - Administrar foro de discusión

8. **Permisos del módulo SOLICITUDES:**
   - `solicitudes.ver` - Ver módulo de solicitudes
   - `solicitudes.crear` - Crear nuevas solicitudes
   - `solicitudes.administrar` - Administrar solicitudes completas
   - `solicitudes.administrar_categorias` - Administrar categorías de solicitudes

9. **Permisos del módulo GESTIÓN TECNOLÓGICA:**
   - `gestion.ver` - Ver módulo de gestión tecnológica
   - `gestion.incidencias.ver` - Consultar y crear incidencias propias
   - `gestion.administrar_activos` - Administrar activos tecnológicos
   - `gestion.administrar_mantenimiento` - Administrar mantenimientos
   - `gestion.administrar_incidencias` - Administrar incidencias tecnológicas
   - `gestion.administrar_licencias` - Administrar licencias de software

---

## Módulo de Intranet

### Descripción
El módulo de Intranet proporciona herramientas de comunicación interna, incluyendo noticias, documentos compartidos, eventos y un foro de discusión.

### Tablas Principales
- `intranet_noticias` - Noticias y anuncios
- `intranet_documentos` - Documentos compartidos
- `intranet_eventos` - Calendario de eventos
- `intranet_foro_categorias` - Categorías del foro
- `intranet_foro_temas` - Temas de discusión
- `intranet_foro_respuestas` - Respuestas en el foro

### Funcionalidades Clave
- Publicación de noticias con categorías y prioridades
- Sistema de documentos compartidos con control de acceso
- Calendario de eventos con recordatorios
- Foro de discusión con votación de respuestas
- Estadísticas de visualización de contenido

---

## Módulo de Tickets

### Descripción
El módulo de Gestión Tecnológica gestiona tickets de soporte, incidencias y su seguimiento.

### Tablas Principales
- `gestion_tickets_categorias` - Categorías de tickets
- `gestion_tickets` - Tickets de soporte e incidencias
- `gestion_tickets_seguimiento` - Historial de seguimiento
- `gestion_tickets_etiquetas` - Etiquetas para clasificación
- `gestion_tickets_plantillas` - Plantillas de respuesta

### Funcionalidades Clave
- Sistema de tickets con códigos automáticos
- Categorización y asignación de tickets
- Seguimiento completo del ciclo de vida
- Sistema de prioridades y SLA
- Plantillas de respuesta y estadísticas

---

## Módulo de Gestión Tecnológica

### Descripción
El módulo de Gestión Tecnológica administra activos tecnológicos, mantenimientos, incidencias y licencias de software.

### Tablas Principales
- `gestion_activos_categorias` - Categorías de activos
- `gestion_activos` - Activos tecnológicos
- `gestion_mantenimiento` - Historial de mantenimiento
- `gestion_incidencias` - Incidencias tecnológicas
- `gestion_licencias` - Licencias de software
- `gestion_licencias_asignaciones` - Asignaciones de licencias
- `gestion_proveedores` - Proveedores tecnológicos

### Funcionalidades Clave
- Inventario de activos tecnológicos
- Gestión de mantenimiento preventivo y correctivo
- Sistema de incidencias tecnológicas
- Control de licencias de software
- Gestión de proveedores y contratos

---

## API Endpoints

### Seguridad

#### Aplicaciones

```
GET    /api/v1/security/applications
GET    /api/v1/security/applications/{id}
POST   /api/v1/security/applications
PUT    /api/v1/security/applications/{id}
PATCH  /api/v1/security/applications/{id}/status
PATCH  /api/v1/security/applications/{id}/visibility
```

**Request Body (POST/PUT):**
```json
{
  "Nombre": "Dashboard",
  "Codigo": "dashboard",
  "Ruta": "/dashboard",
  "Color": "#6366f1",
  "Icono": "layout-dashboard",
  "OrdenMenu": 1,
  "Visible": true,
  "Estado": true
}
```

#### Permisos

```
GET    /api/v1/security/permissions
GET    /api/v1/security/permissions/application/{applicationId}
POST   /api/v1/security/permissions
PUT    /api/v1/security/permissions/{id}
PATCH  /api/v1/security/permissions/{id}/status
```

**Request Body (POST/PUT):**
```json
{
  "AplicacionId": 1,
  "Modulo": "dashboard",
  "Accion": "Ver",
  "Descripcion": "Ver dashboard",
  "CodigoUnico": "dashboard.ver",
  "Estado": true
}
```

#### Roles

```
GET    /api/v1/security/roles
GET    /api/v1/security/roles/{id}
POST   /api/v1/security/roles
PUT    /api/v1/security/roles/{id}
PATCH  /api/v1/security/roles/{id}/status
POST   /api/v1/security/roles/{id}/permissions
```

**Request Body (POST/PUT Rol):**
```json
{
  "Nombre": "Administrador",
  "Codigo": "admin",
  "Descripcion": "Acceso total al sistema",
  "NivelAcceso": 10,
  "Estado": true
}
```

**Request Body (POST Permisos):**
```json
{
  "Permisos": [1, 2, 3, 4]
}
```

#### Usuarios

```
GET    /api/v1/security/users
GET    /api/v1/security/users/{id}
POST   /api/v1/security/users
PUT    /api/v1/security/users/{id}
PATCH  /api/v1/security/users/{id}/status
POST   /api/v1/security/users/{id}/roles
GET    /api/v1/security/users/{id}/permissions
```

---

### Intranet

#### Noticias

```
GET    /api/v1/intranet/noticias
GET    /api/v1/intranet/noticias/published
GET    /api/v1/intranet/noticias/category/{categoria}
GET    /api/v1/intranet/noticias/{id}
POST   /api/v1/intranet/noticias/{id}/view
POST   /api/v1/intranet/noticias
PUT    /api/v1/intranet/noticias/{id}
PATCH  /api/v1/intranet/noticias/{id}/status
```

#### Documentos

```
GET    /api/v1/intranet/documentos
GET    /api/v1/intranet/documentos/category/{categoria}
GET    /api/v1/intranet/documentos/{id}
POST   /api/v1/intranet/documentos/{id}/download
POST   /api/v1/intranet/documentos
PUT    /api/v1/intranet/documentos/{id}
PATCH  /api/v1/intranet/documentos/{id}/status
```

#### Eventos

```
GET    /api/v1/intranet/eventos
POST   /api/v1/intranet/eventos/daterange
POST   /api/v1/intranet/eventos/upcoming
GET    /api/v1/intranet/eventos/{id}
POST   /api/v1/intranet/eventos
PUT    /api/v1/intranet/eventos/{id}
PATCH  /api/v1/intranet/eventos/{id}/status
```

---

### Solicitudes

#### Categorías

```
GET    /api/v1/solicitudes/categorias
GET    /api/v1/solicitudes/categorias/active
GET    /api/v1/solicitudes/categorias/{id}
POST   /api/v1/solicitudes/categorias
PUT    /api/v1/solicitudes/categorias/{id}
PATCH  /api/v1/solicitudes/categorias/{id}/status
```

#### Solicitudes

```
GET    /api/v1/solicitudes
GET    /api/v1/solicitudes/status/{estado}
GET    /api/v1/solicitudes/assignee/{usuarioId}
GET    /api/v1/solicitudes/requester/{usuarioId}
GET    /api/v1/solicitudes/{id}
POST   /api/v1/solicitudes
PUT    /api/v1/solicitudes/{id}
PATCH  /api/v1/solicitudes/{id}/status
POST   /api/v1/solicitudes/{id}/assign
```

---

### Gestión Tecnológica

#### Activos

```
GET    /api/v1/gestion/activos
GET    /api/v1/gestion/activos/category/{categoriaId}
GET    /api/v1/gestion/activos/assignee/{usuarioId}
GET    /api/v1/gestion/activos/{id}
POST   /api/v1/gestion/activos
PUT    /api/v1/gestion/activos/{id}
PATCH  /api/v1/gestion/activos/{id}/status
PATCH  /api/v1/gestion/activos/{id}/asset-status
POST   /api/v1/gestion/activos/{id}/assign
```
```json
{
  "Permisos": [1, 2, 3, 4, 5]
}
```

#### Usuarios

```
GET    /api/v1/security/users
GET    /api/v1/security/users/{id}
POST   /api/v1/security/users
PUT    /api/v1/security/users/{id}
PATCH  /api/v1/security/users/{id}/status
POST   /api/v1/security/users/{id}/roles
GET    /api/v1/security/users/{id}/permissions
```

**Request Body (POST/PUT Usuario):**
```json
{
  "GrupoId": 1,
  "EmpresaId": 1,
  "CiudadId": 1,
  "AgenciaId": 1,
  "AreaId": 1,
  "CargoId": 1,
  "Nombres": "Juan",
  "Apellidos": "Pérez",
  "EmailCorporativo": "juan.perez@example.com",
  "Telefono": "1234567890",
  "Estado": true
}
```

**Request Body (POST Roles):**
```json
{
  "Roles": [1, 2, 3]
}
```

---

## Scripts de Base de Datos

### migrate.php
**Ubicación:** `backend/scripts/migrate.php`

**Función:** Ejecuta todas las migraciones pendientes.

**Uso:**
```bash
php backend/scripts/migrate.php
```

### seed.php
**Ubicación:** `backend/scripts/seed.php`

**Función:** Ejecuta todos los seeds (datos iniciales).

**Uso:**
```bash
php backend/scripts/seed.php
```

### manual_seed.php
**Ubicación:** `backend/scripts/manual_seed.php`

**Función:** Seed manual con datos básicos del sistema.

**Datos que inserta:**
- 8 aplicaciones (Dashboard, Intranet, Incidencias, Solicitudes, Inventario, Fondo de Reposición, Seguridad, Configuración)
- 8 roles (Administrador, Analista TI, Supervisor TI, Gerencia, RRHH, Operaciones, Tesorería, Usuario Estándar)
- 4 permisos de ejemplo
- Asignación de todos los permisos al rol Administrador
- Asignación del rol Administrador a los usuarios ID 1 y 2

**Uso:**
```bash
php backend/scripts/manual_seed.php
```

---

## Estado Actual del Sistema

### Completado (FASE 1)

✅ **Base de Datos:**
- Tablas RBAC (gcp_aplicaciones, gcp_permisos, gcp_roles, gcp_rolespermisos; rol único en gcp_usuarios)
- Migraciones ejecutadas
- Datos iniciales cargados
- Usuarios 1 y 2 con rol Administrador

✅ **Backend:**
- Repositorios completos para seguridad
- Services con lógica de negocio
- Controllers con todos los endpoints
- Middleware JWT con validación de estado de usuario
- Middleware de permisos
- Rutas configuradas

✅ **Frontend:**
- Página de Seguridad completa
- CRUD de Aplicaciones (crear, editar, activar/desactivar, mostrar/ocultar)
- CRUD de Permisos (crear, editar, activar/desactivar por aplicación)
- CRUD de Roles (crear, editar, activar/desactivar, asignar permisos estilo Azure)
- Gestión de Usuarios (asignar roles, activar/desactivar, ver permisos efectivos)
- API service completo
- Modales para creación/edición
- UI moderna y responsive

✅ **Seguridad:**
- Autenticación JWT
- Validación de estado de usuario en cada petición
- Sistema de permisos granular
- Herencia de permisos a través de roles
- Middleware de autorización

### Pendiente (FASE 2)

🔄 **INTRANET:**
- Calidad (documentos por empresa/área/categoría)
- Documentación (repositorio documental)
- Directorio → Organigrama Corporativo
- Manuales técnicos y administrativos
- Píldoras de capacitación
- Links Aplicativos
- Administración Intranet
- Varios

🔄 **SOLICITUDES:**
- Creación de solicitudes (vacaciones, licencias, permisos, certificaciones)
- Adjuntar documentos
- Flujo de aprobación (jefe → RH → gerencia)
- Trazabilidad completa
- Aprobadores únicamente ven solicitudes pendientes
- Rechazo con motivo

🔄 **GESTIÓN TECNOLÓGICA (Inventario + Incidencias):**
- Mesa de ayuda (incidencias con archivos y activos)
- Inventario por empresa/agencia/usuario
- Aceptación digital de activos
- Relación Activo-Incidencia automática
- Mantenimientos preventivos y correctivos
- Calificación de servicio al cerrar casos
- Estados de activos

---

## Configuración

### Backend (.env)

```env
APP_NAME="Gacela Conecta"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://192.168.10.156:5176

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=gacela_conecta_beta
DB_USERNAME=root
DB_PASSWORD=Gacelator*
DB_CHARSET=utf8mb4

JWT_SECRET=67834e8e75b61fdb75d2eb2637584cf971faada269be36e9178ed2194cce0d57
JWT_EXPIRATION=43200

MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=coordinadorsistemasfm@gmail.com
MAIL_PASSWORD=wddezjaoqgnajurw
MAIL_FROM_ADDRESS=coordinadorsistemasfm@gmail.com
MAIL_FROM_NAME="Gacela Conecta"
```

### Frontend (package.json)

```json
{
  "name": "gacela-conecta-frontend",
  "dependencies": {
    "react": "^18.3.1",
    "react-dom": "^18.3.1",
    "react-router-dom": "^6.26.1",
    "axios": "^1.7.7",
    "lucide-react": "^0.454.0"
  },
  "devDependencies": {
    "@vitejs/plugin-react": "^4.3.1",
    "vite": "^5.4.2",
    "tailwindcss": "^3.4.1"
  }
}
```

---

## Notas Importantes

1. **Validación de Estado:** El middleware JWT verifica el estado del usuario en CADA petición, lo que permite desactivar usuarios inmediatamente sin necesidad de que cierren sesión.

2. **Permisos Efectivos:** Los permisos de un usuario se calculan dinámicamente como la unión de todos los permisos de sus roles asignados.

3. **Cascada de Eliminación:** Las relaciones FK están configuradas con ON DELETE CASCADE, lo que significa que al eliminar un rol o permiso, se eliminan automáticamente las asignaciones relacionadas.

4. **Códigos Únicos:** Los códigos de permisos (ej: `dashboard.ver`) deben ser únicos en todo el sistema y se usan para validar permisos en el middleware.

5. **Organización por Aplicación:** La UI de permisos está organizada por aplicación para facilitar la gestión, similar a la consola de Azure.

6. **Roles de Sistema:** Los usuarios ID 1 y 2 tienen asignado el rol Administrador con todos los permisos para facilitar la administración inicial.

---

## Próximos Pasos

1. Completar módulo INTRANET con todas las subsecciones
2. Desarrollar módulo SOLICITUDES con flujo de aprobación
3. Unificar Inventario e Incidencias en Gestión Tecnológica
4. Implementar Organigrama Corporativo interactivo
5. Desarrollar sistema de notificaciones
6. Implementar auditoría completa de acciones
7. Crear sistema de reportes
8. Optimizar rendimiento y caché

---

*Documentación generada el 2026-08-05*
*Versión del sistema: 2.0 Beta*
