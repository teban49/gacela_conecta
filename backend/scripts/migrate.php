<?php

// Script de migración de base de datos
// Cargar configuración
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'gacela_conecta_beta',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];

try {
    // Conectar a MySQL (sin seleccionar base de datos)
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Base de datos '{$config['database']}' creada/verificada\n";
    
    // Seleccionar base de datos
    $pdo->exec("USE `{$config['database']}`");
    
    // Ejecutar migraciones
    $migrationsDir = __DIR__ . '/../database/migrations';
    $migrationFiles = glob($migrationsDir . '/*.sql');
    sort($migrationFiles);
    
    foreach ($migrationFiles as $file) {
        $sql = file_get_contents($file);
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignorar errores de tabla ya existente
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "✗ Error en " . basename($file) . ": " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✓ Ejecutado: " . basename($file) . "\n";
    }
    
    echo "\n✓ Migraciones completadas exitosamente\n";
    
} catch (PDOException $e) {
    echo "✗ Error de conexión: " . $e->getMessage() . "\n";
    echo "✗ Asegúrate de que PHP tenga el driver MySQL instalado (php_mysql / pdo_mysql)\n";
    exit(1);
}
