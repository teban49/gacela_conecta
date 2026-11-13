# Gacela Conecta

Plataforma corporativa del Grupo Gacela implementada según las especificaciones SADD v1.0.

## Arquitectura General

### Gacela Core Platform (GCP)

El núcleo de la solución sigue el principio **GCP FIRST**, donde el Frontend (React + Vite) y el Backend (PHP) están 100% desacoplados, comunicándose exclusivamente mediante JSON a través de una API REST.

### Servicios Transversales GCP

1. **Authentication Engine** - Magic Links para correo corporativo y usuario/contraseña para cuentas sin correo
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
Antes de ejecutar el runner, crea una base de datos vacía cuyo nombre coincida con `DB_DATABASE` en `backend/.env`.

```bash
cd ..
php backend/scripts/run_migrations.php
```
El runner aplica las migraciones registradas tanto en instalaciones nuevas como existentes. No ejecutes manualmente migraciones individuales ya registradas. Las migraciones 044 y 045 actualizan los SLA y el usuario de las cuentas sin correo a la cédula, respectivamente; las contraseñas existentes no se modifican.

4. **Iniciar servidor PHP:**
```bash
cd backend
php -S 0.0.0.0:8002 -t public router.php
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
VITE_BACKEND_URL=http://127.0.0.1:8002
```

3. **Iniciar servidor de desarrollo:**
```bash
npm run dev
```

En PowerShell de Windows, si `npm run dev` falla porque la política de ejecución bloquea `npm.ps1`, usa:
```powershell
npm.cmd run dev
```

La aplicación estará disponible en `http://localhost:5173`.

### Contenido estático de Intranet

Los archivos de capacitación y los catálogos que los muestran se mantienen en `frontend/public/media/intranet/`. Después de iniciar Vite, esos archivos se sirven desde `/media/intranet/`.

- **Píldoras GIS, PXP, FICS, Ofima y Frixo:** guarda los videos en `frontend/public/media/intranet/pildoras/<plataforma>/` y registra cada video en `frontend/public/media/intranet/pildoras/videos.json`. Cada elemento usa `titulo`, `archivo` y, opcionalmente, `descripcion`; `archivo` debe ser el nombre del video dentro de la carpeta de esa plataforma. Los videos grandes se excluyen de Git; el catálogo JSON sí se versiona y los videos deben copiarse al entorno de despliegue.
- **Links de aplicativos:** guarda las imágenes en `frontend/public/media/intranet/aplicativos/images/` y configura título, URL e imagen en `frontend/public/media/intranet/aplicativos/links.json`. Las imágenes actuales son `pxp_flota.png`, `pxp_expreso.png`, `pxp_rapido.png`, `fics.png`, `ams.png`, `pag_flota.png`, `micom.png`, `prosegur.png` y `sgt.png`.
- **Formatos:** se administran desde la pestaña Formatos de Intranet, por empresa y categoría (`gestion humana`, `compras`, `servicio al cliente` y `sistemas`). Los archivos y el catálogo se guardan en `frontend/public/media/intranet/formatos/`.
- **Documentación:** se administra desde la pestaña Documentación de Intranet, por empresa, en `frontend/public/media/intranet/documentacion/`.
- **Manuales técnicos:** se administran desde la pestaña Manuales Técnicos. Los archivos se guardan en `frontend/public/media/intranet/manuales/pdf/` y su catálogo en `manuales.json`.
- **Documentos privados de Calidad:** guarda los PDF por empresa y opción en `backend/storage/intranet/calidad/{EmpresaId}/{opcion}/`. Al consultar Calidad se crean las nueve carpetas de la empresa afiliada seleccionada. Usa `vinculaciones`, `mantenimiento`, `torre-control`, `calidad`, `tecnologia`, `gerencia`, `servicio-cliente`, `gestion-humana` y `compras-proveedores` como nombres de carpeta. El ID debe ser el de `gcp_empresas`; estos documentos se entregan desde un endpoint autenticado y no deben moverse a `frontend/public`.

