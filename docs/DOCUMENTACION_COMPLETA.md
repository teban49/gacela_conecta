# Documentación Completa del Proyecto Gacela Conecta Beta_V2

## Índice
1. [Descripción General del Proyecto](#descripción-general-del-proyecto)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura de Directorios](#estructura-de-directorios)
4. [Documentación de Archivos - Raíz](#documentación-de-archivos---raíz)
5. [Documentación de Archivos - Backend](#documentación-de-archivos---backend)
6. [Documentación de Archivos - Frontend](#documentación-de-archivos---frontend)
7. [Documentación de Archivos - Database](#documentación-de-archivos---database)
8. [Documentación de Archivos - Scripts](#documentación-de-archivos---scripts)
9. [Archivos Vacíos y Redundantes](#archivos-vacíos-y-redundantes)
10. [Conexiones Entre Archivos](#conexiones-entre-archivos)
11. [Flujo de Autenticación](#flujo-de-autenticación)

---

## Descripción General del Proyecto

**Gacela Conecta** es una plataforma corporativa del Grupo Gacela implementada según las especificaciones SADD v1.0. Es una aplicación web que sigue el principio **GCP FIRST** (Gacela Core Platform First), donde el Frontend (React + Vite) y el Backend (PHP) están 100% desacoplados, comunicándose exclusivamente mediante JSON a través de una API REST.

### Características Principales
- **Autenticación sin contraseñas** (Magic Links)
- **Control de acceso basado en roles** (RBAC)
- **Estructura organizacional jerárquica** (Grupo > Empresa > Ciudad > Agencia > Área > Cargo > Usuario)
- **Sistema de auditoría** automático
- **Gestión centralizada de archivos**
- **Sistema de notificaciones**

---

## Arquitectura del Sistema

### Stack Tecnológico

#### Backend
- **PHP 8.2+**
- **API RESTful**
- **MySQL 8.0+**
- **Firebase JWT** para autenticación
- **PHPMailer** para envío de correos
- **vlucas/phpdotenv** para gestión de variables de entorno

#### Frontend
- **React 18.3+**
- **Vite** como bundler
- **React Router DOM** para enrutamiento
- **Axios** para comunicación API
- **Tailwind CSS** para estilos
- **Lucide React** para iconos

### Servicios Transversales GCP
1. **Authentication Engine** - Magic Links / Passwordless
2. **Authorization Engine** - RBAC (Permisos por Rol/Cargo)
3. **Organization Engine** - Grupo > Empresa > Ciudad > Agencia > Área > Cargo > Usuario
4. **Audit Engine** - Registro de bitácora automático
5. **Files Engine** - Gestión centralizada de adjuntos
6. **Notification Engine** - Envío de correos y alertas

---

## Estructura de Directorios

```
Gacela Conecta Beta_V2/
├── README.md                          # Documentación principal del proyecto
├── backend/                           # Aplicación backend en PHP
│   ├── app/                          # Código fuente de la aplicación
│   │   ├── Console/                  # Comandos de consola y scheduler
│   │   ├── Controllers/              # Controladores de la API
│   │   ├── Core/                     # Clases base del sistema
│   │   ├── Enums/                    # Enumeraciones del sistema
│   │   ├── Exceptions/               # Excepciones personalizadas
│   │   ├── Helpers/                  # Funciones auxiliares
│   │   ├── Mail/                     # Sistema de correos
│   │   ├── Middleware/               # Middleware de la aplicación
│   │   ├── Models/                   # Modelos de datos
│   │   ├── Notifications/            # Sistema de notificaciones
│   │   └── Services/                 # Lógica de negocio
│   ├── config/                       # Archivos de configuración
│   ├── public/                       # Punto de entrada público
│   ├── routes/                       # Definición de rutas
│   ├── composer.json                 # Dependencias PHP
│   └── composer.lock                 # Lock de dependencias
├── database/                         # Scripts de base de datos
│   ├── migrations/                   # Migraciones SQL
│   ├── procedures/                   # Procedimientos almacenados
│   ├── functions/                    # Funciones SQL
│   ├── triggers/                     # Triggers SQL
│   ├── views/                        # Vistas SQL
│   ├── seeds/                        # Datos de prueba
│   ├── backups/                      # Backups de base de datos
│   └── README.md                     # Documentación de base de datos (VACÍO)
├── docs/                             # Documentación del proyecto
│   ├── API/                          # Documentación de API
│   ├── UI-UX/                        # Documentación de interfaz
│   ├── SADD/                         # Documentación SADD
│   ├── SDD/                          # Documentación de diseño de software
│   ├── Backlog/                      # Backlog del proyecto
│   ├── Meeting Notes/                # Notas de reuniones
│   └── ADR/                          # Architecture Decision Records
├── frontend/                         # Aplicación frontend en React
│   ├── public/                       # Archivos estáticos públicos
│   ├── src/                          # Código fuente React
│   │   ├── assets/                   # Imágenes y recursos
│   │   ├── components/               # Componentes reutilizables
│   │   ├── contexts/                 # Contextos React
│   │   ├── constants/                # Constantes de la aplicación
│   │   ├── hooks/                    # Custom hooks
│   │   ├── modules/                  # Módulos de funcionalidad
│   │   ├── router/                   # Configuración de rutas
│   │   ├── services/                 # Servicios de API
│   │   ├── utils/                    # Utilidades
│   │   ├── App.jsx                   # Componente principal (VACÍO)
│   │   ├── main.jsx                  # Punto de entrada
│   │   └── index.css                 # Estilos globales
│   ├── dist/                         # Build de producción
│   ├── node_modules/                 # Dependencias npm
│   ├── index.html                    # HTML de entrada
│   ├── package.json                  # Dependencias npm
│   ├── package-lock.json             # Lock de dependencias
│   ├── vite.config.js                # Configuración de Vite
│   ├── tailwind.config.js            # Configuración de Tailwind
│   └── postcss.config.js             # Configuración de PostCSS
└── scripts/                          # Scripts de automatización
    ├── install.bat                   # Script de instalación Windows
    ├── install.sh                    # Script de instalación Linux/Mac
    ├── deploy.bat                    # Script de despliegue Windows
    ├── deploy.sh                     # Script de despliegue Linux/Mac
    ├── backup-db.bat                  # Script de backup DB Windows
    ├── backup-db.sh                  # Script de backup DB Linux/Mac
    ├── reset-db.bat                  # Script de reset DB Windows
    └── clear-cache.bat               # Script de limpieza de cache
```

---

## Documentación de Archivos - Raíz

### README.md
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/README.md`
- **Tamaño**: 8,534 bytes (327 líneas)
- **Estado**: ACTIVO
- **Descripción**: Documentación principal del proyecto que incluye:
  - Arquitectura general del sistema
  - Stack tecnológico completo
  - Instrucciones de instalación para backend y frontend
  - Flujo de autenticación con Magic Links
  - Estructura de base de datos
  - Endpoints de la API
  - Configuración de seguridad
  - Scripts disponibles
- **Conexiones**: Es el punto de referencia principal para desarrolladores
- **Parámetros importantes**: 
  - Variables de entorno requeridas
  - Configuración de JWT (12 horas expiración)
  - Configuración de Magic Links (15 minutos expiración)

---

## Documentación de Archivos - Backend

### composer.json
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/composer.json`
- **Tamaño**: 22 bytes (22 líneas)
- **Estado**: ACTIVO
- **Descripción**: Archivo de configuración de dependencias PHP
- **Dependencias principales**:
  - `php: ^8.2` - Versión mínima de PHP requerida
  - `firebase/php-jwt: ^6.11` - Librería para generación y validación de tokens JWT
  - `phpmailer/phpmailer: ^6.10` - Librería para envío de correos
  - `vlucas/phpdotenv: ^5.6` - Librería para carga de variables de entorno
- **Autoloading**: Configurado con PSR-4 para namespace `App\`
- **Conexiones**: Es utilizado por Composer para instalar dependencias en `vendor/`

### composer.lock
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/composer.lock`
- **Estado**: ACTIVO
- **Descripción**: Archivo de lock de dependencias que asegura versiones consistentes
- **Conexiones**: Generado automáticamente por Composer

---

### app/Core/Kernel.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Kernel.php`
- **Tamaño**: 72 líneas
- **Estado**: ACTIVO
- **Descripción**: Clase principal de inicialización de la aplicación
- **Funcionalidades**:
  1. `bootstrap()` - Método principal que inicia la aplicación
  2. `loadEnv()` - Carga variables de entorno usando phpdotenv
  3. `initializeServices()` - Inicializa servicios centrales (Database, JWT, Mail)
  4. `loadRoutes()` - Carga todos los archivos de rutas del directorio `routes/`
- **Conexiones**:
  - Conecta con `config/constants.php` para constantes globales
  - Conecta con `config/database.php` para configuración de base de datos
  - Conecta con `config/jwt.php` para configuración de JWT
  - Conecta con `config/mail.php` para configuración de correo
  - Conecta con `Router::dispatch()` para despachar peticiones
- **Parámetros importantes**:
  - Ruta de constants.php: `dirname(__DIR__, 2) . '/config/constants.php'`
  - Ruta de config: `__DIR__ . '/../../config/'`

### app/Core/Application.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Application.php`
- **Estado**: VACÍO
- **Descripción**: Archivo vacío, posiblemente planeado para uso futuro
- **Recomendación**: Eliminar o implementar funcionalidad

### app/Core/Config.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Config.php`
- **Estado**: VACÍO
- **Descripción**: Archivo vacío, posiblemente planeado para gestión de configuración
- **Recomendación**: Eliminar o implementar funcionalidad de gestión de configuración

### app/Core/Database.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Database.php`
- **Tamaño**: 47 líneas
- **Estado**: ACTIVO
- **Descripción**: Clase singleton para gestión de conexión a base de datos PDO
- **Funcionalidades**:
  1. `init(array $config)` - Inicializa la conexión con configuración
  2. `getConnection()` - Retorna instancia PDO (singleton)
- **Parámetros de configuración**:
  - `host` - Servidor de base de datos (default: localhost)
  - `port` - Puerto (default: 3306)
  - `database` - Nombre de base de datos (default: gacela_conecta_beta)
  - `username` - Usuario (default: root)
  - `password` - Contraseña (default: vacío)
  - `charset` - Charset (default: utf8mb4)
  - `options` - Opciones PDO (ERRMODE_EXCEPTION, FETCH_ASSOC, EMULATE_PREPARES false)
- **Conexiones**:
  - Utilizado por todos los Models
  - Inicializado por `Kernel::initializeServices()`
- **Manejo de errores**: Lanza RuntimeException si falla la conexión

### app/Core/Router.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Router.php`
- **Tamaño**: 76 líneas
- **Estado**: ACTIVO
- **Descripción**: Enrutador HTTP que maneja rutas REST
- **Funcionalidades**:
  1. `get(string $path, callable $handler)` - Registra ruta GET
  2. `post(string $path, callable $handler)` - Registra ruta POST
  3. `put(string $path, callable $handler)` - Registra ruta PUT
  4. `delete(string $path, callable $handler)` - Registra ruta DELETE
  5. `dispatch()` - Despacha la petición actual
  6. `convertPathToRegex(string $path)` - Convierte ruta a regex
  7. `setCorsHeaders()` - Establece headers CORS
- **Parámetros de rutas**: Soporta parámetros dinámicos con sintaxis `{param}`
- **CORS configuración**:
  - Origin: `*` (todos)
  - Methods: GET, POST, PUT, DELETE, OPTIONS
  - Headers: Content-Type, Authorization
  - Max-Age: 86400 segundos (24 horas)
- **Conexiones**:
  - Utiliza `Request` para obtener método y URI
  - Utiliza `Response` para respuestas de error
  - Llamado por `Kernel::bootstrap()`

### app/Core/Request.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Request.php`
- **Tamaño**: 109 líneas
- **Estado**: ACTIVO
- **Descripción**: Clase para manejo de peticiones HTTP
- **Funcionalidades**:
  1. `getMethod()` - Retorna método HTTP (GET, POST, PUT, DELETE)
  2. `getUri()` - Retorna URI de la petición
  3. `getData()` - Retorna datos de la petición
  4. `get(string $key, mixed $default)` - Retorna dato específico
  5. `has(string $key)` - Verifica si existe un dato
  6. `getHeader(string $key, mixed $default)` - Retorna header específico
  7. `getBearerToken()` - Extrae token Bearer del header Authorization
  8. `getIp()` - Retorna IP del cliente
  9. `getUserAgent()` - Retorna User Agent del cliente
- **Parseo de datos**:
  - GET: lee de `$_GET`
  - JSON: parsea `php://input` con Content-Type application/json
  - Form: lee de `$_POST`
- **Parseo de headers**: Convierte headers HTTP_ a formato normalizado
- **Prioridad de IP**: HTTP_X_FORWARDED_FOR > HTTP_CLIENT_IP > REMOTE_ADDR > 0.0.0.0
- **Conexiones**: Utilizado por `Router` y todos los Controladores

### app/Core/Response.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Response.php`
- **Tamaño**: 79 líneas
- **Estado**: ACTIVO
- **Descripción**: Clase para respuestas HTTP estandarizadas
- **Funcionalidades**:
  1. `json()` - Respuesta JSON genérica con todos los parámetros
  2. `success()` - Respuesta exitosa (200)
  3. `created()` - Recurso creado (201)
  4. `error()` - Error genérico (400)
  5. `unauthorized()` - No autorizado (401)
  6. `forbidden()` - Acceso denegado (403)
  7. `notFound()` - Recurso no encontrado (404)
  8. `validationError()` - Error de validación (422)
  9. `serverError()` - Error interno del servidor (500)
- **Estructura de respuesta JSON**:
  ```json
  {
    "success": boolean,
    "data": mixed,
    "message": string,
    "errors": array
  }
  ```
- **Uso de constantes**: Soporta constantes definidas para keys y códigos HTTP
  - `RESPONSE_KEY_SUCCESS`, `RESPONSE_KEY_DATA`, `RESPONSE_KEY_MESSAGE`, `RESPONSE_KEY_ERRORS`
  - `HTTP_OK`, `HTTP_CREATED`, `HTTP_UNAUTHORIZED`, `HTTP_FORBIDDEN`, `HTTP_NOT_FOUND`, `HTTP_UNPROCESSABLE_ENTITY`, `HTTP_INTERNAL_SERVER_ERROR`
- **Conexiones**: Utilizado por todos los Controladores

### app/Core/Auth.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Auth.php`
- **Estado**: VACÍO
- **Descripción**: Archivo vacío, posiblemente planeado para funcionalidad de autenticación
- **Recomendación**: Eliminar o implementar funcionalidad de autenticación centralizada

### app/Core/JwtService.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/JwtService.php`
- **Tamaño**: 65 líneas
- **Estado**: ACTIVO
- **Descripción**: Servicio para generación y validación de tokens JWT
- **Funcionalidades**:
  1. `init(array $config)` - Inicializa configuración JWT
  2. `encode(array $payload)` - Genera token JWT
  3. `decode(string $token)` - Decodifica token JWT
  4. `validate(string $token)` - Valida token JWT
  5. `generateMagicLinkToken(int $userId)` - Genera token para magic link
  6. `hashToken(string $token)` - Hashea token con SHA-256
- **Parámetros de configuración**:
  - `secret` - Clave secreta para firma (default: 'default_secret')
  - `algorithm` - Algoritmo de firma (default: 'HS256')
  - `expiration` - Tiempo de expiración en segundos (default: 43200 = 12 horas)
- **Payload JWT**: Incluye `iat` (issued at) y `exp` (expiration)
- **Magic Link Token**: Generado con random_bytes(32) + userId + timestamp, hasheado con SHA-256
- **Conexiones**:
  - Utilizado por `AuthService`
  - Inicializado por `Kernel::initializeServices()`
- **Dependencias**: firebase/php-jwt

### app/Core/Mail.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Mail.php`
- **Tamaño**: 663 líneas
- **Estado**: ACTIVO
- **Descripción**: Servicio para envío de correos electrónicos
- **Funcionalidades**:
  1. `init(array $config)` - Inicializa configuración de correo
  2. `sendMagicLink(string $toEmail, string $userName, string $magicLink)` - Envía magic link
  3. `getConfig(string $key, string $default)` - Obtiene configuración de forma segura
- **Parámetros de configuración**:
  - `host` - Servidor SMTP (default: smtp.gmail.com)
  - `port` - Puerto SMTP (default: 587)
  - `encryption` - Encriptación (default: tls)
  - `username` - Usuario SMTP
  - `password` - Contraseña SMTP
  - `from_email` - Email remitente (default: username)
  - `from_name` - Nombre remitente (default: 'Gacela Conecta')
- **Mapeo de variables .env**:
  - MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- **Plantilla de correo HTML**: Incluye:
  - Banner corporativo con colores Gacela (naranja #f58634, azul #1b6eb8, rojo #ad130b)
  - Logo y branding
  - Mensaje personalizado con nombre del usuario
  - Botón de confirmación con magic link
  - Aviso de expiración (15 minutos)
  - Enlace alternativo
  - Sección de seguridad
  - Footer corporativo
- **Imagen embebida**: `public/gacela-access-banner.png` como CID
- **Conexiones**:
  - Utilizado por `AuthService`
  - Inicializado por `Kernel::initializeServices()`
- **Dependencias**: PHPMailer/PHPMailer
- **Manejo de errores**: Registra error en error_log y retorna false

### app/Core/Validator.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Validator.php`
- **Estado**: VACÍO
- **Descripción**: Archivo vacío, posiblemente planeado para validación de datos
- **Recomendación**: Implementar funcionalidad de validación de datos

### app/Core/Logger.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Core/Logger.php`
- **Estado**: VACÍO
- **Descripción**: Archivo vacío, posiblemente planeado para logging
- **Recomendación**: Implementar funcionalidad de logging

---

### app/Controllers/AuthController.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Controllers/AuthController.php`
- **Tamaño**: 107 líneas
- **Estado**: ACTIVO
- **Descripción**: Controlador para endpoints de autenticación
- **Funcionalidades**:
  1. `requestMagicLink()` - POST `/api/v1/auth/magic-link` - Solicita magic link
  2. `verifyToken()` - POST `/api/v1/auth/verify-token` - Verifica token y crea sesión
  3. `me()` - GET `/api/v1/auth/me` - Obtiene usuario autenticado actual
  4. `logout()` - POST `/api/v1/auth/logout` - Cierra sesión
- **Validaciones**:
  - Email requerido y formato válido
  - Token requerido
- **Parámetros capturados**:
  - IP del cliente via `$request->getIp()`
  - User Agent via `$request->getUserAgent()`
- **Conexiones**:
  - Utiliza `AuthService` para lógica de negocio
  - Utiliza `Request` para obtener datos
  - Utiliza `Response` para respuestas
- **Endpoints**:
  - POST `/api/v1/auth/magic-link` - Request body: `{ "email": "usuario@empresa.com" }`
  - POST `/api/v1/auth/verify-token` - Request body: `{ "token": "token-magic-link" }`
  - GET `/api/v1/auth/me` - Header: `Authorization: Bearer {jwt-token}`
  - POST `/api/v1/auth/logout` - Header: `Authorization: Bearer {jwt-token}`

### app/Controllers/UserController.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Controllers/UserController.php`
- **Estado**: VACÍO
- **Descripción**: Controlador vacío para gestión de usuarios
- **Recomendación**: Implementar CRUD de usuarios

### app/Controllers/CompanyController.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Controllers/CompanyController.php`
- **Estado**: VACÍO
- **Descripción**: Controlador vacío para gestión de empresas
- **Recomendación**: Implementar CRUD de empresas

### app/Controllers/DepartmentController.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Controllers/DepartmentController.php`
- **Estado**: VACÍO
- **Descripción**: Controlador vacío para gestión de departamentos
- **Recomendación**: Implementar CRUD de departamentos

### app/Controllers/PositionController.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Controllers/PositionController.php`
- **Estado**: VACÍO
- **Descripción**: Controlador vacío para gestión de cargos
- **Recomendación**: Implementar CRUD de cargos

### app/Controllers/RoleController.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Controllers/RoleController.php`
- **Estado**: VACÍO
- **Descripción**: Controlador vacío para gestión de roles
- **Recomendación**: Implementar CRUD de roles

### app/Controllers/MenuController.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Controllers/MenuController.php`
- **Estado**: VACÍO
- **Descripción**: Controlador vacío para gestión de menús
- **Recomendación**: Implementar gestión de menús dinámicos

---

### app/Models/User.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Models/User.php`
- **Tamaño**: 172 líneas
- **Estado**: ACTIVO
- **Descripción**: Modelo para gestión de usuarios
- **Constantes**:
  - `STATE_ACTIVE = 1` - Estado activo del usuario
- **Funcionalidades**:
  1. `findByEmail(string $email)` - Busca usuario por email corporativo
  2. `findById(int $id)` - Busca usuario por ID
  3. `getUserPermissions(int $userId)` - Obtiene permisos del usuario
  4. `getOrganizationData(int $userId)` - Obtiene datos organizacionales del usuario
- **Joins en consultas**:
  - `gcp_agencias` - Datos de agencia
  - `gcp_cargos` - Datos de cargo
  - `gcp_areas` - Datos de área
  - `gcp_usuarios.RolId` - Rol único asignado al usuario
  - `gcp_roles` - Datos de roles
  - `gcp_usuariosempresas` - Empresas del usuario
  - `gcp_empresas` - Datos de empresas
- **Datos retornados por findByEmail/findById**:
  - Datos básicos del usuario
  - AgenciaNombre, AgenciaCiudad, AgenciaCodigo
  - CargoNombre, CargoCodigo
  - AreaId, AreaNombre
  - Roles (array de IDs)
  - RolesCodigos (array de códigos)
  - EmpresasIds (array de IDs)
  - Empresas (array de objetos con id y nombre)
- **Permisos**: Retorna array de CodigoUnico de permisos
- **Conexiones**:
  - Utiliza `Database::getConnection()`
  - Utilizado por `AuthService`

### app/Models/AuthToken.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Models/AuthToken.php`
- **Tamaño**: 88 líneas
- **Estado**: ACTIVO
- **Descripción**: Modelo para gestión de tokens de autenticación
- **Funcionalidades**:
  1. `create(array $data)` - Crea nuevo token
  2. `findByTokenHash(string $tokenHash)` - Busca token por hash
  3. `markAsUsed(int $tokenId)` - Marca token como usado
  4. `cleanupExpired()` - Elimina tokens expirados
- **Campos de create**:
  - UsuarioId (requerido)
  - TokenHash (requerido)
  - Tipo (default: 'MAGIC_LINK' o constante TOKEN_TYPE_MAGIC_LINK)
  - FechaExpiracion (requerido)
  - IpAddress (opcional)
  - UserAgent (opcional)
- **Constantes utilizadas**:
  - `TOKEN_TYPE_MAGIC_LINK` - Tipo de token magic link
  - `TOKEN_STATE_UNUSED` - Estado no usado (default: 0)
  - `TOKEN_STATE_USED` - Estado usado (default: 1)
- **Conexiones**:
  - Utiliza `Database::getConnection()`
  - Utilizado por `AuthService`
- **Foreign Key**: UsuarioId referencia GCP_Usuarios(Id) ON DELETE CASCADE

### app/Models/Company.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Models/Company.php`
- **Estado**: VACÍO
- **Descripción**: Modelo vacío para gestión de empresas
- **Recomendación**: Implementar modelo de empresas

### app/Models/Department.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Models/Department.php`
- **Estado**: VACÍO
- **Descripción**: Modelo vacío para gestión de departamentos
- **Recomendación**: Implementar modelo de departamentos

### app/Models/Position.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Models/Position.php`
- **Estado**: VACÍO
- **Descripción**: Modelo vacío para gestión de cargos
- **Recomendación**: Implementar modelo de cargos

### app/Models/Role.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Models/Role.php`
- **Estado**: VACÍO
- **Descripción**: Modelo vacío para gestión de roles
- **Recomendación**: Implementar modelo de roles

### app/Models/Menu.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Models/Menu.php`
- **Estado**: VACÍO
- **Descripción**: Modelo vacío para gestión de menús
- **Recomendación**: Implementar modelo de menús

---

### app/Services/AuthService.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Services/AuthService.php`
- **Tamaño**: 168 líneas
- **Estado**: ACTIVO
- **Descripción**: Servicio de lógica de negocio para autenticación
- **Funcionalidades**:
  1. `requestMagicLink(string $email, string $ip, string $userAgent)` - Solicita magic link
  2. `verifyToken(string $token, string $ip, string $userAgent)` - Verifica token
  3. `validateSessionToken(string $token)` - Valida token de sesión JWT
- **Flujo de requestMagicLink**:
  1. Busca usuario por email
  2. Si no existe, retorna mensaje genérico (prevención de enumeración)
  3. Genera token con `JwtService::generateMagicLinkToken()`
  4. Hashea token con `JwtService::hashToken()`
  5. Calcula expiración (15 minutos = 900 segundos)
  6. Almacena token en base de datos via `AuthToken::create()`
  7. Genera URL de magic link con APP_URL
  8. Envía correo via `Mail::sendMagicLink()`
  9. Retorna respuesta genérica
- **Flujo de verifyToken**:
  1. Hashea token recibido
  2. Busca token en base de datos via `AuthToken::findByTokenHash()`
  3. Si no existe o expiró, retorna error
  4. Obtiene usuario via `User::findById()`
  5. Marca token como usado via `AuthToken::markAsUsed()`
  6. Obtiene permisos del usuario via `User::getUserPermissions()`
  7. Obtiene datos organizacionales via `User::getOrganizationData()`
  8. Genera JWT con payload (sub, email, roles, permissions)
  9. Retorna token JWT y datos del usuario
- **Flujo de validateSessionToken**:
  1. Decodifica token JWT via `JwtService::decode()`
  2. Si inválido, retorna null
  3. Obtiene usuario via `User::findById()`
  4. Si no existe, retorna null
  5. Obtiene datos organizacionales
  6. Retorna datos del usuario con permisos del token
- **Conexiones**:
  - Utiliza `User` model
  - Utiliza `AuthToken` model
  - Utiliza `JwtService`
  - Utiliza `Mail`
  - Utilizado por `AuthController`
- **Variables de entorno**:
  - APP_URL - URL de la aplicación (default: http://192.168.10.156:5176)

---

### app/Middleware/AuthMiddleware.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Middleware/AuthMiddleware.php`
- **Estado**: VACÍO
- **Descripción**: Middleware vacío para autenticación
- **Recomendación**: Implementar middleware de autenticación

### app/Middleware/JwtMiddleware.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Middleware/JwtMiddleware.php`
- **Estado**: VACÍO
- **Descripción**: Middleware vacío para validación JWT
- **Recomendación**: Implementar middleware de validación JWT

### app/Middleware/CorsMiddleware.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Middleware/CorsMiddleware.php`
- **Estado**: VACÍO
- **Descripción**: Middleware vacío para CORS
- **Nota**: CORS está manejado en `Router::setCorsHeaders()`
- **Recomendación**: Eliminar (funcionalidad duplicada)

### app/Middleware/PermissionMiddleware.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Middleware/PermissionMiddleware.php`
- **Estado**: VACÍO
- **Descripción**: Middleware vacío para validación de permisos
- **Recomendación**: Implementar middleware de permisos RBAC

### app/Middleware/AuditMiddleware.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Middleware/AuditMiddleware.php`
- **Estado**: VACÍO
- **Descripción**: Middleware vacío para auditoría
- **Recomendación**: Implementar middleware de auditoría

---

### app/Enums/PermissionType.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Enums/PermissionType.php`
- **Estado**: VACÍO
- **Descripción**: Enum vacío para tipos de permisos
- **Recomendación**: Implementar enum de tipos de permisos (crear, leer, actualizar, eliminar)

### app/Enums/RequestStatus.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Enums/RequestStatus.php`
- **Estado**: VACÍO
- **Descripción**: Enum vacío para estados de petición
- **Recomendación**: Implementar enum de estados (pending, approved, rejected)

### app/Enums/RoleType.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Enums/RoleType.php`
- **Estado**: VACÍO
- **Descripción**: Enum vacío para tipos de roles
- **Recomendación**: Implementar enum de tipos de roles (admin, user, guest)

### app/Enums/UserStatus.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Enums/UserStatus.php`
- **Estado**: VACÍO
- **Descripción**: Enum vacío para estados de usuario
- **Recomendación**: Implementar enum de estados (active, inactive, suspended)

---

### app/Exceptions/ApiException.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Exceptions/ApiException.php`
- **Estado**: VACÍO
- **Descripción**: Excepción vacía para errores de API
- **Recomendación**: Implementar excepción personalizada de API

### app/Exceptions/UnauthorizedException.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Exceptions/UnauthorizedException.php`
- **Estado**: VACÍO
- **Descripción**: Excepción vacía para no autorizado
- **Recomendación**: Implementar excepción de no autorizado

### app/Exceptions/ValidationException.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Exceptions/ValidationException.php`
- **Estado**: VACÍO
- **Descripción**: Excepción vacía para errores de validación
- **Recomendación**: Implementar excepción de validación

---

### app/Helpers/DateHelper.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Helpers/DateHelper.php`
- **Estado**: VACÍO
- **Descripción**: Helper vacío para fechas
- **Recomendación**: Implementar helper de fechas

### app/Helpers/FileHelper.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Helpers/FileHelper.php`
- **Estado**: VACÍO
- **Descripción**: Helper vacío para archivos
- **Recomendación**: Implementar helper de archivos

### app/Helpers/ResponseHelper.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Helpers/ResponseHelper.php`
- **Estado**: VACÍO
- **Descripción**: Helper vacío para respuestas
- **Recomendación**: Implementar helper de respuestas

### app/Helpers/StringHelper.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Helpers/StringHelper.php`
- **Estado**: VACÍO
- **Descripción**: Helper vacío para strings
- **Recomendación**: Implementar helper de strings

---

### app/Mail/MagicLinkMail.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Mail/MagicLinkMail.php`
- **Estado**: VACÍO
- **Descripción**: Clase vacía para mail de magic link
- **Nota**: La funcionalidad está en `Mail::sendMagicLink()`
- **Recomendación**: Eliminar (funcionalidad duplicada)

### app/Mail/MailService.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Mail/MailService.php`
- **Estado**: VACÍO
- **Descripción**: Servicio vacío de mail
- **Nota**: La funcionalidad está en `Mail` class
- **Recomendación**: Eliminar (funcionalidad duplicada)

### app/Mail/Providers/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Mail/Providers/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para providers de mail
- **Recomendación**: Eliminar si no se usará

### app/Mail/Templates/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Mail/Templates/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para templates de mail
- **Nota**: Los templates están inline en `Mail::sendMagicLink()`
- **Recomendación**: Mover templates HTML aquí o eliminar directorio

---

### app/Console/Kernel.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Console/Kernel.php`
- **Estado**: VACÍO
- **Descripción**: Kernel vacío de consola
- **Recomendación**: Implementar kernel de comandos de consola

### app/Console/Scheduler.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Console/Scheduler.php`
- **Estado**: VACÍO
- **Descripción**: Scheduler vacío para tareas programadas
- **Recomendación**: Implementar scheduler para tareas (cleanup de tokens, etc.)

### app/Console/Commands/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Console/Commands/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para comandos de consola
- **Recomendación**: Implementar comandos (migrate, seed, cleanup)

---

### app/Notifications/NotificationService.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Notifications/NotificationService.php`
- **Estado**: VACÍO
- **Descripción**: Servicio vacío de notificaciones
- **Recomendación**: Implementar servicio de notificaciones

### app/Notifications/Channels/EmailNotification.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Notifications/Channels/EmailNotification.php`
- **Estado**: VACÍO
- **Descripción**: Canal vacío de notificaciones por email
- **Recomendación**: Implementar canal de email

### app/Notifications/Channels/PushNotification.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Notifications/Channels/PushNotification.php`
- **Estado**: VACÍO
- **Descripción**: Canal vacío de notificaciones push
- **Recomendación**: Implementar canal de push

### app/Notifications/Channels/WhatsAppNotification.php
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/backend/app/Notifications/Channels/WhatsAppNotification.php`
- **Estado**: VACÍO
- **Descripción**: Canal vacío de notificaciones WhatsApp
- **Recomendación**: Implementar canal de WhatsApp

---

## Documentación de Archivos - Frontend

### package.json
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/package.json`
- **Tamaño**: 28 líneas
- **Estado**: ACTIVO
- **Descripción**: Archivo de configuración de dependencias npm
- **Dependencias principales**:
  - `react: ^18.3.1` - Framework React
  - `react-dom: ^18.3.1` - DOM de React
  - `react-router-dom: ^6.26.1` - Enrutamiento
  - `axios: ^1.7.7` - Cliente HTTP
  - `lucide-react: ^0.454.0` - Iconos
- **DevDependencies**:
  - `@types/react: ^18.3.3` - Types de React
  - `@types/react-dom: ^18.3.0` - Types de React DOM
  - `@vitejs/plugin-react: ^4.3.1` - Plugin React para Vite
  - `vite: ^5.4.2` - Bundler
  - `tailwindcss: ^3.4.1` - Framework CSS
  - `postcss: ^8.4.35` - Procesador CSS
  - `autoprefixer: ^10.4.17` - Prefijos automáticos
- **Scripts**:
  - `dev` - Inicia servidor de desarrollo
  - `build` - Build de producción
  - `preview` - Preview de producción

### package-lock.json
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/package-lock.json`
- **Estado**: ACTIVO
- **Descripción**: Lock de dependencias npm para versiones consistentes

### vite.config.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/vite.config.js`
- **Estado**: ACTIVO
- **Descripción**: Configuración de Vite

### tailwind.config.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/tailwind.config.js`
- **Estado**: ACTIVO
- **Descripción**: Configuración de Tailwind CSS

### postcss.config.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/postcss.config.js`
- **Estado**: ACTIVO
- **Descripción**: Configuración de PostCSS

### index.html
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/index.html`
- **Tamaño**: 14 líneas
- **Estado**: ACTIVO
- **Descripción**: HTML de entrada de la aplicación
- **Elementos**:
  - Charset UTF-8
  - Favicon: `/public/images/favicon.ico`
  - Viewport responsive
  - Title: "Gacela Conecta"
  - Div root: `<div id="root"></div>`
  - Script de entrada: `/src/main.jsx`
- **Conexiones**: Carga `main.jsx` que inicia la aplicación React

---

### src/App.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/App.jsx`
- **Tamaño**: 6 líneas
- **Estado**: VACÍO (solo retorna null)
- **Descripción**: Código mínimo que retorna null
- **Nota**: La aplicación se inicia desde `main.jsx` con RouterProvider
- **Recomendación**: Eliminar o implementar como componente de error boundary

### src/main.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/main.jsx`
- **Tamaño**: 15 líneas
- **Estado**: ACTIVO
- **Descripción**: Punto de entrada de la aplicación React
- **Funcionalidades**:
  - Importa React y ReactDOM
  - Importa AuthProvider del contexto
  - Importa router configurado
  - Importa estilos globales
  - Renderiza aplicación con StrictMode
- **Conexiones**:
  - Utiliza `contexts/AuthContext.jsx`
  - Utiliza `router/index.jsx`
  - Utiliza `index.css`

### src/index.css
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/index.css`
- **Tamaño**: 18 líneas
- **Estado**: ACTIVO
- **Descripción**: Estilos globales de la aplicación
- **Contenido**:
  - Directivas de Tailwind (@tailwind base, components, utilities)
  - Reset de body (margin: 0)
  - Font stack del sistema
  - Antialiasing
  - Font family para código
- **Conexiones**: Importado en `main.jsx`

---

### src/router/index.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/router/index.jsx`
- **Tamaño**: 98 líneas
- **Estado**: ACTIVO
- **Descripción**: Configuración de rutas de la aplicación
- **Componentes**:
  1. `ProtectedRoute` - Protege rutas que requieren autenticación
  2. `PublicRoute` - Rutas públicas (redirige si está autenticado)
  3. `ProtectedLayoutWrapper` - Wrapper con MainLayout para rutas protegidas
- **Rutas configuradas**:
  - `/login` - LoginPage (pública)
  - `/auth/verify` - VerifyPage (pública)
  - `/` - Redirect a /login
  - `/dashboard` - DashboardPage (protegida)
  - `/intranet` - IntranetHomePage (protegida)
  - `/intranet/calidad` - Placeholder módulo calidad (protegida)
- **Conexiones**:
  - Utiliza `useAuth` hook de AuthContext
  - Utiliza `MainLayout` component
  - Utiliza módulos de auth, dashboard, intranet
- **Loading state**: Muestra spinner mientras verifica autenticación

### src/router/GuestRoute.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/router/GuestRoute.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente para rutas de invitados (no implementado en router actual)

### src/router/PrivateRoute.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/router/PrivateRoute.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente para rutas privadas (no implementado en router actual)

---

### src/contexts/AuthContext.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/contexts/AuthContext.jsx`
- **Tamaño**: 79 líneas
- **Estado**: ACTIVO
- **Descripción**: Contexto React para gestión de autenticación
- **Estado**:
  - `user` - Datos del usuario
  - `loading` - Estado de carga
  - `isAuthenticated` - Boolean de autenticación
- **Funcionalidades**:
  1. `checkAuth()` - Verifica autenticación al montar
  2. `login(token, userData)` - Inicia sesión
  3. `logout()` - Cierra sesión
  4. `hasPermission(permission)` - Verifica permiso específico
  5. `hasRole(role)` - Verifica rol específico
- **Storage**: Utiliza localStorage para:
  - `auth_token` - Token JWT
  - `user_data` - Datos del usuario en JSON
- **Conexiones**:
  - Utilizado por `router/index.jsx`
  - Utilizado por `useAuth` hook
  - Utilizado por páginas de autenticación
- **Hook**: Exporta `useAuth()` custom hook

### src/contexts/ThemeContext.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/contexts/ThemeContext.jsx`
- **Estado**: VACÍO
- **Descripción**: Contexto vacío para tema
- **Recomendación**: Implementar contexto para tema claro/oscuro

### src/contexts/NotificationContext.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/contexts/NotificationContext.jsx`
- **Estado**: VACÍO
- **Descripción**: Contexto vacío para notificaciones
- **Recomendación**: Implementar contexto para notificaciones toast/alert

---

### src/services/api.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/services/api.js`
- **Tamaño**: 48 líneas
- **Estado**: ACTIVO
- **Descripción**: Cliente Axios configurado para API
- **Configuración**:
  - Base URL: `/api/v1`
  - API URL desde env: `VITE_API_URL` (default: http://localhost:8001)
  - Content-Type: application/json
- **Interceptors**:
  - Request: Agrega header Authorization con Bearer token desde localStorage
  - Response: Maneja 401 limpiando storage y redirigiendo a /login
- **APIs exportadas**:
  - `authAPI.requestMagicLink(email)` - POST /auth/magic-link
  - `authAPI.verifyToken(token)` - POST /auth/verify-token
  - `authAPI.getMe()` - GET /auth/me
  - `authAPI.logout()` - POST /auth/logout
- **Conexiones**:
  - Utilizado por módulos de auth
  - Utiliza localStorage para token

### src/services/http.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/services/http.js`
- **Estado**: VACÍO
- **Descripción**: Servicio HTTP vacío
- **Nota**: La funcionalidad está en `api.js`
- **Recomendación**: Eliminar (funcionalidad duplicada)

### src/services/auth.service.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/services/auth.service.js`
- **Estado**: VACÍO
- **Descripción**: Servicio de autenticación vacío
- **Nota**: La funcionalidad está en `api.js`
- **Recomendación**: Eliminar (funcionalidad duplicada)

### src/services/storage.service.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/services/storage.service.js`
- **Estado**: VACÍO
- **Descripción**: Servicio de almacenamiento vacío
- **Recomendación**: Implementar servicio para localStorage/sessionStorage

---

### src/hooks/useAuth.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/hooks/useAuth.js`
- **Estado**: VACÍO
- **Descripción**: Hook vacío de autenticación
- **Nota**: La funcionalidad está en `AuthContext.jsx`
- **Recomendación**: Eliminar (funcionalidad duplicada)

### src/hooks/usePermissions.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/hooks/usePermissions.js`
- **Estado**: VACÍO
- **Descripción**: Hook vacío de permisos
- **Recomendación**: Implementar hook para verificación de permisos

### src/hooks/useModal.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/hooks/useModal.js`
- **Estado**: VACÍO
- **Descripción**: Hook vacío para modales
- **Recomendación**: Implementar hook para gestión de modales

### src/hooks/usePagination.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/hooks/usePagination.js`
- **Estado**: VACÍO
- **Descripción**: Hook vacío para paginación
- **Recomendación**: Implementar hook para paginación

---

### src/constants/companyTheme.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/constants/companyTheme.js`
- **Estado**: ACTIVO
- **Descripción**: Constantes de tema corporativo

### src/utils/images.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/utils/images.js`
- **Estado**: ACTIVO
- **Descripción**: Utilidades para imágenes

---

### src/components/MainLayout.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/components/MainLayout.jsx`
- **Tamaño**: 30 líneas
- **Estado**: ACTIVO
- **Descripción**: Layout principal de la aplicación
- **Funcionalidades**:
  - Muestra DashboardHeader en todas las páginas
  - Muestra Sidebar excepto en home (/dashboard)
  - Outlet para contenido de rutas
- **Lógica**:
  - `isHomePage` - true si pathname es /dashboard o /
- **Conexiones**:
  - Utiliza `DashboardHeader`
  - Utiliza `Sidebar`
  - Utilizado por `router/index.jsx`

### src/components/Sidebar.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/components/Sidebar.jsx`
- **Tamaño**: 53 líneas
- **Estado**: ACTIVO
- **Descripción**: Componente de barra lateral de navegación
- **Items de menú**:
  - Home Principal (/dashboard) - Icono Home
  - Calidad (/intranet/calidad) - Icono ShieldCheck
  - Manuales (/intranet/manuales) - Icono BookOpen
  - Directorio (/intranet/directorio) - Icono Phone
  - Formatos (/intranet/formatos) - Icono FileText
  - Soporte TI (/intranet/soporte) - Icono Headphones
- **Funcionalidades**:
  - Filtrado por permisos (comentado, actualmente muestra todos)
  - Highlight de ruta activa
  - Estilos activos: bg-orange-50 text-orange-600
- **Conexiones**:
  - Utiliza react-router-dom (Link, useLocation)
  - Utiliza lucide-react (iconos)
  - Utilizado por `MainLayout`

---

### src/modules/auth/pages/LoginPage.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/pages/LoginPage.jsx`
- **Tamaño**: 111 líneas
- **Estado**: ACTIVO
- **Descripción**: Página de login con magic link
- **Estado local**:
  - `email` - Input de email
  - `loading` - Estado de carga
  - `message` - Mensaje de éxito
  - `error` - Mensaje de error
- **Funcionalidades**:
  - `handleSubmit()` - Envía solicitud de magic link
  - Valida email
  - Llama `authAPI.requestMagicLink()`
  - Muestra mensajes de éxito/error
- **UI**:
  - Gradiente de fondo (indigo-purple-pink)
  - Card blanca con shadow
  - Icono de Mail en círculo gradiente
  - Input de email con validación
  - Botón con icono Send
  - Loader durante carga
  - Mensajes de éxito (verde) y error (rojo)
- **Conexiones**:
  - Utiliza `authAPI` de services/api.js
  - Utiliza `useAuth` de AuthContext
  - Utiliza react-router-dom (useNavigate)
  - Utiliza lucide-react (Mail, Send, Loader2)

### src/modules/auth/pages/VerifyPage.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/pages/VerifyPage.jsx`
- **Tamaño**: 117 líneas
- **Estado**: ACTIVO
- **Descripción**: Página de verificación de magic link
- **Estado local**:
  - `status` - loading, success, error
  - `message` - Mensaje de estado
  - `hasRequested` - Ref para prevenir duplicados en StrictMode
- **Funcionalidades**:
  - `verifyToken()` - Verifica token de URL
  - Extrae token de searchParams
  - Llama `authAPI.verifyToken()`
  - Si exitoso: llama `login()` y redirige a /dashboard
  - Si error: muestra mensaje y botón para reintentar
- **UI**:
  - Iconos según estado (Loader2, CheckCircle, XCircle, AlertCircle)
  - Colores según estado (indigo, green, red, yellow)
  - Redirección automática después de 1.5s en éxito
- **Conexiones**:
  - Utiliza `authAPI` de services/api.js
  - Utiliza `useAuth` de AuthContext
  - Utiliza react-router-dom (useNavigate, useSearchParams)
  - Utiliza lucide-react (iconos)

### src/modules/auth/pages/CheckEmailPage.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/pages/CheckEmailPage.jsx`
- **Estado**: ACTIVO
- **Descripción**: Página para verificar email (no leída)

### src/modules/auth/components/LoginForm.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/components/LoginForm.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de formulario de login (no leído)

### src/modules/auth/components/LoginCard.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/components/LoginCard.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de card de login (no leído)

### src/modules/auth/components/LoginFooter.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/components/LoginFooter.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de footer de login (no leído)

### src/modules/auth/components/EmailInput.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/components/EmailInput.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de input de email (no leído)

### src/modules/auth/hooks/useLogin.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/hooks/useLogin.js`
- **Estado**: ACTIVO
- **Descripción**: Hook de login (no leído)

### src/modules/auth/index.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/index.js`
- **Estado**: ACTIVO
- **Descripción**: Export del módulo auth (no leído)

### src/modules/auth/types/auth.types.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/types/auth.types.js`
- **Estado**: ACTIVO
- **Descripción**: Types de auth (no leído)

### src/modules/auth/utils/auth.utils.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/utils/auth.utils.js`
- **Estado**: ACTIVO
- **Descripción**: Utilidades de auth (no leído)

### src/modules/auth/routes.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/auth/routes.jsx`
- **Estado**: ACTIVO
- **Descripción**: Rutas del módulo auth (no leído)

---

### src/modules/dashboard/pages/DashboardPage.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/pages/DashboardPage.jsx`
- **Tamaño**: 29 líneas
- **Estado**: ACTIVO
- **Descripción**: Página principal del dashboard
- **Componentes**:
  - WelcomeSection - Saludo personalizado
  - BannerSection - Banner promocional
  - ApplicationsSection - Aplicaciones disponibles
  - ProfileSection - Información del perfil
- **Props pasados**:
  - `user` - Datos del usuario
  - `permissions` - Permisos del usuario
  - `roles` - Roles del usuario
- **Conexiones**:
  - Utiliza `useAuth` de AuthContext
  - Utiliza componentes del módulo dashboard

### src/modules/dashboard/components/WelcomeSection.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/components/WelcomeSection.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de bienvenida (no leído)

### src/modules/dashboard/components/BannerSection.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/components/BannerSection.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de banner (no leído)

### src/modules/dashboard/components/ApplicationsSection.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/components/ApplicationsSection.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de aplicaciones (no leído)

### src/modules/dashboard/components/ApplicationCard.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/components/ApplicationCard.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de card de aplicación (no leído)

### src/modules/dashboard/components/ProfileSection.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/components/ProfileSection.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de perfil (no leído)

### src/modules/dashboard/components/DashboardHeader.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/components/DashboardHeader.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de header del dashboard (no leído)

### src/modules/dashboard/data/applications.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/data/applications.js`
- **Estado**: ACTIVO
- **Descripción**: Datos de aplicaciones (no leído)

### src/modules/dashboard/utils/companyColors.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/utils/companyColors.js`
- **Estado**: ACTIVO
- **Descripción**: Colores corporativos (no leído)

### src/modules/dashboard/index.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/index.js`
- **Estado**: ACTIVO
- **Descripción**: Export del módulo dashboard (no leído)

### src/modules/dashboard/routes.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/dashboard/routes.jsx`
- **Estado**: ACTIVO
- **Descripción**: Rutas del módulo dashboard (no leído)

---

### src/modules/intranet/pages/IntranetHomePage.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/pages/IntranetHomePage.jsx`
- **Estado**: ACTIVO
- **Descripción**: Página principal de intranet (no leída)

### src/modules/intranet/components/IntranetHero.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/components/IntranetHero.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente hero de intranet (no leído)

### src/modules/intranet/components/WelcomeCard.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/components/WelcomeCard.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de bienvenida (no leído)

### src/modules/intranet/components/QuickAccessCard.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/components/QuickAccessCard.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de acceso rápido (no leído)

### src/modules/intranet/components/ImportantNoticesCard.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/components/ImportantNoticesCard.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de avisos importantes (no leído)

### src/modules/intranet/components/HelpCenterCard.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/components/HelpCenterCard.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de centro de ayuda (no leído)

### src/modules/intranet/components/TipsCard.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/components/TipsCard.jsx`
- **Estado**: ACTIVO
- **Descripción**: Componente de tips (no leído)

### src/modules/intranet/data/quickLinks.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/data/quickLinks.js`
- **Estado**: ACTIVO
- **Descripción**: Datos de enlaces rápidos (no leído)

### src/modules/intranet/utils/heroSlides.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/intranet/utils/heroSlides.js`
- **Estado**: ACTIVO
- **Descripción**: Datos de slides hero (no leído)

---

### src/modules/companies/index.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/companies/index.js`
- **Estado**: ACTIVO
- **Descripción**: Export del módulo companies (no leído)

### src/modules/companies/routes.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/companies/routes.jsx`
- **Estado**: ACTIVO
- **Descripción**: Rutas del módulo companies (no leído)

### src/modules/departments/index.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/departments/index.js`
- **Estado**: ACTIVO
- **Descripción**: Export del módulo departments (no leído)

### src/modules/departments/routes.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/departments/routes.jsx`
- **Estado**: ACTIVO
- **Descripción**: Rutas del módulo departments (no leído)

### src/modules/positions/index.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/positions/index.js`
- **Estado**: ACTIVO
- **Descripción**: Export del módulo positions (no leído)

### src/modules/positions/routes.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/positions/routes.jsx`
- **Estado**: ACTIVO
- **Descripción**: Rutas del módulo positions (no leído)

### src/modules/roles/index.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/roles/index.js`
- **Estado**: ACTIVO
- **Descripción**: Export del módulo roles (no leído)

### src/modules/roles/routes.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/roles/routes.jsx`
- **Estado**: ACTIVO
- **Descripción**: Rutas del módulo roles (no leído)

### src/modules/users/index.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/users/index.js`
- **Estado**: ACTIVO
- **Descripción**: Export del módulo users (no leído)

### src/modules/users/routes.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/users/routes.jsx`
- **Estado**: ACTIVO
- **Descripción**: Rutas del módulo users (no leído)

### src/modules/menus/index.js
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/menus/index.js`
- **Estado**: ACTIVO
- **Descripción**: Export del módulo menus (no leído)

### src/modules/menus/routes.jsx
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/modules/menus/routes.jsx`
- **Estado**: ACTIVO
- **Descripción**: Rutas del módulo menus (no leído)

---

### src/assets/images/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/frontend/src/assets/images/`
- **Estado**: ACTIVO
- **Descripción**: Directorio de imágenes
- **Archivos**:
  - favicon.ico, favicon-16x16.png, favicon-32x32.png - Favicons
  - apple-touch-icon.png - Icono Apple
  - android-chrome-192x192.png, android-chrome-512x512.png - Iconos Android
  - icon.ico - Icono general
  - logo-gacela.png - Logo corporativo
  - dashboard-banner.png - Banner del dashboard
  - site.webmanifest - Manifest PWA
  - intranet/ - Directorio de iconos de intranet
    - Icono_Administracion.ico
    - Icono_Calidad.ico
    - Icono_Directorio.ico
    - Icono_Documentacion.ico
    - Icono_Formatos.ico
    - Icono_Linksaplicativos.ico
    - Icono_Manuales.ico
    - Icono_Varios.ico
    - Icono_pildoras.ico
    - banner1.png, banner2.png, banner3.png - Banners de intranet

---

## Documentación de Archivos - Database

### database/README.md
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/README.md`
- **Estado**: VACÍO
- **Descripción**: Documentación de base de datos vacía
- **Recomendación**: Documentar estructura de base de datos

### database/migrations/001_create_gcp_usuarios_table.sql
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/migrations/001_create_gcp_usuarios_table.sql`
- **Tamaño**: 32 líneas
- **Estado**: ACTIVO
- **Descripción**: Migración para crear tabla de usuarios
- **Tabla**: GCP_Usuarios
- **Campos**:
  - Id (INT AUTO_INCREMENT PRIMARY KEY)
  - GrupoId (INT NOT NULL) - FK a GCP_Grupos
  - EmpresaId (INT NOT NULL) - FK a GCP_Empresas
  - CiudadId (INT NOT NULL) - FK a GCP_Ciudades
  - AgenciaId (INT NOT NULL) - FK a GCP_Agencias
  - AreaId (INT NOT NULL) - FK a GCP_Areas
  - CargoId (INT NOT NULL) - FK a GCP_Cargos
  - Nombres (VARCHAR(100) NOT NULL)
  - Apellidos (VARCHAR(100) NOT NULL)
  - EmailCorporativo (VARCHAR(150) NOT NULL UNIQUE)
  - Telefono (VARCHAR(20))
  - Estado (TINYINT NOT NULL DEFAULT 1) - 1=Activo, 0=Inactivo
  - FechaCreacion (DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)
  - FechaActualizacion (DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
  - CreadoPor (INT)
  - ActualizadoPor (INT)
- **Índices**:
  - idx_grupo, idx_empresa, idx_ciudad, idx_agencia, idx_area, idx_cargo
  - idx_email (EmailCorporativo)
  - idx_estado (Estado)
- **Engine**: InnoDB, utf8mb4_unicode_ci

### database/migrations/002_create_gcp_authtokens_table.sql
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/migrations/002_create_gcp_authtokens_table.sql`
- **Tamaño**: 23 líneas
- **Estado**: ACTIVO
- **Descripción**: Migración para crear tabla de tokens de autenticación
- **Tabla**: GCP_AuthTokens
- **Campos**:
  - Id (INT AUTO_INCREMENT PRIMARY KEY)
  - UsuarioId (INT NOT NULL) - FK a GCP_Usuarios
  - TokenHash (VARCHAR(255) NOT NULL UNIQUE) - Hash SHA-256 del token
  - Tipo (VARCHAR(50) NOT NULL DEFAULT 'MAGIC_LINK') - MAGIC_LINK, PASSWORD_RESET, etc.
  - FechaExpiracion (DATETIME NOT NULL)
  - Usado (TINYINT NOT NULL DEFAULT 0) - 0=No usado, 1=Usado
  - FechaCreacion (DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)
  - FechaUso (DATETIME)
  - IpAddress (VARCHAR(45))
  - UserAgent (VARCHAR(255))
- **Índices**:
  - idx_usuario (UsuarioId)
  - idx_token (TokenHash)
  - idx_expiracion (FechaExpiracion)
  - idx_usado (Usado)
- **Foreign Key**: fk_authtokens_usuario -> GCP_Usuarios(Id) ON DELETE CASCADE
- **Engine**: InnoDB, utf8mb4_unicode_ci

### database/migrations/003_create_gcp_organizaciones_table.sql
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/migrations/003_create_gcp_organizaciones_table.sql`
- **Tamaño**: 107 líneas
- **Estado**: ACTIVO
- **Descripción**: Migración para crear tablas de estructura organizacional
- **Tablas creadas**:
  1. **GCP_Grupos** - Grupos organizacionales
     - Id, Nombre, Codigo (UNIQUE), Nit, Direccion, Telefono, Email, Estado
     - Índices: idx_codigo, idx_estado
  2. **GCP_Empresas** - Empresas
     - Id, GrupoId (FK), Nombre, Codigo, Nit, Direccion, Telefono, Email, Estado
     - UNIQUE: uk_grupo_codigo (GrupoId, Codigo)
     - FK: fk_empresas_grupo -> GCP_Grupos(Id) ON DELETE CASCADE
  3. **GCP_Ciudades** - Ciudades
     - Id, EmpresaId (FK), Nombre, Codigo, Departamento, Pais, Estado
     - UNIQUE: uk_empresa_codigo (EmpresaId, Codigo)
     - FK: fk_ciudades_empresa -> GCP_Empresas(Id) ON DELETE CASCADE
  4. **GCP_Agencias** - Agencias
     - Id, CiudadId (FK), Nombre, Codigo, Direccion, Telefono, Estado
     - UNIQUE: uk_ciudad_codigo (CiudadId, Codigo)
     - FK: fk_agencias_ciudad -> GCP_Ciudades(Id) ON DELETE CASCADE
  5. **GCP_Areas** - Áreas
     - Id, AgenciaId (FK), Nombre, Codigo, Descripcion, Estado
     - UNIQUE: uk_agencia_codigo (AgenciaId, Codigo)
     - FK: fk_areas_agencia -> GCP_Agencias(Id) ON DELETE CASCADE
  6. **GCP_Cargos** - Cargos
     - Id, AreaId (FK), Nombre, Codigo, Descripcion, NivelJerarquico, Estado
     - UNIQUE: uk_area_codigo (AreaId, Codigo)
     - FK: fk_cargos_area -> GCP_Areas(Id) ON DELETE CASCADE
- **Jerarquía**: Grupo > Empresa > Ciudad > Agencia > Área > Cargo
- **Engine**: InnoDB, utf8mb4_unicode_ci

### database/migrations/004_create_gcp_rbac_tables.sql
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/migrations/004_create_gcp_rbac_tables.sql`
- **Tamaño**: 59 líneas
- **Estado**: ACTIVO
- **Descripción**: Migración para crear tablas RBAC (Role-Based Access Control)
- **Tablas creadas**:
  1. **GCP_Roles** - Roles del sistema
     - Id, Nombre, Codigo (UNIQUE), Descripcion, NivelAcceso (1=Básico, 10=Admin), Estado
     - Índices: idx_codigo, idx_estado
  2. **GCP_Permisos** - Permisos del sistema
     - Id, Modulo, Accion, Descripcion, CodigoUnico (UNIQUE, ej: usuarios.crear), Estado
     - Índices: idx_modulo, idx_codigo, idx_estado
  3. **GCP_RolesPermisos** - Relación roles-permisos
     - Id, RolId (FK), PermisoId (FK), FechaCreacion
     - UNIQUE: uk_rol_permiso (RolId, PermisoId)
     - FKs: fk_rolespermisos_rol -> GCP_Roles(Id), fk_rolespermisos_permiso -> GCP_Permisos(Id)
  4. **GCP_UsuariosRoles** - Relación usuarios-roles
     - Id, UsuarioId (FK), RolId (FK), FechaCreacion
     - UNIQUE: uk_usuario_rol (UsuarioId, RolId)
     - FKs: fk_usuariosroles_usuario -> GCP_Usuarios(Id), fk_usuariosroles_rol -> GCP_Roles(Id)
- **Engine**: InnoDB, utf8mb4_unicode_ci

### database/migrations/005_create_gcp_audit_table.sql
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/migrations/005_create_gcp_audit_table.sql`
- **Tamaño**: 25 líneas
- **Estado**: ACTIVO
- **Descripción**: Migración para crear tabla de auditoría
- **Tabla**: GCP_AuditLog
- **Campos**:
  - Id (INT AUTO_INCREMENT PRIMARY KEY)
  - UsuarioId (INT) - FK a GCP_Usuarios
  - Accion (VARCHAR(100) NOT NULL) - Ej: login, crear_usuario
  - Modulo (VARCHAR(100) NOT NULL) - Ej: auth, usuarios
  - EntidadId (INT) - ID de entidad afectada
  - EntidadTipo (VARCHAR(100)) - Tipo de entidad
  - DatosAntes (JSON) - Estado antes del cambio
  - DatosDespues (JSON) - Estado después del cambio
  - IpAddress (VARCHAR(45))
  - UserAgent (VARCHAR(255))
  - FechaCreacion (DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)
- **Índices**:
  - idx_usuario (UsuarioId)
  - idx_accion (Accion)
  - idx_modulo (Modulo)
  - idx_entidad (EntidadId, EntidadTipo)
  - idx_fecha (FechaCreacion)
- **Foreign Key**: fk_audit_usuario -> GCP_Usuarios(Id) ON DELETE SET NULL
- **Engine**: InnoDB, utf8mb4_unicode_ci

### database/procedures/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/procedures/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para procedimientos almacenados
- **Recomendación**: Implementar procedimientos si es necesario

### database/functions/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/functions/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para funciones SQL
- **Recomendación**: Implementar funciones si es necesario

### database/triggers/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/triggers/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para triggers SQL
- **Recomendación**: Implementar triggers para auditoría automática

### database/views/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/views/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para vistas SQL
- **Recomendación**: Implementar vistas para reportes

### database/seeds/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/seeds/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para datos de prueba
- **Recomendación**: Implementar seeds para desarrollo

### database/backups/
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/database/backups/`
- **Estado**: DIRECTORIO VACÍO
- **Descripción**: Directorio para backups de base de datos
- **Recomendación**: Configurar backups automáticos

---

## Documentación de Archivos - Scripts

### scripts/install.bat
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/scripts/install.bat`
- **Estado**: ACTIVO
- **Descripción**: Script de instalación para Windows
- **Recomendación**: Revisar implementación

### scripts/install.sh
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/scripts/install.sh`
- **Estado**: ACTIVO
- **Descripción**: Script de instalación para Linux/Mac
- **Recomendación**: Revisar implementación

### scripts/deploy.bat
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/scripts/deploy.bat`
- **Estado**: ACTIVO
- **Descripción**: Script de despliegue para Windows
- **Recomendación**: Revisar implementación

### scripts/deploy.sh
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/scripts/deploy.sh`
- **Estado**: ACTIVO
- **Descripción**: Script de despliegue para Linux/Mac
- **Recomendación**: Revisar implementación

### scripts/backup-db.bat
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/scripts/backup-db.bat`
- **Estado**: ACTIVO
- **Descripción**: Script de backup de base de datos para Windows
- **Recomendación**: Revisar implementación

### scripts/backup-db.sh
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/scripts/backup-db.sh`
- **Estado**: ACTIVO
- **Descripción**: Script de backup de base de datos para Linux/Mac
- **Recomendación**: Revisar implementación

### scripts/reset-db.bat
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/scripts/reset-db.bat`
- **Estado**: ACTIVO
- **Descripción**: Script de reset de base de datos para Windows
- **Recomendación**: Revisar implementación

### scripts/clear-cache.bat
- **Ubicación**: `c:/Users/jmolano/Gacela Conecta Beta_V2/scripts/clear-cache.bat`
- **Estado**: ACTIVO
- **Descripción**: Script de limpieza de cache para Windows
- **Recomendación**: Revisar implementación

---

## Archivos Vacíos y Redundantes

### Archivos Vacíos (Requieren Implementación o Eliminación)

#### Backend
1. **app/Core/Application.php** - VACÍO - Eliminar o implementar
2. **app/Core/Config.php** - VACÍO - Eliminar o implementar gestión de config
3. **app/Core/Auth.php** - VACÍO - Eliminar o implementar auth centralizada
4. **app/Core/Validator.php** - VACÍO - Implementar validación
5. **app/Core/Logger.php** - VACÍO - Implementar logging
6. **app/Controllers/UserController.php** - VACÍO - Implementar CRUD usuarios
7. **app/Controllers/CompanyController.php** - VACÍO - Implementar CRUD empresas
8. **app/Controllers/DepartmentController.php** - VACÍO - Implementar CRUD departamentos
9. **app/Controllers/PositionController.php** - VACÍO - Implementar CRUD cargos
10. **app/Controllers/RoleController.php** - VACÍO - Implementar CRUD roles
11. **app/Controllers/MenuController.php** - VACÍO - Implementar gestión menús
12. **app/Models/Company.php** - VACÍO - Implementar modelo empresas
13. **app/Models/Department.php** - VACÍO - Implementar modelo departamentos
14. **app/Models/Position.php** - VACÍO - Implementar modelo cargos
15. **app/Models/Role.php** - VACÍO - Implementar modelo roles
16. **app/Models/Menu.php** - VACÍO - Implementar modelo menús
17. **app/Middleware/AuthMiddleware.php** - VACÍO - Implementar middleware auth
18. **app/Middleware/JwtMiddleware.php** - VACÍO - Implementar middleware JWT
19. **app/Middleware/CorsMiddleware.php** - VACÍO - ELIMINAR (duplicado con Router)
20. **app/Middleware/PermissionMiddleware.php** - VACÍO - Implementar middleware permisos
21. **app/Middleware/AuditMiddleware.php** - VACÍO - Implementar middleware auditoría
22. **app/Enums/PermissionType.php** - VACÍO - Implementar enum permisos
23. **app/Enums/RequestStatus.php** - VACÍO - Implementar enum estados
24. **app/Enums/RoleType.php** - VACÍO - Implementar enum roles
25. **app/Enums/UserStatus.php** - VACÍO - Implementar enum usuarios
26. **app/Exceptions/ApiException.php** - VACÍO - Implementar excepción API
27. **app/Exceptions/UnauthorizedException.php** - VACÍO - Implementar excepción auth
28. **app/Exceptions/ValidationException.php** - VACÍO - Implementar excepción validación
29. **app/Helpers/DateHelper.php** - VACÍO - Implementar helper fechas
30. **app/Helpers/FileHelper.php** - VACÍO - Implementar helper archivos
31. **app/Helpers/ResponseHelper.php** - VACÍO - Implementar helper respuestas
32. **app/Helpers/StringHelper.php** - VACÍO - Implementar helper strings
33. **app/Mail/MagicLinkMail.php** - VACÍO - ELIMINAR (duplicado con Mail)
34. **app/Mail/MailService.php** - VACÍO - ELIMINAR (duplicado con Mail)
35. **app/Console/Kernel.php** - VACÍO - Implementar kernel consola
36. **app/Console/Scheduler.php** - VACÍO - Implementar scheduler
37. **app/Notifications/NotificationService.php** - VACÍO - Implementar servicio notificaciones
38. **app/Notifications/Channels/EmailNotification.php** - VACÍO - Implementar canal email
39. **app/Notifications/Channels/PushNotification.php** - VACÍO - Implementar canal push
40. **app/Notifications/Channels/WhatsAppNotification.php** - VACÍO - Implementar canal WhatsApp

#### Frontend
1. **src/App.jsx** - VACÍO (retorna null) - Eliminar o implementar error boundary
2. **src/contexts/ThemeContext.jsx** - VACÍO - Implementar tema claro/oscuro
3. **src/contexts/NotificationContext.jsx** - VACÍO - Implementar notificaciones
4. **src/services/http.js** - VACÍO - ELIMINAR (duplicado con api.js)
5. **src/services/auth.service.js** - VACÍO - ELIMINAR (duplicado con api.js)
6. **src/services/storage.service.js** - VACÍO - Implementar servicio storage
7. **src/hooks/useAuth.js** - VACÍO - ELIMINAR (duplicado con AuthContext)
8. **src/hooks/usePermissions.js** - VACÍO - Implementar hook permisos
9. **src/hooks/useModal.js** - VACÍO - Implementar hook modales
10. **src/hooks/usePagination.js** - VACÍO - Implementar hook paginación

#### Database
1. **database/README.md** - VACÍO - Documentar estructura DB

#### Directorios Vacíos
1. **app/Mail/Providers/** - Eliminar si no se usará
2. **app/Mail/Templates/** - Mover templates aquí o eliminar
3. **app/Console/Commands/** - Implementar comandos o eliminar
4. **database/procedures/** - Implementar o eliminar
5. **database/functions/** - Implementar o eliminar
6. **database/triggers/** - Implementar o eliminar
7. **database/views/** - Implementar o eliminar
8. **database/seeds/** - Implementar o eliminar
9. **database/backups/** - Configurar o eliminar

---

## Conexiones Entre Archivos

### Flujo de Inicialización del Backend

1. **public/index.php** (no leído, punto de entrada)
   ↓
2. **Kernel::bootstrap()**
   ↓
3. **Kernel::loadEnv()** - Carga .env
   ↓
4. **Kernel::initializeServices()**
   - **Database::init()** - Inicializa conexión PDO
   - **JwtService::init()** - Inicializa JWT
   - **Mail::init()** - Inicializa PHPMailer
   ↓
5. **Kernel::loadRoutes()** - Carga rutas de routes/
   ↓
6. **Router::dispatch()** - Despacha petición

### Flujo de Autenticación Magic Link

#### Solicitud de Magic Link

1. **Frontend**: LoginPage.jsx
   - Usuario ingresa email
   - Llama `authAPI.requestMagicLink(email)`
   ↓
2. **Frontend**: services/api.js
   - Axios POST `/api/v1/auth/magic-link`
   - Agrega headers si existe token
   ↓
3. **Backend**: Router::dispatch()
   - Enruta a AuthController::requestMagicLink()
   ↓
4. **Backend**: AuthController::requestMagicLink()
   - Valida email
   - Llama AuthService::requestMagicLink()
   ↓
5. **Backend**: AuthService::requestMagicLink()
   - User::findByEmail() - Busca usuario
   - JwtService::generateMagicLinkToken() - Genera token
   - JwtService::hashToken() - Hashea token
   - AuthToken::create() - Almacena en DB
   - Mail::sendMagicLink() - Envía correo
   ↓
6. **Backend**: Mail::sendMagicLink()
   - PHPMailer envía correo HTML
   - Usa banner de public/gacela-access-banner.png

#### Verificación de Magic Link

1. **Frontend**: Usuario hace clic en enlace
   - Navega a `/auth/verify?token={TOKEN}`
   ↓
2. **Frontend**: VerifyPage.jsx
   - Extrae token de URL
   - Llama `authAPI.verifyToken(token)`
   ↓
3. **Frontend**: services/api.js
   - Axios POST `/api/v1/auth/verify-token`
   ↓
4. **Backend**: Router::dispatch()
   - Enruta a AuthController::verifyToken()
   ↓
5. **Backend**: AuthController::verifyToken()
   - Llama AuthService::verifyToken()
   ↓
6. **Backend**: AuthService::verifyToken()
   - JwtService::hashToken() - Hashea token
   - AuthToken::findByTokenHash() - Busca en DB
   - AuthToken::markAsUsed() - Marca como usado
   - User::findById() - Obtiene usuario
   - User::getUserPermissions() - Obtiene permisos
   - User::getOrganizationData() - Obtiene datos org
   - JwtService::encode() - Genera JWT
   ↓
7. **Frontend**: VerifyPage.jsx
   - Recibe token JWT y datos usuario
   - Llama `login(token, userData)`
   ↓
8. **Frontend**: AuthContext::login()
   - Almacena en localStorage
   - Actualiza estado
   - Redirige a /dashboard

### Flujo de Petición API Autenticada

1. **Frontend**: Cualquier página protegida
   - Usa hook useAuth()
   ↓
2. **Frontend**: services/api.js
   - Request interceptor agrega `Authorization: Bearer {token}`
   ↓
3. **Backend**: Router::dispatch()
   - SetCorsHeaders() - Headers CORS
   - Enruta a controlador
   ↓
4. **Backend**: Controlador
   - Procesa petición
   - Llama Response::json/success/error()
   ↓
5. **Frontend**: services/api.js
   - Response interceptor maneja 401
   - Limpia localStorage y redirige a /login

### Conexiones de Base de Datos

**User Model**:
- Conecta con: GCP_Usuarios, GCP_Agencias, GCP_Cargos, GCP_Areas, GCP_Roles, GCP_Permisos, GCP_Empresas
- Joins: LEFT JOIN para roles y empresas
- Utiliza: Database::getConnection()

**AuthToken Model**:
- Conecta con: GCP_AuthTokens, GCP_Usuarios
- Foreign Key: UsuarioId -> GCP_Usuarios(Id) ON DELETE CASCADE
- Utiliza: Database::getConnection()

### Conexiones de Frontend

**Router**:
- Utiliza: AuthContext (useAuth)
- Utiliza: MainLayout, Sidebar
- Utiliza: Módulos (auth, dashboard, intranet)

**AuthContext**:
- Utiliza: localStorage
- Utiliza: services/api.js (authAPI)
- Proporciona: useAuth hook

**MainLayout**:
- Utiliza: DashboardHeader
- Utiliza: Sidebar
- Utiliza: Outlet (react-router-dom)

**Sidebar**:
- Utiliza: react-router-dom (Link, useLocation)
- Utiliza: lucide-react (iconos)
- Datos: Array de menuItems hardcoded

---

## Flujo de Autenticación Detallado

### 1. Solicitud de Magic Link

**Paso 1**: Usuario ingresa email en LoginPage
- Frontend: `LoginPage.jsx` → `handleSubmit()`
- Validación: Email requerido, formato válido
- Captura: IP y User Agent

**Paso 2**: Petición a API
- Frontend: `services/api.js` → `authAPI.requestMagicLink(email)`
- Axios POST: `/api/v1/auth/magic-link`
- Body: `{ "email": "usuario@empresa.com" }`

**Paso 3**: Backend procesa solicitud
- Router: Enruta a `AuthController::requestMagicLink()`
- Controller: Valida email, llama `AuthService::requestMagicLink()`

**Paso 4**: AuthService genera token
- Model: `User::findByEmail(email)` - Busca usuario activo
- Si no existe: Retorna mensaje genérico (prevención enumeración)
- JWT: `JwtService::generateMagicLinkToken(userId)` - Genera token
- Hash: `JwtService::hashToken(token)` - SHA-256
- Expiración: 15 minutos (900 segundos)
- Model: `AuthToken::create()` - Almacena hash en DB

**Paso 5**: Envío de correo
- URL: `{APP_URL}/auth/verify?token={TOKEN}`
- Mail: `Mail::sendMagicLink(toEmail, userName, magicLink)`
- PHPMailer: Configura SMTP, envía HTML corporativo
- Banner: Imagen embebida desde `public/gacela-access-banner.png`

**Paso 6**: Respuesta al frontend
- Response: Mensaje genérico "Si tu correo está registrado..."
- Frontend: Muestra mensaje de éxito

### 2. Verificación de Token

**Paso 1**: Usuario hace clic en enlace
- URL: `/auth/verify?token={TOKEN}`
- Frontend: `VerifyPage.jsx` se monta

**Paso 2**: Frontend verifica token
- React: `useEffect()` ejecuta `verifyToken()`
- Extract: `searchParams.get('token')`
- API: `authAPI.verifyToken(token)`
- Prevención: `useRef(hasRequested)` para StrictMode

**Paso 3**: Backend valida token
- Router: Enruta a `AuthController::verifyToken()`
- Controller: Llama `AuthService::verifyToken()`
- Hash: `JwtService::hashToken(token)` - Compara con DB
- Model: `AuthToken::findByTokenHash()` - Busca token no usado y no expirado

**Paso 4**: Si token válido
- Model: `AuthToken::markAsUsed()` - Marca como consumido
- Model: `User::findById()` - Obtiene datos completos
- Model: `User::getUserPermissions()` - Obtiene permisos RBAC
- Model: `User::getOrganizationData()` - Obtiene estructura org
- JWT: `JwtService::encode(payload)` - Genera token de sesión
  - Payload: sub, email, roles, permissions
  - Expiración: 12 horas (43200 segundos)

**Paso 5**: Respuesta al frontend
- Response: `{ success: true, data: { token, user } }`
- User: id, nombres, apellidos, email, telefono, roles, permissions, organization

**Paso 6**: Frontend establece sesión
- Context: `AuthContext::login(token, userData)`
- Storage: localStorage.setItem('auth_token', token)
- Storage: localStorage.setItem('user_data', JSON.stringify(userData))
- State: setUser(userData), setIsAuthenticated(true)
- Redirect: `navigate('/dashboard')` después de 1.5s

### 3. Sesión Activa

**Almacenamiento**:
- Frontend: localStorage (auth_token, user_data)
- Backend: JWT en header Authorization

**Validación**:
- Frontend: `AuthContext::checkAuth()` al montar
- Backend: Middleware JWT (no implementado aún)
- API: Response interceptor maneja 401

**Expiración**:
- Magic Link: 15 minutos
- JWT: 12 horas

**Cierre de sesión**:
- Frontend: `AuthContext::logout()`
- Storage: localStorage.removeItem()
- State: setUser(null), setIsAuthenticated(false)
- API: POST `/api/v1/auth/logout` (client-side token removal)

---

## Resumen de Estado del Proyecto

### Funcionalidades Implementadas
✅ Autenticación con Magic Links
✅ Generación y validación JWT
✅ Envío de correos HTML corporativos
✅ Modelo de usuarios con relaciones organizacionales
✅ Modelo de tokens de autenticación
✅ Servicio de autenticación completo
✅ Router con rutas públicas y protegidas
✅ Contexto de autenticación React
✅ Cliente Axios con interceptores
✅ Páginas de login y verificación
✅ Layout principal con sidebar
✅ Estructura de base de datos completa

### Funcionalidades Pendientes
❌ CRUD de usuarios
❌ CRUD de empresas
❌ CRUD de departamentos
❌ CRUD de cargos
❌ CRUD de roles
❌ CRUD de menús
❌ Middleware de autenticación
❌ Middleware de validación JWT
❌ Middleware de permisos RBAC
❌ Middleware de auditoría
❌ Sistema de notificaciones
❌ Scheduler para tareas programadas
❌ Comandos de consola
❌ Triggers de auditoría automática
❌ Vistas para reportes
❌ Seeds para datos de prueba

### Archivos Redundantes (Sugerencia: Eliminar)
- app/Middleware/CorsMiddleware.php (duplicado con Router)
- app/Mail/MagicLinkMail.php (duplicado con Mail)
- app/Mail/MailService.php (duplicado con Mail)
- src/services/http.js (duplicado con api.js)
- src/services/auth.service.js (duplicado con api.js)
- src/hooks/useAuth.js (duplicado con AuthContext)
- src/App.jsx (vacío, retorna null)

### Directorios Vacíos (Sugerencia: Implementar o Eliminar)
- app/Mail/Providers/
- app/Mail/Templates/
- app/Console/Commands/
- database/procedures/
- database/functions/
- database/triggers/
- database/views/
- database/seeds/
- database/backups/

---

## Conclusión

El proyecto **Gacela Conecta Beta_V2** es una aplicación corporativa con arquitectura moderna desacoplada (React + PHP). Tiene una base sólida con autenticación por Magic Links completamente funcional, estructura de base de datos bien diseñada con jerarquía organizacional y RBAC, y frontend React con rutas protegidas.

**Puntos fuertes**:
- Arquitectura limpia y desacoplada
- Autenticación password-less segura
- Estructura de base de datos completa y escalable
- Frontend React moderno con hooks y contextos
- Sistema de rutas protegidas funcionando

**Áreas de mejora**:
- Implementar controladores CRUD para todas las entidades
- Implementar middleware para autenticación y permisos
- Implementar sistema de auditoría automático
- Eliminar archivos vacíos y redundantes
- Completar implementación de enums y helpers
- Implementar sistema de notificaciones
- Configurar backups automáticos de base de datos

**Recomendación prioritaria**:
1. Implementar middleware de JWT y permisos
2. Completar CRUDs de usuarios, roles y empresas
3. Implementar sistema de auditoría con triggers
4. Eliminar archivos redundantes
5. Documentar mejor los módulos del frontend

---

**Documento generado**: 2026-08-04
**Versión**: 1.0
**Autor**: Cascade AI Assistant
**Proyecto**: Gacela Conecta Beta_V2
