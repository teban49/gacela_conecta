<?php

// Script de seed de base de datos
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
    // Conectar a la base de datos
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ Conectado a la base de datos '{$config['database']}'\n";
    
    // Ejecutar seeds
    $seedsDir = __DIR__ . '/../database/seeds';
    $seedFiles = glob($seedsDir . '/*.sql');
    sort($seedFiles);
    
    foreach ($seedFiles as $file) {
        echo "\nEjecutando: " . basename($file) . "\n";
        $sql = file_get_contents($file);
        
        // Dividir por statements individuales para mejor manejo de errores
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    echo "✗ Error en statement: " . substr($statement, 0, 100) . "... : " . $e->getMessage() . "\n";
                }
            }
        }
        echo "✓ Ejecutado: " . basename($file) . "\n";
    }
    
    echo "\n✓ Seeds completados exitosamente\n";
    
} catch (PDOException $e) {
    echo "✗ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}
