<?php

// Script para verificar roles y permisos
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
    
    echo "Roles en la base de datos:\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $pdo->query("SELECT Id, Nombre, Codigo, NivelAcceso, Estado FROM gcp_roles ORDER BY Id");
    $roles = $stmt->fetchAll();
    
    if (empty($roles)) {
        echo "No hay roles en la base de datos.\n";
    } else {
        foreach ($roles as $role) {
            echo sprintf(
                "ID: %d | %s (%s) | Nivel: %d | Estado: %s\n",
                $role['Id'],
                $role['Nombre'],
                $role['Codigo'],
                $role['NivelAcceso'],
                $role['Estado'] ? 'Activo' : 'Inactivo'
            );
        }
    }
    
    echo str_repeat("-", 80) . "\n";
    
    // Verificar permisos del rol Administrador
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM gcp_rolespermisos WHERE RolId = 1");
    $count = $stmt->fetch();
    echo "Permisos asignados al rol Administrador: {$count['total']}\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM gcp_permisos");
    $totalPerms = $stmt->fetch();
    echo "Total de permisos en el sistema: {$totalPerms['total']}\n";
    
    // Verificar roles asignados directamente a usuarios
    echo str_repeat("-", 80) . "\n";
    $stmt = $pdo->query("
        SELECT u.Id AS UsuarioId, u.RolId, r.Nombre, r.Codigo
        FROM gcp_usuarios u
        LEFT JOIN gcp_roles r ON r.Id = u.RolId
        ORDER BY u.Id
    ");
    $userRoles = $stmt->fetchAll();
    echo "Roles de usuarios:\n";
    if (empty($userRoles)) {
        echo "No hay asignaciones.\n";
    } else {
        foreach ($userRoles as $ur) {
            echo "Usuario ID: {$ur['UsuarioId']} -> Rol ID: {$ur['RolId']} ({$ur['Nombre']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
