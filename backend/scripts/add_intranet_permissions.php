<?php

// Script para agregar permisos del módulo INTRANET
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Agregando permisos del módulo INTRANET...\n";

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
    
    // Obtener ID de la aplicación Intranet
    $stmt = $pdo->prepare("SELECT Id FROM gcp_aplicaciones WHERE Codigo = 'intranet'");
    $stmt->execute();
    $app = $stmt->fetch();
    
    if (!$app) {
        echo "Error: No se encontró la aplicación Intranet\n";
        exit(1);
    }
    
    $applicacionId = $app['Id'];
    echo "ID de aplicación Intranet: {$applicacionId}\n";
    
    // Permisos del módulo INTRANET
    $intranetPermisos = [
        [$applicacionId, 'intranet', 'Ver', 'Ver módulo de intranet', 'intranet.ver'],
        [$applicacionId, 'intranet', 'Administrar Noticias', 'Administrar noticias y anuncios', 'intranet.administrar_noticias'],
        [$applicacionId, 'intranet', 'Administrar Documentos', 'Administrar documentos compartidos', 'intranet.administrar_documentos'],
        [$applicacionId, 'intranet', 'Administrar Eventos', 'Administrar calendario de eventos', 'intranet.administrar_eventos'],
        [$applicacionId, 'intranet', 'Administrar Directorio', 'Editar los contactos del directorio', 'intranet.administrar_directorio'],
        [$applicacionId, 'intranet', 'Administrar Foro', 'Administrar foro de discusión', 'intranet.administrar_foro'],
    ];
    
    foreach ($intranetPermisos as $perm) {
        $check = $pdo->prepare("SELECT Id FROM gcp_permisos WHERE CodigoUnico = ? LIMIT 1");
        $check->execute([$perm[4]]);
        if ($check->fetchColumn()) {
            continue;
        }
        $sql = "INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($perm);
    }
    echo "✓ Permisos INTRANET insertados\n";
    
    // Asignar todos los permisos al rol Administrador
    echo "Asignando permisos INTRANET al rol Administrador...\n";
    $sql = "INSERT INTO gcp_rolespermisos (RolId, PermisoId)
            SELECT 1, p.Id
            FROM gcp_permisos p
            WHERE p.AplicacionId = ?
              AND NOT EXISTS (
                  SELECT 1 FROM gcp_rolespermisos rp
                  WHERE rp.RolId = 1 AND rp.PermisoId = p.Id
              )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$applicacionId]);
    echo "✓ Permisos INTRANET asignados al rol Administrador\n";
    
    echo "\n✓ Permisos INTRANET completados\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}