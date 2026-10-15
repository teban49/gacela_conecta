<?php

// Script para agregar permisos del módulo GESTIÓN TECNOLÓGICA
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Agregando permisos del módulo GESTIÓN TECNOLÓGICA...\n";

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
    
    // Obtener ID de la aplicación Gestión Tecnológica
    $stmt = $pdo->prepare("SELECT Id FROM gcp_aplicaciones WHERE Codigo = 'inventario'");
    $stmt->execute();
    $app = $stmt->fetch();
    
    if (!$app) {
        echo "Error: No se encontró la aplicación Gestión Tecnológica (inventario)\n";
        exit(1);
    }
    
    $applicacionId = $app['Id'];
    echo "ID de aplicación Gestión Tecnológica: {$applicacionId}\n";
    
    // Permisos del módulo GESTIÓN TECNOLÓGICA
    $gestionPermisos = [
        [$applicacionId, 'gestion', 'Ver', 'Ver módulo de gestión tecnológica', 'gestion.ver'],
        [$applicacionId, 'gestion', 'Administrar Activos', 'Administrar activos tecnológicos', 'gestion.administrar_activos'],
        [$applicacionId, 'gestion', 'Administrar Mantenimiento', 'Administrar mantenimientos', 'gestion.administrar_mantenimiento'],
        [$applicacionId, 'gestion', 'Administrar Incidencias', 'Administrar incidencias tecnológicas', 'gestion.administrar_incidencias'],
        [$applicacionId, 'gestion', 'Administrar Licencias', 'Administrar licencias de software', 'gestion.administrar_licencias'],
    ];
    
    foreach ($gestionPermisos as $perm) {
        $sql = "INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($perm);
    }
    echo "✓ Permisos GESTIÓN TECNOLÓGICA insertados\n";
    
    // Asignar todos los permisos al rol Administrador
    echo "Asignando permisos GESTIÓN TECNOLÓGICA al rol Administrador...\n";
    $sql = "INSERT INTO gcp_rolespermisos (RolId, PermisoId) SELECT 1, Id FROM gcp_permisos WHERE AplicacionId = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$applicacionId]);
    echo "✓ Permisos GESTIÓN TECNOLÓGICA asignados al rol Administrador\n";
    
    echo "\n✓ Permisos GESTIÓN TECNOLÓGICA completados\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}