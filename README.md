# Gacela Conecta

Plataforma corporativa del Grupo Gacela implementada según las especificaciones SADD v1.0.

## Arquitectura General

### Gacela Core Platform (GCP)

El núcleo de la solución sigue el principio **GCP FIRST**, donde el Frontend (React + Vite) y el Backend (PHP) están 100% desacoplados, comunicándose exclusivamente mediante JSON a través de una API REST.

### Servicios Transversales GCP

1. **Authentication Engine** - Magic Links / Passwordless
2. **Authorization Engine** - RBAC (Permisos por Rol/Cargo)
3. **Organization Engine** - Grupo > Empresa > Ciudad > Agencia > Área > Cargo > Usuario
4. **Audit Engine** - Registro de bitácora automático
5. **Files Engine** - Gestión centralizada de adjuntos
6. **Notification Engine** - Envío de correos y alertas

## Tech Stack

### Backend
- **PHP 8.2+**
- **API RESTful**
- **MySQL**
- **Firebase JWT** para autenticación
- **PHPMailer** para envío de correos

### Frontend
- **React 18.3+**
- **Vite**
- **React Router DOM**
- **Axios** para comunicación API
- **Tailwind CSS** para estilos
- **Lucide React** para iconos

## Instalación

### Prerrequisitos
- PHP 8.2 o superior
- MySQL 8.0 o superior
- Node.js 18+ y npm
- Composer

### Backend Setup

1. **Instalar dependencias:**
```bash
cd backend
composer install
```

2. **Configurar variables de entorno:**
```bash
cp .env.example .env
```

Editar `.env` con tus credenciales:
```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:5173

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=gacela_conecta
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=tu-secret-key-seguro
JWT_EXPIRATION=43200

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_FROM_EMAIL=noreply@grupogacela.com.co
MAIL_FROM_NAME=Gacela Conecta
```

3. **Ejecutar migraciones de base de datos:**
```bash
# Ejecutar los archivos SQL en database/migrations/
mysql -u root -p gacela_conecta < database/migrations/001_create_gcp_usuarios_table.sql
mysql -u root -p gacela_conecta < database/migrations/002_create_gcp_authtokens_table.sql
mysql -u root -p gacela_conecta < database/migrations/003_create_gcp_organizaciones_table.sql
mysql -u root -p gacela_conecta < database/migrations/004_create_gcp_rbac_tables.sql
mysql -u root -p gacela_conecta < database/migrations/005_create_gcp_audit_table.sql
```
Para actualizar una instalación existente, ejecuta `php backend/scripts/run_migrations.php`. Las migraciones 020 y 021 incorporan las solicitudes de asignación, los estados de ticket `nueva`, `pendiente` y `cerrada`, y los permisos de incidencias para los roles de TI. La migración 020 crea también un activo de demostración para validar los mantenimientos semestrales.

4. **Iniciar servidor PHP:**
```bash
php -S localhost:8001 -t public
```

### Frontend Setup

1. **Instalar dependencias:**
```bash
cd frontend
npm install
```

2. **Configurar variables de entorno:**
```bash
cp .env.example .env
```

El archivo `.env` ya está configurado por defecto:
```env
VITE_API_URL=http://localhost:8001
```

3. **Iniciar servidor de desarrollo:**
```bash
npm run dev
```

La aplicación estará disponible en `http://localhost:5173`

## Flujo de Autenticación (Magic Links)

### 1. Solicitud de Magic Link
- **Frontend:** Usuario ingresa correo corporativo en `/login`
- **Backend:** POST `/api/v1/auth/magic-link`
- **Proceso:**
  - Valida que el usuario exista y esté activo
  - Genera token criptográfico único (SHA-256)
  - Almacena token en `GCP_AuthTokens` con expiración de 15 minutos
  - Envía correo HTML corporativo con el enlace

### 2. Verificación de Token
- **Frontend:** Usuario hace clic en enlace → `/auth/verify?token={TOKEN}`
- **Backend:** POST `/api/v1/auth/verify-token`
- **Proceso:**
  - Valida token (existencia, no expirado, no usado)
  - Marca token como consumido
  - Genera JWT Bearer Token (12 horas de validez)
  - Retorna datos del usuario, roles y permisos

### 3. Sesión Activa
- **Frontend:** Almacena Bearer Token en `localStorage`
- **Backend:** Valida token en cada petición protegida
- **Expiración:** 12 horas (configurable)

## Estructura de Base de Datos

### Tablas Principales GCP

