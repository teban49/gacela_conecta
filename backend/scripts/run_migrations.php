<?php

// Script para ejecutar migraciones de base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Ejecutando migraciones de base de datos...\n";

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
    
    // Migraciones a ejecutar
    $migrations = [
        '009_create_intranet_tables.sql',
        '017_rename_solicitudes_to_gestion_tickets.sql',
        '010_create_solicitudes_tables.sql',
        '010_create_gcp_solicitudes_tables.sql',
        '013_seed_default_solicitud_category.sql',
        '011_create_gestion_tecnologica_tables.sql',
        '014_seed_default_asset_categories.sql',
        '015_hr_request_approvals_and_managers.sql',
        '016_hr_manager_cargo_and_incident_attachments.sql',
        '018_single_role_per_user.sql',
        '019_gestion_intranet_improvements.sql',
        '020_asset_assignment_acceptance_and_ticket_states.sql',
        '021_pending_ticket_confirmation.sql',
        '022_incident_satisfaction_survey.sql',
        '023_user_read_access.sql',
        '024_usuario_role_permissions.sql',
        '025_permissions_for_roles_4_to_8.sql'
    ];
    
    foreach ($migrations as $migrationFile) {
        $migrationPath = __DIR__ . '/../../database/migrations/' . $migrationFile;
        
        if (!file_exists($migrationPath)) {
            echo "⚠️  Archivo de migración no encontrado: {$migrationFile}\n";
            continue;
        }
        
        echo "Ejecutando migración: {$migrationFile}\n";
        
        $sql = file_get_contents($migrationPath);
        
        try {
            $pdo->exec($sql);
            echo "✓ Migración {$migrationFile} completada\n";
        } catch (PDOException $e) {
            echo "✗ Error en migración {$migrationFile}: " . $e->getMessage() . "\n";
            // Continuar con las demás migraciones aunque una falle
        }
    }
    
    echo "\n✓ Proceso de migraciones finalizado\n";
    
} catch (PDOException $e) {
    echo "✗ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}