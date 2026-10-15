<?php

// Script manual de seed
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Iniciando seed manual...\n";

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
    echo "Ejecutando seed manual...\n";
    
    // Desactivar restricciones
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "✓ FK checks desactivados\n";
    
    // Limpiar tablas
    $pdo->exec("TRUNCATE TABLE gcp_rolespermisos");
    $pdo->exec("TRUNCATE TABLE gcp_permisos");
    $pdo->exec("TRUNCATE TABLE gcp_roles");
    $pdo->exec("TRUNCATE TABLE gcp_aplicaciones");
    echo "✓ Tablas limpiadas\n";
    
    // Insertar aplicaciones
    echo "Insertando aplicaciones...\n";
    $sql = "INSERT INTO gcp_aplicaciones (Nombre, Codigo, Ruta, Color, Icono, OrdenMenu, Visible, Estado) VALUES
    ('Dashboard', 'dashboard', '/dashboard', '#6366f1', 'layout-dashboard', 1, 1, 1),
    ('Intranet', 'intranet', '/intranet', '#8b5cf6', 'newspaper', 2, 1, 1),
    ('Incidencias', 'incidencias', '/incidencias', '#ef4444', 'alert-triangle', 3, 1, 1),
    ('Solicitudes', 'solicitudes', '/solicitudes', '#f59e0b', 'file-text', 4, 1, 1),
    ('Inventario', 'inventario', '/inventario', '#10b981', 'package', 5, 1, 1),
    ('Fondo de Reposición', 'fondo_reposicion', '/fondo-reposicion', '#06b6d4', 'wallet', 6, 1, 1),
    ('Seguridad', 'seguridad', '/seguridad', '#ec4899', 'shield', 7, 1, 1),
    ('Configuración', 'configuracion', '/configuracion', '#64748b', 'settings', 8, 1, 1)";
    $pdo->exec($sql);
    echo "✓ Aplicaciones insertadas\n";
    
    // Insertar roles
    echo "Insertando roles...\n";
    $sql = "INSERT INTO gcp_roles (Nombre, Codigo, Descripcion, NivelAcceso, Estado) VALUES
    ('Administrador', 'admin', 'Acceso total al sistema', 10, 1),
    ('Analista TI', 'analista_ti', 'Soporte técnico y desarrollo', 5, 1),
    ('Supervisor TI', 'supervisor_ti', 'Supervisión de área TI', 7, 1),
    ('Gerencia', 'gerencia', 'Nivel gerencial', 8, 1),
    ('Recursos Humanos', 'rrhh', 'Gestión de personal', 6, 1),
    ('Operaciones', 'operaciones', 'Operaciones del negocio', 4, 1),
    ('Tesorería', 'tesoreria', 'Gestión financiera', 5, 1),
    ('Usuario Estándar', 'usuario', 'Acceso básico', 1, 1)";
    $pdo->exec($sql);
    echo "✓ Roles insertados\n";
    
    // Insertar permisos (permisos de seguridad y algunos ejemplos)
    echo "Insertando permisos...\n";
    
    // Permisos de Seguridad
    $seguridadPermisos = [
        [7, 'seguridad', 'Ver', 'Ver módulo de seguridad', 'seguridad.ver'],
        [7, 'seguridad', 'Administrar Aplicaciones', 'Administrar aplicaciones', 'seguridad.administrar_aplicaciones'],
        [7, 'seguridad', 'Administrar Permisos', 'Administrar permisos', 'seguridad.administrar_permisos'],
        [7, 'seguridad', 'Administrar Roles', 'Administrar roles', 'seguridad.administrar_roles'],
        [7, 'seguridad', 'Administrar Usuarios', 'Administrar usuarios', 'seguridad.administrar_usuarios'],
        [7, 'seguridad', 'Administrar', 'Administrar seguridad completa', 'seguridad.administrar'],
    ];
    
    // Permisos de Dashboard
    $dashboardPermisos = [
        [1, 'dashboard', 'Ver', 'Ver dashboard', 'dashboard.ver'],
        [1, 'dashboard', 'Administrar', 'Administrar dashboard', 'dashboard.administrar'],
    ];
    
    foreach (array_merge($seguridadPermisos, $dashboardPermisos) as $perm) {
        $sql = "INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($perm);
    }
    echo "✓ Permisos insertados\n";
    
    // Asignar todos los permisos al rol Administrador
    echo "Asignando permisos al rol Administrador...\n";
    $sql = "INSERT INTO gcp_rolespermisos (RolId, PermisoId) SELECT 1, Id FROM gcp_permisos";
    $pdo->exec($sql);
    echo "✓ Permisos asignados al rol Administrador\n";
    
    // Asignar rol Administrador a usuarios 1 y 2
    echo "Asignando rol Administrador a usuarios 1 y 2...\n";
    $sql = "UPDATE gcp_usuarios SET RolId = 1 WHERE Id IN (1, 2)";
    $pdo->exec($sql);
    echo "✓ Rol Administrador asignado a usuarios 1 y 2\n";
    
    // Reactivar restricciones
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ FK checks reactivados\n";
    
    // Verificar
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM gcp_aplicaciones");
    $apps = $stmt->fetch();
    echo "Aplicaciones: {$apps['total']}\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM gcp_roles");
    $roles = $stmt->fetch();
    echo "Roles: {$roles['total']}\n";
    
    echo "\n✓ Seed manual completado\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
