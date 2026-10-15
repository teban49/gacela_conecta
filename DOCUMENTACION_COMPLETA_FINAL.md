# DOCUMENTACIÓN COMPLETA FINAL - GACELA CONECTA BETA V2

## ÍNDICE
1. [Descripción General del Proyecto](#descripción-general-del-proyecto)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura de Directorios](#estructura-de-directorios)
4. [Base de Datos](#base-de-datos)
5. [Backend - PHP](#backend---php)
6. [Frontend - React](#frontend---react)
7. [Flujo de Datos](#flujo-de-datos)
8. [Configuración y Setup](#configuración-y-setup)
9. [API Endpoints](#api-endpoints)
10. [Módulos del Sistema](#módulos-del-sistema)

---

## DESCRIPCIÓN GENERAL DEL PROYECTO

**Nombre**: Gacela Conecta Beta V2  
**Tipo**: Sistema ERP Corporativo  
**Tecnologías**: PHP 8.x (Backend), React 18 (Frontend), MySQL (Base de Datos)  
**Arquitectura**: REST API + SPA (Single Page Application)  
**Autenticación**: JWT + Magic Link  
**Autorización**: RBAC (Role-Based Access Control)

### Propósito
Sistema de gestión empresarial para administración de usuarios, roles, permisos, aplicaciones, intranet, solicitudes y gestión tecnológica.

### Características Principales
- Autenticación segura con Magic Link y JWT
- Sistema de permisos granular RBAC
- Sidebar dinámico desde base de datos
- Invalidación inmediata de sesiones
- Protección de rutas en frontend y backend
- CRUD completo para todas las entidades
- Validación de estado de usuario en cada petición

---

## ARQUITECTURA DEL SISTEMA

### Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (React)                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Components  │  │   Services   │  │    Contexts  │      │
│  │  - Páginas   │  │  - API calls │  │  - AuthContext│      │
│  │  - Layouts   │  │  - Axios     │  │  - State Mgmt│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│           ↓                 ↓                 ↓               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Router + Protección de Rutas            │  │
│  │  - PermissionRoute - PermissionRoute - PrivateRoute │  │
│  └──────────────────────────────────────────────────────┘  │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP/REST API
                            │ JWT Bearer Token
┌───────────────────────────┴─────────────────────────────────┐
│                    BACKEND (PHP)                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │→│   Services   │→│  Repositories│      │
│  │  - Request   │  │  - Business  │  │  - Data Access│      │
│  │  - Response  │  │    Logic     │  │  - SQL Queries│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│           ↓              ↓                  ↓               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Middleware Layer                          │  │
│  │  - JwtMiddleware - PermissionMiddleware              │  │
│  └──────────────────────────────────────────────────────┘  │
│           ↓                                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Core Services                             │  │
│  │  - Router - Database - JwtService - Response         │  │
│  └──────────────────────────────────────────────────────┘  │
└───────────────────────────┬─────────────────────────────────┘
                            │ PDO
┌───────────────────────────┴─────────────────────────────────┐
│              BASE DE DATOS (MySQL)                         │
│  - gcp_usuarios - gcp_roles - gcp_permisos                 │
│  - gcp_aplicaciones - gcp_rolespermisos - gcp_usuarios.RolId │
│  - Tablas de módulos (intranet, solicitudes, gestión)      │
└─────────────────────────────────────────────────────────────┘
```

### Patrones de Diseño Implementados

1. **Repository-Service-Controller**: Separación de responsabilidades
2. **BaseService**: Herencia para evitar duplicación en CRUDs
3. **Middleware Pipeline**: Protección de rutas en capas
4. **Context API**: Gestión de estado global en frontend
5. **Dependency Injection**: Inyección de dependencias manual
6. **Singleton Pattern**: Database connection, Auth user

---

## ESTRUCTURA DE DIRECTORIOS

### Árbol Completo de Directorios

```
Gacela Conecta Beta_V2/
├── backend/                          # Backend PHP
│   ├── app/
│   │   ├── Controllers/              # Controladores HTTP
│   │   │   ├── Administration/      # Módulo de administración
│   │   │   │   ├── CompanyController.php
│   │   │   │   ├── DepartmentController.php
│   │   │   │   ├── MenuController.php
│   │   │   │   ├── PositionController.php
│   │   │   │   ├── RoleController.php
│   │   │   │   └── UserController.php
│   │   │   ├── Auth/               # Autenticación
│   │   │   │   └── AuthController.php
│   │   │   ├── Gestion/            # Gestión Tecnológica
│   │   │   │   └── ActivoController.php
│   │   │   ├── Intranet/           # Módulo Intranet
│   │   │   │   ├── DocumentoController.php
│   │   │   │   ├── EventoController.php
│   │   │   │   ├── IntranetController.php
│   │   │   │   └── NoticiaController.php
│   │   │   ├── Security/           # Módulo de Seguridad
│   │   │   │   ├── ApplicationController.php
│   │   │   │   ├── PermissionController.php
│   │   │   │   ├── RoleController.php
│   │   │   │   └── UserController.php
│   │   │   └── Solicitudes/        # Módulo de Solicitudes
│   │   │       ├── CategoriaController.php
│   │   │       └── SolicitudController.php
│   │   ├── Core/                   # Servicios centrales
│   │   │   ├── Application.php     # Bootstrap de la aplicación
│   │   │   ├── Auth.php            # Usuario autenticado global
│   │   │   ├── Config.php          # Gestión de configuración
│   │   │   ├── Database.php        # Conexión a base de datos
│   │   │   ├── JwtService.php      # Generación/validación JWT
│   │   │   ├── Kernel.php          # Inicialización del sistema
│   │   │   ├── Logger.php          # Sistema de logging
│   │   │   ├── Mail.php            # Sistema de correos
│   │   │   ├── Request.php         # Manejo de peticiones HTTP
│   │   │   ├── Response.php        # Respuestas HTTP
│   │   │   ├── Router.php          # Enrutador de rutas
│   │   │   └── Validator.php       # Validación de datos
│   │   ├── Console/                # Comandos de consola
│   │   │   ├── Kernel.php
│   │   │   └── Scheduler.php
│   │   ├── Enums/                  # Enumeraciones
│   │   │   ├── PermissionType.php
│   │   │   ├── RequestStatus.php
│   │   │   ├── RoleType.php
│   │   │   └── UserStatus.php
│   │   ├── Exceptions/             # Excepciones personalizadas
│   │   │   ├── ApiException.php
│   │   │   ├── UnauthorizedException.php
│   │   │   └── ValidationException.php
│   │   ├── Helpers/                # Funciones auxiliares
│   │   │   ├── DateHelper.php
│   │   │   ├── FileHelper.php
│   │   │   ├── ResponseHelper.php
│   │   │   └── StringHelper.php
│   │   ├── Mail/                   # Plantillas de correo
│   │   │   ├── MagicLinkMail.php
│   │   │   └── MailService.php
│   │   ├── Middleware/             # Middleware de rutas
│   │   │   ├── AuditMiddleware.php
│   │   │   ├── AuthMiddleware.php
│   │   │   ├── CorsMiddleware.php
│   │   │   ├── JwtMiddleware.php
│   │   │   └── PermissionMiddleware.php
│   │   ├── Models/                 # Modelos de datos
│   │   │   ├── AuthToken.php
│   │   │   ├── Company.php
│   │   │   ├── Department.php
│   │   │   ├── Menu.php
│   │   │   ├── Position.php
│   │   │   ├── Role.php
│   │   │   └── User.php
│   │   ├── Notifications/          # Sistema de notificaciones
│   │   │   ├── Channels/
│   │   │   │   ├── EmailNotification.php
│   │   │   │   ├── PushNotification.php
│   │   │   │   └── WhatsAppNotification.php
│   │   │   └── NotificationService.php
│   │   ├── Policies/               # Políticas de autorización
│   │   │   ├── MenuPolicy.php
│   │   │   ├── RolePolicy.php
│   │   │   └── UserPolicy.php
│   │   ├── Repositories/           # Repositorios de datos
│   │   │   ├── BaseRepository.php
│   │   │   ├── Gestion/
│   │   │   │   └── ActivoRepository.php
│   │   │   ├── Intranet/
│   │   │   │   ├── DocumentRepository.php
│   │   │   │   ├── DocumentoRepository.php
│   │   │   │   ├── EventoRepository.php
│   │   │   │   ├── IntranetRepository.php
│   │   │   │   ├── NoticiaRepository.php
│   │   │   │   └── OrganigramaRepository.php
│   │   │   ├── MenuRepository.php
│   │   │   ├── PositionRepository.php
│   │   │   ├── RoleRepository.php
│   │   │   ├── Security/
│   │   │   │   ├── ApplicationRepository.php
│   │   │   │   ├── PermissionRepository.php
│   │   │   │   ├── RoleRepository.php
│   │   │   │   └── UserRepository.php
│   │   │   ├── Solicitudes/
│   │   │   │   ├── CategoriaRepository.php
│   │   │   │   └── SolicitudRepository.php
│   │   │   └── UserRepository.php
│   │   └── Services/               # Servicios de negocio
│   │       ├── Administration/
│   │       │   ├── CompanyService.php
│   │       │   ├── DepartmentService.php
│   │       │   ├── MenuService.php
│   │       │   ├── PositionService.php
│   │       │   ├── RoleService.php
│   │       │   └── UserService.php
│   │       ├── Auth/
│   │       │   └── AuthService.php
│   │       ├── BaseService.php     # Clase base para servicios
│   │       ├── Gestion/
│   │       │   └── ActivoService.php
│   │       ├── Intranet/
│   │       │   ├── DocumentoService.php
│   │       │   ├── EventoService.php
│   │       │   └── IntranetService.php
│   │       ├── Security/
│   │       │   ├── ApplicationService.php
│   │       │   ├── PermissionService.php
│   │       │   ├── RoleService.php
│   │       │   └── UserService.php
│   │       └── Solicitudes/
│   │           ├── CategoriaService.php
│   │           └── SolicitudService.php
│   ├── config/                      # Archivos de configuración
│   │   ├── constants.php
│   │   ├── database.php
│   │   ├── jwt.php
│   │   └── mail.php
│   ├── public/                      # Directorio público
│   │   └── index.php              # Entry point de la aplicación
│   ├── routes/                      # Definición de rutas
│   │   ├── api.php
│   │   ├── auth.php
│   │   ├── companies.php
│   │   ├── departments.php
│   │   ├── gestion.php
│   │   ├── intranet.php
│   │   ├── menus.php
│   │   ├── positions.php
│   │   ├── roles.php
│   │   ├── security.php
│   │   ├── solicitudes.php
│   │   └── users.php
│   ├── scripts/                     # Scripts de utilidad
│   │   ├── add_gestion_permissions.php
│   │   ├── add_intranet_permissions.php
│   │   ├── add_solicitudes_permissions.php
│   │   ├── manual_seed.php
│   │   ├── run_migrations.php
│   │   └── setup_core_permissions.php
│   ├── vendor/                      # Dependencias Composer
│   ├── .env                        # Variables de entorno
│   ├── .env.example                # Ejemplo de .env
│   ├── .gitignore
│   ├── .htaccess                   # Configuración Apache
│   ├── composer.json               # Dependencias PHP
│   └── composer.lock
├── database/                        # Base de datos
│   └── migrations/                 # Migraciones SQL
│       ├── 001_create_gcp_tables.sql
│       ├── 002_create_administration_tables.sql
│       ├── 003_create_auth_tables.sql
│       ├── 004_create_notifications_tables.sql
│       ├── 005_create_manual_seed.sql
│       ├── 006_create_gcp_aplicaciones_table.sql
│       ├── 007_alter_gcp_permisos_add_aplicacionid.sql
│       ├── 008_create_security_tables.sql
│       ├── 009_create_intranet_tables.sql
│       ├── 010_create_solicitudes_tables.sql
│       └── 011_create_gestion_tecnologica_tables.sql
├── docs/                            # Documentación
│   ├── CORE_DEL_SISTEMA.md         # Documentación del Core
│   ├── DOCUMENTACION_SISTEMA.md    # Documentación general
│   └── GUÍA_INSTALACIÓN.md         # Guía de instalación
├── frontend/                        # Frontend React
│   ├── public/                     # Archivos estáticos
│   │   └── index.html
│   ├── src/
│   │   ├── components/            # Componentes React
│   │   │   ├── MainLayout.jsx     # Layout principal
│   │   │   └── Sidebar.jsx       # Menú lateral dinámico
│   │   ├── contexts/              # Contextos React
│   │   │   └── AuthContext.jsx   # Contexto de autenticación
│   │   ├── modules/               # Módulos de la aplicación
│   │   │   ├── auth/             # Módulo de autenticación
│   │   │   │   ├── pages/
│   │   │   │   │   ├── LoginPage.jsx
│   │   │   │   │   └── VerifyPage.jsx
│   │   │   │   └── services/
│   │   │   │       └── authAPI.js
│   │   │   ├── dashboard/        # Módulo Dashboard
│   │   │   │   └── pages/
│   │   │   │       └── DashboardPage.jsx
│   │   │   ├── gestion/          # Módulo Gestión Tecnológica
│   │   │   │   ├── pages/
│   │   │   │   │   └── GestionPage.jsx
│   │   │   │   └── services/
│   │   │   │       └── gestionAPI.js
│   │   │   ├── intranet/         # Módulo Intranet
│   │   │   │   ├── pages/
│   │   │   │   │   ├── IntranetHomePage.jsx
│   │   │   │   │   └── IntranetPage.jsx
│   │   │   │   └── services/
│   │   │   │       └── intranetAPI.js
│   │   │   ├── security/         # Módulo de Seguridad
│   │   │   │   ├── pages/
│   │   │   │   │   └── SecurityPage.jsx
│   │   │   │   └── services/
│   │   │   │       └── securityAPI.js
│   │   │   └── solicitudes/      # Módulo de Solicitudes
│   │   │       ├── pages/
│   │   │       │   └── SolicitudesPage.jsx
│   │   │       └── services/
│   │   │           └── solicitudesAPI.js
│   │   ├── router/                # Configuración de rutas
│   │   │   ├── PermissionRoute.jsx
│   │   │   ├── PrivateRoute.jsx
│   │   │   ├── GuestRoute.jsx
│   │   │   └── index.jsx
│   │   ├── services/              # Servicios globales
│   │   │   ├── api.js            # Cliente Axios
│   │   │   └── applicationsAPI.js
│   │   ├── App.jsx                # Componente principal
│   │   └── main.jsx               # Entry point
│   ├── .env                        # Variables de entorno
│   ├── .env.example
│   ├── index.html
│   ├── package.json               # Dependencias Node.js
│   ├── package-lock.json
│   ├── postcss.config.js
│   ├── tailwind.config.js
│   └── vite.config.js
├── GUÍA_INSTALACIÓN.md
├── README.md
└── DOCUMENTACION_COMPLETA_FINAL.md (este archivo)
```

---

## BASE DE DATOS

### Tablas Principales del Sistema

#### Tablas de Seguridad y Autenticación

**gcp_usuarios**
- Propósito: Almacenar usuarios del sistema
- Campos principales: Id, Nombres, Apellidos, EmailCorporativo, Telefono, Estado
- Relaciones: 
  - → gcp_roles (RolId)
  - → gcp_usuariosempresas (UsuarioId)
  - → gcp_agencias (AgenciaId)
  - → gcp_cargos (CargoId)
- Uso: Autenticación, gestión de usuarios, asignación de roles

**gcp_roles**
- Propósito: Definir roles del sistema
- Campos principales: Id, Nombre, Codigo, Descripcion, NivelAcceso, Estado
- Relaciones:
  - ← gcp_usuarios (RolId)
  - → gcp_rolespermisos (RolId)
- Uso: Sistema RBAC, jerarquía de permisos

**gcp_permisos**
- Propósito: Definir permisos granulares
- Campos principales: Id, AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado
- Relaciones:
  - → gcp_aplicaciones (AplicacionId)
  - → gcp_rolespermisos (PermisoId)
- Uso: Control de acceso por acción

**gcp_aplicaciones**
- Propósito: Definir aplicaciones/módulos del sistema
- Campos principales: Id, Nombre, Codigo, Ruta, Color, Icono, OrdenMenu, Visible, Estado
- Relaciones:
  - → gcp_permisos (AplicacionId)
- Uso: Sidebar dinámico, organización de módulos

**gcp_rolespermisos**
- Propósito: Relación muchos a muchos entre roles y permisos
- Campos: RolId, PermisoId
- Uso: Asignación de permisos a roles

**gcp_auth_tokens**
- Propósito: Almacenar tokens de autenticación
- Campos: Id, UsuarioId, TokenHash, Tipo, FechaExpiracion, IpAddress, UserAgent, Usado
- Uso: Magic link authentication, validación de tokens

#### Tablas de Organización

**gcp_agencias**
- Propósito: Ubicaciones geográficas de la empresa
- Campos: Id, Nombre, Ciudad, Codigo
- Relaciones: → gcp_usuarios (AgenciaId)

**gcp_cargos**
- Propósito: Puestos de trabajo
- Campos: Id, Nombre, Codigo, AreaId
- Relaciones: 
  - → gcp_areas (AreaId)
  - → gcp_usuarios (CargoId)

**gcp_areas**
- Propósito: Departamentos funcionales
- Campos: Id, Nombre
- Relaciones: → gcp_cargos (AreaId)

**gcp_empresas**
- Propósito: Empresas del grupo
- Campos: Id, Nombre, Ruc
- Relaciones: → gcp_usuariosempresas (EmpresaId)

#### Tablas del Módulo Intranet

**intranet_noticias**
- Propósito: Noticias y anuncios
- Campos: Id, Titulo, Contenido, Categoria, Prioridad, AutorId, Estado
- Relaciones: → gcp_usuarios (AutorId)

**intranet_documentos**
- Propósito: Documentos compartidos
- Campos: Id, Titulo, Descripcion, Archivo, Categoria, SubidoPorId, Estado
- Relaciones: → gcp_usuarios (SubidoPorId)

**intranet_eventos**
- Propósito: Calendario de eventos
- Campos: Id, Titulo, Descripcion, FechaInicio, FechaFin, Ubicacion, OrganizadorId, Estado
- Relaciones: → gcp_usuarios (OrganizadorId)

**intranet_foro_categorias**
- Propósito: Categorías del foro
- Campos: Id, Nombre, Descripcion, Estado

**intranet_foro_temas**
- Propósito: Temas de discusión
- Campos: Id, CategoriaId, Titulo, Contenido, AutorId, Estado
- Relaciones: 
  - → intranet_foro_categorias (CategoriaId)
  - → gcp_usuarios (AutorId)

**intranet_foro_respuestas**
- Propósito: Respuestas del foro
- Campos: Id, TemaId, Contenido, AutorId, Estado
- Relaciones:
  - → intranet_foro_temas (TemaId)
  - → gcp_usuarios (AutorId)

#### Tablas del Módulo de Tickets

**gestion_tickets_categorias**
- Propósito: Categorías de tickets
- Campos: Id, Nombre, Descripcion, Estado

**gestion_tickets**
- Propósito: Tickets de soporte e incidencias
- Campos: Id, Ticket, CategoriaId, Titulo, Descripcion, Estado, Prioridad, ReportadoPorId, AsignadoAId
- Relaciones:
  - → gestion_tickets_categorias (CategoriaId)
  - → gcp_usuarios (ReportadoPorId, AsignadoAId)

**gestion_tickets_seguimiento**
- Propósito: Historial de seguimiento
- Campos: Id, SolicitudId, Comentario, AutorId, Fecha
- Relaciones:
  - → gestion_tickets (SolicitudId)
  - → gcp_usuarios (AutorId)

#### Tablas del Módulo Gestión Tecnológica

**gestion_activos_categorias**
- Propósito: Categorías de activos
- Campos: Id, Nombre, Descripcion, Codigo, Icono, Color, Estado

**gestion_activos**
- Propósito: Activos tecnológicos
- Campos: Id, CategoriaId, CodigoInventario, Nombre, Descripcion, Marca, Modelo, NumeroSerie, EstadoActivo, AsignadoAId
- Relaciones:
  - → gestion_activos_categorias (CategoriaId)
  - → gcp_usuarios (AsignadoAId)

**gestion_mantenimiento**
- Propósito: Historial de mantenimiento
- Campos: Id, ActivoId, Tipo, Descripcion, TecnicoId, FechaProgramada, FechaRealizacion, Estado
- Relaciones:
  - → gestion_activos (ActivoId)
  - → gcp_usuarios (TecnicoId)

**gestion_incidencias**
- Propósito: Incidencias tecnológicas
- Campos: Id, Ticket, ActivoId, Tipo, Prioridad, Estado, Titulo, Descripcion, ReportadoPorId, AsignadoAId
- Relaciones:
  - → gestion_activos (ActivoId)
  - → gcp_usuarios (ReportadoPorId, AsignadoAId)

**gestion_licencias**
- Propósito: Licencias de software
- Campos: Id, Nombre, Tipo, Proveedor, NumeroLicencia, Cantidad, CantidadUsada, FechaVencimiento, Estado

**gestion_licencias_asignaciones**
- Propósito: Asignaciones de licencias
- Campos: Id, LicenciaId, UsuarioId, ActivoId, FechaAsignacion, Estado
- Relaciones:
  - → gestion_licencias (LicenciaId)
  - → gcp_usuarios (UsuarioId)
  - → gestion_activos (ActivoId)

---

## BACKEND - PHP

### Core del Sistema

#### app/Core/Kernel.php
**Propósito**: Bootstrap de la aplicación  
**Funciones**:
- Cargar constantes globales
- Cargar variables de entorno
- Inicializar servicios (Database, JWT, Mail)
- Cargar rutas
- Despachar petición  
**Dependencias**: Config, Database, JwtService, Mail, Router  
**Tablas usadas**: Ninguna directa (solo inicialización)

#### app/Core/Database.php
**Propósito**: Gestión de conexión a base de datos  
**Funciones**:
- `init()`: Inicializar conexión PDO
- `getConnection()`: Obtener instancia de conexión  
**Configuración**: Desde config/database.php  
**Tablas usadas**: Todas (acceso genérico)

#### app/Core/Router.php
**Propósito**: Enrutador de peticiones HTTP  
**Funciones**:
- `get()`, `post()`, `put()`, `patch()`, `delete()`: Definir rutas
- `dispatch()`: Enrutar petición al handler correspondiente
- `runMiddleware()`: Ejecutar middleware de ruta  
**Dependencias**: JwtMiddleware, PermissionMiddleware  
**Tablas usadas**: Ninguna

#### app/Core/Request.php
**Propósito**: Manejo de peticiones HTTP  
**Funciones**:
- `getMethod()`: Obtener método HTTP
- `getUri()`: Obtener URI
- `get()`: Obtener parámetro
- `json()`: Obtener JSON del body
- `getBearerToken()`: Extraer token Authorization  
**Tablas usadas**: Ninguna

#### app/Core/Response.php
**Propósito**: Respuestas HTTP estandarizadas  
**Funciones**:
- `success()`: 200 OK
- `created()`: 201 Created
- `error()`: 400 Bad Request
- `unauthorized()`: 401 Unauthorized
- `forbidden()`: 403 Forbidden
- `notFound()`: 404 Not Found
- `validationError()`: 422 Unprocessable Entity
- `serverError()`: 500 Internal Server Error  
**Tablas usadas**: Ninguna

#### app/Core/JwtService.php
**Propósito**: Generación y validación de tokens JWT  
**Funciones**:
- `encode()`: Generar token JWT
- `decode()`: Decodificar token
- `validate()`: Validar firma y expiración
- `generateMagicLinkToken()`: Token temporal para magic-link
- `hashToken()`: Hash de token para storage  
**Tablas usadas**: gcp_auth_tokens (magic link)

#### app/Core/Auth.php
**Propósito**: Usuario autenticado global  
**Funciones**:
- `setUser()`: Establecer usuario autenticado
- `user()`: Obtener usuario autenticado
- `check()`: Verificar autenticación  
**Tablas usadas**: Ninguna (usa datos del middleware)

### Middleware

#### app/Middleware/JwtMiddleware.php
**Propósito**: Validar autenticación JWT  
**Funciones**:
- `handle()`: Validar token y cargar usuario
- Verificar estado del usuario en cada petición
- Cargar roles y permisos del usuario  
**Dependencias**: UserRepository, RoleRepository  
**Tablas usadas**: gcp_usuarios, gcp_rolespermisos, gcp_permisos

#### app/Middleware/PermissionMiddleware.php
**Propósito**: Validar permisos específicos  
**Funciones**:
- `handle()`: Verificar permiso requerido
- `isAdmin()`: Verificar si es administrador
- `check()`: Verificación estática de permiso  
**Dependencias**: Auth  
**Tablas usadas**: Ninguna (usa datos del usuario en Auth)

### Repositories

#### app/Repositories/BaseRepository.php
**Propósito**: Clase base para repositorios  
**Funciones**:
- `getAll()`: Obtener todos los registros
- `getById()`: Obtener por ID
- `create()`: Crear registro
- `update()`: Actualizar registro
- `changeStatus()`: Cambiar estado  
**Tablas usadas**: Dinámico (definido en subclases)

#### app/Repositories/Security/UserRepository.php
**Propósito**: Acceso a datos de usuarios  
**Funciones adicionales**:
- `getUserRoles()`: Obtener roles de usuario
- `syncRoles()`: Sincronizar roles de usuario
- `getUserPermissions()`: Obtener permisos efectivos  
**Tablas usadas**: gcp_usuarios, gcp_roles, gcp_rolespermisos, gcp_permisos, gcp_agencias, gcp_cargos, gcp_areas

#### app/Repositories/Security/RoleRepository.php
**Propósito**: Acceso a datos de roles  
**Funciones adicionales**:
- `getRolePermissions()`: Obtener permisos de rol
- `syncPermissions()`: Sincronizar permisos de rol  
**Tablas usadas**: gcp_roles, gcp_rolespermisos, gcp_permisos

#### app/Repositories/Security/ApplicationRepository.php
**Propósito**: Acceso a datos de aplicaciones  
**Funciones adicionales**:
- `changeVisibility()`: Cambiar visibilidad  
**Tablas usadas**: gcp_aplicaciones

#### app/Repositories/Security/PermissionRepository.php
**Propósito**: Acceso a datos de permisos  
**Funciones adicionales**:
- `byApplication()`: Obtener permisos por aplicación  
**Tablas usadas**: gcp_permisos

### Services

#### app/Services/BaseService.php
**Propósito**: Clase base para servicios  
**Funciones**:
- `getAll()`, `getById()`, `create()`, `update()`, `changeStatus()`
- Métodos sobreescribibles para mensajes personalizados  
**Dependencias**: BaseRepository (método abstracto getRepository())

#### app/Services/Auth/AuthService.php
**Propósito**: Lógica de autenticación  
**Funciones**:
- `requestMagicLink()`: Generar y enviar magic link
- `verifyToken()`: Validar token y crear sesión
- `validateSessionToken()`: Validar sesión JWT  
**Dependencias**: User, AuthToken, JwtService, Mail  
**Tablas usadas**: gcp_usuarios, gcp_auth_tokens

#### app/Services/Security/UserService.php
**Propósito**: Lógica de negocio de usuarios  
**Funciones adicionales**:
- `syncRoles()`: Sincronizar roles de usuario  
**Dependencias**: UserRepository  
**Tablas usadas**: gcp_usuarios, gcp_roles

#### app/Services/Security/RoleService.php
**Propósito**: Lógica de negocio de roles  
**Funciones adicionales**:
- `getByIdWithPermissions()`: Obtener rol con permisos
- `syncPermissions()`: Sincronizar permisos de rol  
**Dependencias**: RoleRepository  
**Tablas usadas**: gcp_roles, gcp_rolespermisos, gcp_permisos

#### app/Services/Security/ApplicationService.php
**Propósito**: Lógica de negocio de aplicaciones  
**Funciones adicionales**:
- `changeVisibility()`: Cambiar visibilidad  
**Dependencias**: ApplicationRepository  
**Tablas usadas**: gcp_aplicaciones

### Controllers

#### app/Controllers/Auth/AuthController.php
**Propósito**: Controlador de autenticación  
**Endpoints**:
- `requestMagicLink()`: POST /api/v1/auth/magic-link
- `verifyToken()`: POST /api/v1/auth/verify-token
- `me()`: GET /api/v1/auth/me
- `logout()`: POST /api/v1/auth/logout  
**Dependencias**: AuthService  
**Tablas usadas**: gcp_usuarios, gcp_auth_tokens

#### app/Controllers/Security/UserController.php
**Propósito**: Controlador de usuarios  
**Endpoints**:
- `index()`: GET /api/v1/security/users
- `show()`: GET /api/v1/security/users/{id}
- `store()`: POST /api/v1/security/users
- `update()`: PUT /api/v1/security/users/{id}
- `changeStatus()`: PATCH /api/v1/security/users/{id}/status
- `syncRoles()`: POST /api/v1/security/users/{id}/roles
- `permissions()`: GET /api/v1/security/users/{id}/permissions  
**Dependencias**: UserService  
**Tablas usadas**: gcp_usuarios, gcp_rolespermisos, gcp_permisos

#### app/Controllers/Security/RoleController.php
**Propósito**: Controlador de roles  
**Endpoints**:
- `index()`: GET /api/v1/security/roles
- `show()`: GET /api/v1/security/roles/{id}
- `store()`: POST /api/v1/security/roles
- `update()`: PUT /api/v1/security/roles/{id}
- `changeStatus()`: PATCH /api/v1/security/roles/{id}/status
- `syncPermissions()`: POST /api/v1/security/roles/{id}/permissions  
**Dependencias**: RoleService  
**Tablas usadas**: gcp_roles, gcp_rolespermisos, gcp_permisos

#### app/Controllers/Security/ApplicationController.php
**Propósito**: Controlador de aplicaciones  
**Endpoints**:
- `index()`: GET /api/v1/security/applications
- `show()`: GET /api/v1/security/applications/{id}
- `store()`: POST /api/v1/security/applications
- `update()`: PUT /api/v1/security/applications/{id}
- `changeStatus()`: PATCH /api/v1/security/applications/{id}/status
- `changeVisibility()`: PATCH /api/v1/security/applications/{id}/visibility  
**Dependencias**: ApplicationService  
**Tablas usadas**: gcp_aplicaciones

#### app/Controllers/Security/PermissionController.php
**Propósito**: Controlador de permisos  
**Endpoints**:
- `index()`: GET /api/v1/security/permissions
- `byApplication()`: GET /api/v1/security/permissions/application/{applicationId}
- `store()`: POST /api/v1/security/permissions
- `update()`: PUT /api/v1/security/permissions/{id}
- `changeStatus()`: PATCH /api/v1/security/permissions/{id}/status  
**Dependencias**: PermissionService  
**Tablas usadas**: gcp_permisos

### Rutas

#### routes/auth.php
**Propósito**: Rutas de autenticación  
**Rutas definidas**:
- POST /api/v1/auth/magic-link
- POST /api/v1/auth/verify-token
- GET /api/v1/auth/me (protegido con jwt)
- POST /api/v1/auth/logout

#### routes/security.php
**Propósito**: Rutas del módulo de seguridad  
**Rutas definidas**:
- Aplicaciones: GET, POST, PUT, PATCH (CRUD completo)
- Permisos: GET, POST, PUT, PATCH (CRUD completo)
- Roles: GET, POST, PUT, PATCH + syncPermissions
- Usuarios: GET, POST, PUT, PATCH + syncRoles + permissions

#### routes/intranet.php
**Propósito**: Rutas del módulo intranet  
**Rutas definidas**:
- Noticias: GET, POST, PUT, PATCH
- Documentos: GET, POST, PUT, PATCH + download
- Eventos: GET, POST, PUT, PATCH + daterange + upcoming

#### routes/solicitudes.php
**Propósito**: Rutas del módulo solicitudes  
**Rutas definidas**:
- Categorías: GET, POST, PUT, PATCH
- Solicitudes: GET, POST, PUT, PATCH + assign

#### routes/gestion.php
**Propósito**: Rutas del módulo gestión tecnológica  
**Rutas definidas**:
- Activos: GET, POST, PUT, PATCH + assign + asset-status

---

## FRONTEND - REACT

### Core del Frontend

#### src/main.jsx
**Propósito**: Entry point de la aplicación React  
**Funciones**: 
- Renderizar App
- Inyectar AuthProvider  
**Dependencias**: React, ReactDOM, App, AuthProvider

#### src/App.jsx
**Propósito**: Componente principal  
**Funciones**: Renderizar Router  
**Dependencias**: Router

#### src/contexts/AuthContext.jsx
**Propósito**: Contexto de autenticación global  
**Estado**:
- user: Datos del usuario
- loading: Estado de carga
- isAuthenticated: Estado de autenticación  
**Funciones**:
- `login()`: Iniciar sesión
- `logout()`: Cerrar sesión
- `hasPermission()`: Verificar permiso
- `hasRole()`: Verificar rol
- `refreshUserData()`: Forzar refresh de datos  
**Dependencias**: authAPI  
**API calls**: GET /api/v1/auth/me

#### src/services/api.js
**Propósito**: Cliente Axios configurado  
**Configuración**:
- baseURL: /api/v1
- Headers: Content-Type: application/json  
**Interceptors**:
- Request: Agregar token Authorization
- Response: Manejar 401, 403, 422, 404, 500  
**Dependencias**: axios

#### src/services/applicationsAPI.js
**Propósito**: API de aplicaciones  
**Funciones**:
- `getApplications()`: GET /api/v1/security/applications
- `getApplicationById()`: GET /api/v1/security/applications/{id}  
**Dependencias**: api.js

### Router

#### src/router/index.jsx
**Propósito**: Configuración de rutas React Router  
**Rutas definidas**:
- /login: LoginPage (pública)
- /auth/verify: VerifyPage (pública)
- /: Redirect a /login
- /dashboard: DashboardPage (protegida)
- /intranet: IntranetHomePage (protegida)
- /security: SecurityPage (protegida)
- /solicitudes: SolicitudesPage (protegida)
- /gestion: GestionPage (protegida)  
**Componentes de protección**:
- ProtectedRoute: Verifica autenticación
- PublicRoute: Redirige si está autenticado
- PermissionRoute: Verifica permiso específico

#### src/router/PermissionRoute.jsx
**Propósito**: Componente de protección por permisos  
**Funciones**:
- Verificar requiredPermission
- Mostrar pantalla de acceso denegado si no tiene permiso
- Bypass para administradores  
**Dependencias**: AuthContext

#### src/router/PrivateRoute.jsx
**Propósito**: Componente de rutas privadas  
**Funciones**:
- Verificar autenticación
- Redirigir a login si no está autenticado  
**Dependencias**: AuthContext

#### src/router/GuestRoute.jsx
**Propósito**: Componente de rutas públicas  
**Funciones**:
- Redirigir a dashboard si está autenticado  
**Dependencias**: AuthContext

### Componentes

#### src/components/MainLayout.jsx
**Propósito**: Layout principal con header y sidebar  
**Contenido**: Header + Sidebar + Outlet  
**Dependencias**: Sidebar, Outlet

#### src/components/Sidebar.jsx
**Propósito**: Menú lateral dinámico  
**Funciones**:
- Cargar aplicaciones desde base de datos
- Filtrar por permisos del usuario
- Renderizar items de menú con iconos  
**Dependencias**: applicationsAPI, AuthContext  
**API calls**: GET /api/v1/security/applications

### Módulos

#### src/modules/auth/pages/LoginPage.jsx
**Propósito**: Página de login con magic link  
**Funciones**:
- Solicitar magic link por email  
**Dependencias**: authAPI  
**API calls**: POST /api/v1/auth/magic-link

#### src/modules/auth/pages/VerifyPage.jsx
**Propósito**: Página de verificación de token  
**Funciones**:
- Validar token magic-link
- Redirigir al dashboard  
**Dependencias**: authAPI  
**API calls**: POST /api/v1/auth/verify-token

#### src/modules/auth/services/authAPI.js
**Propósito**: API de autenticación  
**Funciones**:
- `requestMagicLink()`: POST /api/v1/auth/magic-link
- `verifyToken()`: POST /api/v1/auth/verify-token
- `getMe()`: GET /api/v1/auth/me
- `logout()`: POST /api/v1/auth/logout  
**Dependencias**: api.js

#### src/modules/security/pages/SecurityPage.jsx
**Propósito**: Página de administración de seguridad  
**Funciones**:
- CRUD de aplicaciones
- CRUD de permisos
- CRUD de roles
- CRUD de usuarios
- Asignación de roles a usuarios
- Asignación de permisos a roles
- Consulta de permisos efectivos de usuario  
**Dependencias**: securityAPI  
**API calls**: Todas las rutas de /api/v1/security/*

#### src/modules/security/services/securityAPI.js
**Propósito**: API del módulo de seguridad  
**Funciones**:
- Aplicaciones: GET, POST, PUT, PATCH (CRUD completo)
- Permisos: GET, POST, PUT, PATCH (CRUD completo)
- Roles: GET, POST, PUT, PATCH + syncPermissions
- Usuarios: GET, POST, PUT, PATCH + syncRoles + permissions  
**Dependencias**: api.js

#### src/modules/intranet/pages/IntranetPage.jsx
**Propósito**: Página del módulo intranet  
**Funciones**:
- Gestión de noticias
- Gestión de documentos
- Gestión de eventos  
**Dependencias**: intranetAPI

#### src/modules/intranet/services/intranetAPI.js
**Propósito**: API del módulo intranet  
**Funciones**:
- Noticias: GET, POST, PUT, PATCH
- Documentos: GET, POST, PUT, PATCH + download
- Eventos: GET, POST, PUT, PATCH + daterange + upcoming  
**Dependencias**: api.js

#### src/modules/solicitudes/pages/SolicitudesPage.jsx
**Propósito**: Página del módulo solicitudes  
**Funciones**:
- Gestión de solicitudes
- Gestión de categorías  
**Dependencias**: solicitudesAPI

#### src/modules/solicitudes/services/solicitudesAPI.js
**Propósito**: API del módulo solicitudes  
**Funciones**:
- Categorías: GET, POST, PUT, PATCH
- Solicitudes: GET, POST, PUT, PATCH + assign  
**Dependencias**: api.js

#### src/modules/gestion/pages/GestionPage.jsx
**Propósito**: Página del módulo gestión tecnológica  
**Funciones**:
- Gestión de activos
- Gestión de mantenimiento
- Gestión de incidencias
- Gestión de licencias  
**Dependencias**: gestionAPI

#### src/modules/gestion/services/gestionAPI.js
**Propósito**: API del módulo gestión tecnológica  
**Funciones**:
- Activos: GET, POST, PUT, PATCH + assign + asset-status  
**Dependencias**: api.js

---

## FLUJO DE DATOS

### Flujo de Autenticación

```
1. Usuario ingresa email en LoginPage
   ↓
2. Frontend llama a POST /api/v1/auth/magic-link
   ↓
3. Backend (AuthService) genera token temporal
   ↓
4. Token almacenado en gcp_auth_tokens
   ↓
5. Email enviado con magic link
   ↓
6. Usuario ingresa con magic link
   ↓
7. Frontend llama a POST /api/v1/auth/verify-token
   ↓
8. Backend valida token en gcp_auth_tokens
   ↓
9. Usuario autenticado desde gcp_usuarios
   ↓
10. Permisos calculados desde gcp_usuarios.RolId → gcp_rolespermisos → gcp_permisos
   ↓
11. JWT generado con payload de usuario y permisos
   ↓
12. Token JWT retornado al frontend
   ↓
13. Frontend almacena token en localStorage
   ↓
14. Usuario redirigido a /dashboard
```

### Flujo de Autorización

```
1. Usuario navega a /security
   ↓
2. Frontend PermissionRoute verifica 'seguridad.ver'
   ↓
3. AuthContext verifica si usuario tiene permiso
   ↓
4. Si tiene permiso → SecurityPage renderizado
   ↓
5. Si no tiene permiso → Pantalla de acceso denegado
   ↓
6. Sidebar dinámico carga aplicaciones desde /api/v1/security/applications
   ↓
7. Aplicaciones filtradas por permisos del usuario
   ↓
8. Menú renderizado solo con aplicaciones accesibles
```

### Flujo de CRUD de Usuarios

```
1. Usuario hace clic en "Nuevo Usuario"
   ↓
2. Modal de usuario se abre
   ↓
3. Usuario completa formulario
   ↓
4. Frontend llama a POST /api/v1/security/users
   ↓
5. Router aplica middleware: jwt + seguridad.administrar_usuarios
   ↓
6. JwtMiddleware valida token y carga usuario
   ↓
7. PermissionMiddleware verifica permiso
   ↓
8. UserController.store() procesa petición
   ↓
9. UserService.create() valida y crea usuario
   ↓
10. UserRepository.create() inserta en gcp_usuarios
   ↓
11. Response.success() retorna 201 Created
   ↓
12. Frontend recibe respuesta y actualiza lista
```

### Flujo de Invalidación Inmediata de Sesión

```
1. Administrador cambia Estado de usuario a 0
   ↓
2. UPDATE gcp_usuarios SET Estado = 0 WHERE Id = X
   ↓
3. Usuario hace próxima petición (max 5 min después)
   ↓
4. JwtMiddleware valida token JWT
   ↓
5. UserService.getById() obtiene usuario de BD
   ↓
6. Estado del usuario es 0 (inactivo)
   ↓
7. Response.unauthorized() retorna 401
   ↓
8. Frontend API interceptor detecta 401
   ↓
9. localStorage limpiado (auth_token, user_data)
   ↓
10. Usuario redirigado a /login
```

---

## CONFIGURACIÓN Y SETUP

### Variables de Entorno (Backend)

**Archivo**: backend/.env

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=gacela_conecta_beta
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://192.168.10.156:5176

JWT_SECRET=tu_clave_secreta_aqui
JWT_EXPIRATION=3600

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@gacela.com
MAIL_FROM_NAME=Gacela Conecta
```

### Variables de Entorno (Frontend)

**Archivo**: frontend/.env

```env
VITE_API_URL=http://localhost:8001
```

### Dependencias Backend

**Archivo**: backend/composer.json

```json
{
  "require": {
    "php": "^8.0",
    "vlucas/phpdotenv": "^5.5",
    "firebase/php-jwt": "^6.0"
  }
}
```

### Dependencias Frontend

**Archivo**: frontend/package.json

```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.0",
    "axios": "^1.0",
    "lucide-react": "^0.294.0"
  },
  "devDependencies": {
    "vite": "^5.0",
    "tailwindcss": "^3.0",
    "autoprefixer": "^10.0"
  }
}
```

---

## API ENDPOINTS

### Autenticación

```
POST   /api/v1/auth/magic-link        Solicitar magic link
POST   /api/v1/auth/verify-token       Validar token magic-link
GET    /api/v1/auth/me                 Obtener usuario actual (jwt)
POST   /api/v1/auth/logout              Cerrar sesión
```

### Seguridad - Aplicaciones

```
GET    /api/v1/security/applications                    Listar aplicaciones
GET    /api/v1/security/applications/{id}               Obtener aplicación
POST   /api/v1/security/applications                    Crear aplicación
PUT    /api/v1/security/applications/{id}               Actualizar aplicación
PATCH  /api/v1/security/applications/{id}/status        Cambiar estado
PATCH  /api/v1/security/applications/{id}/visibility    Cambiar visibilidad
```

### Seguridad - Permisos

```
GET    /api/v1/security/permissions                          Listar permisos
GET    /api/v1/security/permissions/application/{appId}     Permisos por aplicación
POST   /api/v1/security/permissions                          Crear permiso
PUT    /api/v1/security/permissions/{id}                     Actualizar permiso
PATCH  /api/v1/security/permissions/{id}/status              Cambiar estado
```

### Seguridad - Roles

```
GET    /api/v1/security/roles                Listar roles
GET    /api/v1/security/roles/{id}           Obtener rol
POST   /api/v1/security/roles                Crear rol
PUT    /api/v1/security/roles/{id}           Actualizar rol
PATCH  /api/v1/security/roles/{id}/status    Cambiar estado
POST   /api/v1/security/roles/{id}/permissions  Sincronizar permisos
```

### Seguridad - Usuarios

```
GET    /api/v1/security/users                  Listar usuarios
GET    /api/v1/security/users/{id}             Obtener usuario
POST   /api/v1/security/users                  Crear usuario
PUT    /api/v1/security/users/{id}             Actualizar usuario
PATCH  /api/v1/security/users/{id}/status      Cambiar estado
POST   /api/v1/security/users/{id}/roles       Sincronizar roles
GET    /api/v1/security/users/{id}/permissions  Permisos efectivos
```

### Intranet - Noticias

```
GET    /api/v1/intranet/noticias                 Listar noticias
GET    /api/v1/intranet/noticias/published       Noticias publicadas
GET    /api/v1/intranet/noticias/category/{cat}  Por categoría
GET    /api/v1/intranet/noticias/{id}            Obtener noticia
POST   /api/v1/intranet/noticias/{id}/view       Marcar como vista
POST   /api/v1/intranet/noticias                 Crear noticia
PUT    /api/v1/intranet/noticias/{id}            Actualizar noticia
PATCH  /api/v1/intranet/noticias/{id}/status     Cambiar estado
```

### Intranet - Documentos

```
GET    /api/v1/intranet/documentos                 Listar documentos
GET    /api/v1/intranet/documentos/category/{cat}  Por categoría
GET    /api/v1/intranet/documentos/{id}            Obtener documento
POST   /api/v1/intranet/documentos/{id}/download   Descargar documento
POST   /api/v1/intranet/documentos                 Crear documento
PUT    /api/v1/intranet/documentos/{id}            Actualizar documento
PATCH  /api/v1/intranet/documentos/{id}/status     Cambiar estado
```

### Intranet - Eventos

```
GET    /api/v1/intranet/eventos                    Listar eventos
POST   /api/v1/intranet/eventos/daterange          Por rango de fechas
POST   /api/v1/intranet/eventos/upcoming           Próximos eventos
GET    /api/v1/intranet/eventos/{id}               Obtener evento
POST   /api/v1/intranet/eventos                    Crear evento
PUT    /api/v1/intranet/eventos/{id}               Actualizar evento
PATCH  /api/v1/intranet/eventos/{id}/status        Cambiar estado
```

### Solicitudes - Categorías

```
GET    /api/v1/solicitudes/categorias              Listar categorías
GET    /api/v1/solicitudes/categorias/active       Categorías activas
GET    /api/v1/solicitudes/categorias/{id}         Obtener categoría
POST   /api/v1/solicitudes/categorias              Crear categoría
PUT    /api/v1/solicitudes/categorias/{id}         Actualizar categoría
PATCH  /api/v1/solicitudes/categorias/{id}/status  Cambiar estado
```

### Solicitudes - Solicitudes

```
GET    /api/v1/solicitudes                         Listar solicitudes
GET    /api/v1/solicitudes/status/{estado}          Por estado
GET    /api/v1/solicitudes/assignee/{userId}       Por asignado
GET    /api/v1/solicitudes/requester/{userId}      Por solicitante
GET    /api/v1/solicitudes/{id}                    Obtener solicitud
POST   /api/v1/solicitudes                         Crear solicitud
PUT    /api/v1/solicitudes/{id}                    Actualizar solicitud
PATCH  /api/v1/solicitudes/{id}/status             Cambiar estado
POST   /api/v1/solicitudes/{id}/assign            Asignar solicitud
```

### Gestión Tecnológica - Activos

```
GET    /api/v1/gestion/activos                      Listar activos
GET    /api/v1/gestion/activos/category/{catId}    Por categoría
GET    /api/v1/gestion/activos/assignee/{userId}    Por asignado
GET    /api/v1/gestion/activos/{id}                 Obtener activo
POST   /api/v1/gestion/activos                      Crear activo
PUT    /api/v1/gestion/activos/{id}                 Actualizar activo
PATCH  /api/v1/gestion/activos/{id}/status          Cambiar estado
PATCH  /api/v1/gestion/activos/{id}/asset-status    Cambiar estado activo
POST   /api/v1/gestion/activos/{id}/assign           Asignar activo
```

---

## MÓDULOS DEL SISTEMA

### Módulo Dashboard

**Propósito**: Pantalla principal del sistema  
**Archivo**: frontend/src/modules/dashboard/pages/DashboardPage.jsx  
**Permisos**: dashboard.ver  
**Funcionalidades**:
- Vista general del sistema
- Estadísticas resumidas
- Accesos rápidos a módulos

### Módulo de Seguridad

**Propósito**: Administración de seguridad del sistema  
**Archivos**:
- Backend: Controllers/Security/, Services/Security/, Repositories/Security/
- Frontend: modules/security/pages/SecurityPage.jsx, modules/security/services/securityAPI.js  
**Permisos**: seguridad.ver, seguridad.administrar_*, etc.  
**Funcionalidades**:
- CRUD de aplicaciones
- CRUD de permisos
- CRUD de roles
- CRUD de usuarios
- Asignación de roles a usuarios
- Asignación de permisos a roles

### Módulo Intranet

**Propósito**: Comunicación interna de la empresa  
**Archivos**:
- Backend: Controllers/Intranet/, Services/Intranet/, Repositories/Intranet/
- Frontend: modules/intranet/pages/IntranetPage.jsx, modules/intranet/services/intranetAPI.js  
**Permisos**: intranet.ver, intranet.administrar_*  
**Funcionalidades**:
- Noticias y anuncios
- Documentos compartidos
- Calendario de eventos
- Foro de discusión

### Módulo Solicitudes

**Propósito**: Gestión de tickets de soporte  
**Archivos**:
- Backend: Controllers/Solicitudes/, Services/Solicitudes/, Repositories/Solicitudes/
- Frontend: modules/solicitudes/pages/SolicitudesPage.jsx, modules/solicitudes/services/solicitudesAPI.js  
**Permisos**: solicitudes.ver, solicitudes.crear, solicitudes.administrar_*  
**Funcionalidades**:
- Creación de solicitudes
- Categorización de solicitudes
- Asignación de solicitudes
- Seguimiento de solicitudes

### Módulo Gestión Tecnológica

**Propósito**: Administración de activos tecnológicos  
**Archivos**:
- Backend: Controllers/Gestion/, Services/Gestion/, Repositories/Gestion/
- Frontend: modules/gestion/pages/GestionPage.jsx, modules/gestion/services/gestionAPI.js  
**Permisos**: gestion.ver, gestion.administrar_*  
**Funcionalidades**:
- Inventario de activos
- Gestión de mantenimiento
- Gestión de incidencias
- Gestión de licencias de software

---

## CONCLUSIÓN

Este documento proporciona una visión completa y detallada del sistema Gacela Conecta Beta V2, incluyendo:

1. **Arquitectura completa**: Separación clara entre frontend y backend
2. **Estructura de archivos**: Cada archivo con su propósito y dependencias
3. **Base de datos**: Todas las tablas con sus relaciones y usos
4. **Flujos de datos**: Cómo fluye la información a través del sistema
5. **API endpoints**: Todas las rutas disponibles
6. **Módulos**: Descripción completa de cada módulo funcional

El sistema está diseñado siguiendo las mejores prácticas de desarrollo empresarial, con:
- Separación de responsabilidades
- Patrones de diseño reutilizables
- Seguridad granular
- Escalabilidad y mantenibilidad

Para el desarrollo de nuevos módulos, seguir la guía detallada en `docs/CORE_DEL_SISTEMA.md`.