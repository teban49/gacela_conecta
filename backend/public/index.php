<?php
// Permitir solicitudes desde Vite (o cualquier origen en desarrollo)
header("Access-Control-Allow-Origin: http://192.168.10.156:5176"); // O usa '*' en desarrollo
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables del archivo .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

// Configuración de cabeceras CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cargar las rutas de la API
require_once __DIR__ . '/../routes/api.php';

use App\Core\Kernel;

// Bootstrap the application
Kernel::bootstrap();
