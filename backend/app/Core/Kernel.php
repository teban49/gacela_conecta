<?php

namespace App\Core;

use Dotenv\Dotenv;

class Kernel
{
    public static function bootstrap(): void
    {
        

        // 1. Cargar constantes globales (incluye USER_STATE_ACTIVE, etc.)
        $constantsFile = dirname(__DIR__, 2) . '/config/constants.php';
        if (file_exists($constantsFile)) {
            require_once $constantsFile;
        }

        // 2. Cargar variables de entorno con phpdotenv
        self::loadEnv();

        // 3. Inicializar servicios centrales (Database, Jwt, Mail)
        self::initializeServices();

        // 4. Cargar rutas
        self::loadRoutes();

        // 5. Despachar petición
        Router::dispatch();
    }

    private static function loadEnv(): void
    {
        $baseDir = __DIR__ . '/../../';
        if (file_exists($baseDir . '.env')) {
            $dotenv = Dotenv::createImmutable($baseDir);
            $dotenv->safeLoad();
        }
    }

    private static function initializeServices(): void
    {
        // Inicializar Database pasando la configuración
        $dbConfigFile = __DIR__ . '/../../config/database.php';
        $dbConfig = file_exists($dbConfigFile) ? require $dbConfigFile : [];
        Database::init($dbConfig);

        // Inicializar JWT
        $jwtConfigFile = __DIR__ . '/../../config/jwt.php';
        $jwtConfig = file_exists($jwtConfigFile) ? require $jwtConfigFile : [];
        JwtService::init($jwtConfig);

        // Inicializar Mail
        $mailConfigFile = __DIR__ . '/../../config/mail.php';
        $mailConfig = file_exists($mailConfigFile) ? require $mailConfigFile : [];
        if (class_exists('App\Core\Mail') && method_exists('App\Core\Mail', 'init')) {
            Mail::init($mailConfig);
        }
    }

    private static function loadRoutes(): void
    {
        $routesDir = __DIR__ . '/../../routes';
        $routeFiles = glob($routesDir . '/*.php');

        if ($routeFiles) {
            foreach ($routeFiles as $routeFile) {
                require_once $routeFile;
            }
        }
    }
}