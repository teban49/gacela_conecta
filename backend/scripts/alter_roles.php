<?php

// Script para alterar tabla gcp_roles
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
    
    echo "Agregando columnas a gcp_roles...\n";
    
    // Verificar si la columna Descripcion existe
    $stmt = $pdo->query("SHOW COLUMNS FROM gcp_roles LIKE 'Descripcion'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE gcp_roles ADD COLUMN Descripcion TEXT AFTER Codigo");
        echo "✓ Columna Descripcion agregada\n";
    } else {
        echo "✓ Columna Descripcion ya existe\n";
    }
    
    // Verificar si la columna NivelAcceso existe
    $stmt = $pdo->query("SHOW COLUMNS FROM gcp_roles LIKE 'NivelAcceso'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE gcp_roles ADD COLUMN NivelAcceso INT DEFAULT 1 AFTER Descripcion");
        echo "✓ Columna NivelAcceso agregada\n";
    } else {
        echo "✓ Columna NivelAcceso ya existe\n";
    }
    
    echo "\n✓ Estructura actualizada\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
