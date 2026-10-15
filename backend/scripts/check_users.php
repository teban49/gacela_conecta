<?php

// Script para verificar usuarios existentes
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
    
    echo "Usuarios en la base de datos:\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $pdo->query("SELECT Id, Nombres, Apellidos, EmailCorporativo, Estado FROM gcp_usuarios ORDER BY Id");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No hay usuarios en la base de datos.\n";
    } else {
        foreach ($users as $user) {
            echo sprintf(
                "ID: %d | %s %s | %s | Estado: %s\n",
                $user['Id'],
                $user['Nombres'],
                $user['Apellidos'],
                $user['EmailCorporativo'],
                $user['Estado'] ? 'Activo' : 'Inactivo'
            );
        }
    }
    
    echo str_repeat("-", 80) . "\n";
    
    // Verificar usuarios específicos
    $checkIds = [1, 2];
    foreach ($checkIds as $id) {
        $stmt = $pdo->prepare("SELECT * FROM gcp_usuarios WHERE Id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "✓ Usuario ID {$id} existe: {$user['Nombres']} {$user['Apellidos']}\n";
            
            // Verificar roles del usuario
            $stmt = $pdo->prepare("
                SELECT r.Id, r.Nombre, r.Codigo
                FROM gcp_usuarios u
                INNER JOIN gcp_roles r ON r.Id = u.RolId
                WHERE u.Id = ?
            ");
            $stmt->execute([$id]);
            $roles = $stmt->fetchAll();
            
            if (!empty($roles)) {
                echo "  Roles: " . implode(', ', array_column($roles, 'Nombre')) . "\n";
            } else {
                echo "  Sin roles asignados\n";
            }
        } else {
            echo "✗ Usuario ID {$id} NO existe\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
