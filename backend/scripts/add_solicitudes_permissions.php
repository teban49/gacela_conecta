<?php

// Script para agregar permisos del módulo SOLICITUDES
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Agregando permisos del módulo SOLICITUDES...\n";

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
];

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Conexión exitosa a la base de datos\n";
    
    // Obtener ID de la aplicación Solicitudes
    $stmt = $pdo->prepare("SELECT Id FROM gcp_aplicaciones WHERE Codigo = 'solicitudes'");
    $stmt->execute();
    $app = $stmt->fetch();
    
    if (!$app) {
        echo "Error: No se encontró la aplicación Solicitudes\n";
        exit(1);
    }
    
    $applicacionId = $app['Id'];
    echo "ID de aplicación Solicitudes: {$applicacionId}\n";
    
    // Permisos del módulo SOLICITUDES
    $solicitudesPermisos = [
        [$applicacionId, 'solicitudes', 'Ver', 'Ver módulo de solicitudes', 'solicitudes.ver'],
        [$applicacionId, 'solicitudes', 'Crear', 'Crear nuevas solicitudes', 'solicitudes.crear'],
        [$applicacionId, 'solicitudes', 'Administrar', 'Administrar solicitudes completas', 'solicitudes.administrar'],
        [$applicacionId, 'solicitudes', 'Administrar Categorías', 'Administrar categorías de solicitudes', 'solicitudes.administrar_categorias'],
    ];
    
    foreach ($solicitudesPermisos as $perm) {
        $sql = "INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($perm);
    }
    echo "✓ Permisos SOLICITUDES insertados\n";
    
    // Asignar todos los permisos al rol Administrador
    echo "Asignando permisos SOLICITUDES al rol Administrador...\n";
    $sql = "INSERT INTO gcp_rolespermisos (RolId, PermisoId) SELECT 1, Id FROM gcp_permisos WHERE AplicacionId = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$applicacionId]);
    echo "✓ Permisos SOLICITUDES asignados al rol Administrador\n";
    
    echo "\n✓ Permisos SOLICITUDES completados\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}