Los catálogos JSON se actualizan automáticamente al agregar o borrar formatos, documentación o manuales desde Intranet; solo los administradores pueden realizar esas acciones. Los botones Ver abren los PDF en el visor flotante de Intranet. Los documentos de Calidad también se administran desde su pestaña y continúan protegidos por autenticación y permisos de empresa/área. Evita guardar archivos secretos en `public`, ya que todo su contenido es accesible desde el navegador.

## Gestión Tecnológica

- Los tickets siguen el flujo **Nuevo → Pendiente → Cerrado**; no hay una transición automática por tiempo.
- Los SLA se calculan por prioridad: baja, 1 mes; media, 21 días; alta, 14 días; crítica, 7 días. Los tickets abiertos vencidos se resaltan en rojo.
- Los activos y mantenimientos se pueden exportar a Excel con los filtros de búsqueda/agencia aplicados.
- Asociar activos a una licencia es opcional.

## Métodos de Autenticación

### Correo corporativo: Magic Link

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

### Usuarios sin correo corporativo

Estas cuentas inician sesión con `POST /api/v1/auth/login`, enviando `usuario` y `password`. El usuario es la cédula normalizada a dígitos; la migración actualiza las cuentas existentes sin cambiar sus contraseñas. Para una cuenta nueva sin correo, la contraseña inicial es la cédula. Los taquilleros conservan la configuración existente sin acceso directo.

## Estructura de Base de Datos

### Tablas Principales GCP

- **GCP_Usuarios** - Usuarios del sistema
- **GCP_AuthTokens** - Tokens de autenticación
- **GCP_Grupos** - Grupos organizacionales
- **GCP_Empresas** - Empresas
- **GCP_Ciudades** - Ciudades
- **GCP_Agencias** - Agencias
- **GCP_Areas** - Tabla del esquema inicial; la migración 031 la elimina.
- **GCP_Cargos** - Cargos
- **GCP_Roles** - Roles del sistema
- **GCP_Permisos** - Permisos
- **GCP_RolesPermisos** - Relación roles-permisos
- **GCP_UsuariosRoles** - Tabla RBAC antigua; la migración 018 la elimina y cada usuario conserva `RolId`.
- **GCP_AuditLog** - Bitácora de auditoría

## Alcance del dashboard de horario

- Administración y directivos consultan todas las marcaciones.
- Gerencia consulta únicamente las personas asociadas a sus empresas en `gcp_usuariosempresas`.
- RD solo puede consultarlo Diego Romero (cuenta registrada como Diego Romero Sanabria) y PDR solo Víctor Hugo Romero; esas cuentas quedan limitadas a su empresa aunque tengan otras empresas asociadas.
- Los jefes con rol departamental consultan las personas asignadas al mismo rol; un jefe con rol estándar consulta su equipo directo configurado mediante `CargoJefeDirectoId`.
- El área del jefe se limita a su área asignada y el selector no permite cambiarla.
- Las marcaciones cuyo nombre no se asocia de forma única con un usuario activo se excluyen de los informes limitados.

El filtro se aplica en el backend antes de calcular rangos, métricas y ausencias; no depende de filtros visuales del frontend.

## API Endpoints

### Autenticación

#### POST `/api/v1/auth/login`
Inicia sesión con usuario y contraseña para cuentas sin correo corporativo.

**Request:**
```json
{
  "usuario": "1234567890",
  "password": "contraseña"
}
```

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

### Cuentas sin correo corporativo
- Inician sesión con usuario y contraseña en `/api/v1/auth/login`.
- El usuario es la cédula normalizada a dígitos.
- El acceso está protegido contra intentos repetidos (5 intentos fallidos en 15 minutos).

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

2. **Autenticación:** Magic Link para correo corporativo; las cuentas sin correo usan cédula y contraseña.

3. **Desacoplamiento:** Frontend y Backend son completamente independientes. La comunicación es exclusivamente vía API REST.

4. **Auditoría:** Todas las acciones deben registrarse en `GCP_AuditLog`.

5. **Permisos:** El control de acceso se realiza mediante RBAC usando `GCP_Roles` y `GCP_Permisos`.

## Licencia

Proprietary - Grupo Gacela

## Soporte

Para soporte técnico, contacte al equipo de desarrollo del Grupo Gacela.