- **GCP_Usuarios** - Usuarios del sistema
- **GCP_AuthTokens** - Tokens de autenticación
- **GCP_Grupos** - Grupos organizacionales
- **GCP_Empresas** - Empresas
- **GCP_Ciudades** - Ciudades
- **GCP_Agencias** - Agencias
- **GCP_Areas** - Áreas
- **GCP_Cargos** - Cargos
- **GCP_Roles** - Roles del sistema
- **GCP_Permisos** - Permisos
- **GCP_RolesPermisos** - Relación roles-permisos
- **GCP_UsuariosRoles** - Relación usuarios-roles
- **GCP_AuditLog** - Bitácora de auditoría

## API Endpoints

### Autenticación

#### POST `/api/v1/auth/magic-link`
Solicita un magic link para autenticación.

**Request:**
```json
{
  "email": "usuario@empresa.com"
}
```

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "Si tu correo está registrado, recibirás un enlace de acceso en tu bandeja de entrada.",
  "errors": []
}
```

#### POST `/api/v1/auth/verify-token`
Verifica un magic link y crea sesión.

**Request:**
```json
{
  "token": "token-magic-link"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "jwt-bearer-token",
    "user": {
      "id": 1,
      "nombres": "Juan",
      "apellidos": "Pérez",
      "email": "juan@empresa.com",
      "roles": [1, 2],
      "permissions": ["usuarios.crear", "usuarios.leer"],
      "organization": {
        "GrupoNombre": "Grupo Gacela",
        "EmpresaNombre": "Empresa XYZ",
        "CiudadNombre": "Bogotá",
        "AgenciaNombre": "Agencia Centro",
        "AreaNombre": "Tecnología",
        "CargoNombre": "Desarrollador"
      }
    }
  },
  "message": "Autenticación exitosa.",
  "errors": []
}
```

#### GET `/api/v1/auth/me`
Obtiene el usuario autenticado actual.

**Headers:**
```
Authorization: Bearer {jwt-token}
```

#### POST `/api/v1/auth/logout`
Cierra la sesión del usuario.

## Seguridad

### Magic Links
- **Expiración:** 15 minutos
- **Uso único:** Se invalida tras consumirse
- **Hash SHA-256:** Tokens almacenados como hash
- **Prevención de enumeración:** Mensaje genérico para usuarios inexistentes

### JWT Tokens
- **Algoritmo:** HS256
- **Expiración:** 12 horas (configurable)
- **Payload:** ID usuario, email, roles, permisos

### CORS
- Configurado para permitir orígenes específicos en producción
- Headers permitidos: Content-Type, Authorization

## Desarrollo

### Backend Structure
```
backend/
├── app/
│   ├── Controllers/     # Controladores API
│   ├── Core/           # Clases base (Database, Response, JWT, etc.)
│   ├── Models/         # Modelos de datos
│   ├── Services/       # Lógica de negocio
│   └── Middleware/     # Middleware de autenticación/autorización
├── config/             # Archivos de configuración
├── routes/             # Definición de rutas
├── public/             # Punto de entrada (index.php)
└── vendor/             # Dependencias Composer
```

### Frontend Structure
```
frontend/
├── src/
│   ├── contexts/       # Contextos React (Auth, etc.)
│   ├── modules/        # Módulos de funcionalidad
│   ├── router/         # Configuración de rutas
│   ├── services/       # Servicios API
│   └── ui/             # Componentes UI reutilizables
├── public/             # Archivos estáticos
└── package.json        # Dependencias npm
```

## Scripts Disponibles

### Backend
```bash
# Iniciar servidor de desarrollo
php -S localhost:8001 -t public
```

### Frontend
```bash
# Instalar dependencias
npm install

# Iniciar servidor de desarrollo
npm run dev

# Build para producción
npm run build

# Preview de producción
npm run preview
```

## Notas Importantes

1. **GCP FIRST:** Todo módulo debe usar los servicios transversales de GCP. No duplicar lógica de permisos, auditoría o archivos.

2. **Passwordless:** Está estrictamente prohibido el uso de contraseñas tradicionales. Solo Magic Links.

3. **Desacoplamiento:** Frontend y Backend son completamente independientes. La comunicación es exclusivamente vía API REST.

4. **Auditoría:** Todas las acciones deben registrarse en `GCP_AuditLog`.

5. **Permisos:** El control de acceso se realiza mediante RBAC usando `GCP_Roles` y `GCP_Permisos`.

## Licencia

Proprietary - Grupo Gacela

## Soporte

Para soporte técnico, contacte al equipo de desarrollo del Grupo Gacela.
