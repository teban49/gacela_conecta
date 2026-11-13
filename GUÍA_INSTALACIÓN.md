# Guía de instalación

Guía para preparar el entorno actual de Gacela Conecta. Para detalles funcionales y configuración de variables, consulta también [README.md](README.md).

## Requisitos

- PHP y Composer compatibles con el proyecto.
- MySQL compatible con la configuración del backend.
- Node.js y npm.
- Acceso a los archivos de medios de la Intranet, si se requiere mostrar sus documentos.

## Preparar el backend

1. Crea una base de datos MySQL vacía y configura sus credenciales en `backend/.env`, tomando como referencia `backend/.env.example`.
2. Instala las dependencias desde la carpeta `backend`:

   ```powershell
   cd backend
   composer install
   cd ..
   ```

3. Ejecuta las migraciones registradas por el proyecto desde la raíz:

   ```powershell
   php backend/scripts/run_migrations.php
   ```

   El runner administra el orden y el registro de migraciones. No ejecutes manualmente migraciones individuales ya registradas.

4. Inicia el backend desde una terminal:

   ```powershell
   cd backend
   php -S 0.0.0.0:8002 -t public router.php
   ```

## Preparar el frontend

1. Configura `frontend/.env` a partir de `frontend/.env.example`, si aplica.
2. Instala dependencias e inicia el servidor local:

   ```powershell
   cd frontend
   npm install
   npm run dev
   ```

3. Para verificar la compilación de producción:

   ```powershell
   npm run build
   ```

## Acceso de usuarios

- Las cuentas con correo corporativo pueden usar el flujo de Magic Link habilitado en la aplicación.
- Las cuentas sin correo corporativo inician sesión con su cédula normalizada a dígitos como usuario y su contraseña.
- No cambies contraseñas ni ejecutes migraciones de datos manualmente durante la instalación.

## Archivos de la Intranet

Los documentos y otros medios usados por la Intranet deben estar disponibles bajo `frontend/public/media/intranet`. El `.gitignore` excluye los archivos de video pesados de las píldoras; conserva el catálogo `videos.json`. Asegúrate de provisionar esos medios por el mecanismo aprobado para el entorno.

## Si falla la instalación

- Confirma que las credenciales de base de datos en `backend/.env` sean válidas y que la base de datos exista.
- Revisa la salida de `php backend/scripts/run_migrations.php` y los logs del backend antes de volver a ejecutar una migración.
- Si faltan dependencias de frontend o backend, ejecuta `npm install` o `composer install` en su carpeta correspondiente.
- Consulta [README.md](README.md) para los comandos y requisitos específicos vigentes.
