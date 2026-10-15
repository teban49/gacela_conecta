<?php

// Script para alterar tabla gcp_permisos
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
    
    echo "Agregando columnas a gcp_permisos...\n";
    
    // Verificar si la columna Modulo existe
    $stmt = $pdo->query("SHOW COLUMNS FROM gcp_permisos LIKE 'Modulo'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE gcp_permisos ADD COLUMN Modulo VARCHAR(100) NOT NULL AFTER AplicacionId");
        echo "✓ Columna Modulo agregada\n";
    } else {
        echo "✓ Columna Modulo ya existe\n";
    }
    
    // Verificar si la columna Descripcion existe
    $stmt = $pdo->query("SHOW COLUMNS FROM gcp_permisos LIKE 'Descripcion'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE gcp_permisos ADD COLUMN Descripcion TEXT AFTER Accion");
        echo "✓ Columna Descripcion agregada\n";
    } else {
        echo "✓ Columna Descripcion ya existe\n";
    }
    
    // Hacer AplicacionId NOT NULL
    $pdo->exec("ALTER TABLE gcp_permisos MODIFY COLUMN AplicacionId INT NOT NULL");
    echo "✓ AplicacionId ahora es NOT NULL\n";
    
    echo "\n✓ Estructura actualizada\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
