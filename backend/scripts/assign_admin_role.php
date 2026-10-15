<?php

// Script para asignar rol Administrador a usuarios 1 y 2
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
    
    echo "Asignando rol Administrador a usuarios 1 y 2...\n";
    
    // Primero asegurar que el rol Administrador tenga todos los permisos
    $pdo->exec("
        INSERT IGNORE INTO gcp_rolespermisos (RolId, PermisoId)
        SELECT 1, Id FROM gcp_permisos
    ");
    echo "✓ Rol Administrador tiene todos los permisos\n";
    
    // Asignar rol Administrador (ID=1) al usuario ID=1
    $stmt = $pdo->prepare("UPDATE gcp_usuarios SET RolId = 1 WHERE Id = 1");
    $stmt->execute();
    echo "✓ Usuario ID 1 asignado al rol Administrador\n";
    
    // Asignar rol Administrador (ID=1) al usuario ID=2
    $stmt = $pdo->prepare("UPDATE gcp_usuarios SET RolId = 1 WHERE Id = 2");
    $stmt->execute();
    echo "✓ Usuario ID 2 asignado al rol Administrador\n";
    
    // Verificar asignación
    echo "\nVerificación:\n";
    foreach ([1, 2] as $userId) {
        $stmt = $pdo->prepare("
            SELECT r.Id, r.Nombre, r.Codigo
            FROM gcp_usuarios u
            INNER JOIN gcp_roles r ON r.Id = u.RolId
            WHERE u.Id = ?
        ");
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll();
        
        echo "Usuario ID {$userId}: " . implode(', ', array_column($roles, 'Nombre')) . "\n";
    }
    
    echo "\n✓ Proceso completado exitosamente\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
