<?php

// Script para configurar permisos del Core del sistema
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Configurando permisos del Core del sistema...\n";

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
    
    // Verificar y agregar columna Visible si no existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM gcp_aplicaciones LIKE 'Visible'");
    $stmt->execute();
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "Agregando columna Visible a gcp_aplicaciones...\n";
        $sql = "ALTER TABLE gcp_aplicaciones ADD COLUMN Visible TINYINT NOT NULL DEFAULT 1 AFTER OrdenMenu";
        $pdo->exec($sql);
        echo "✓ Columna Visible agregada\n";
    }
    
    // Actualizar aplicaciones existentes para tener Visible = 1
    $sql = "UPDATE gcp_aplicaciones SET Visible = 1 WHERE Visible IS NULL OR Visible = 0";
    $pdo->exec($sql);
    echo "✓ Aplicaciones actualizadas con Visible = 1\n";
    
    // Crear aplicaciones si no existen
    $aplicaciones = [
        ['Nombre' => 'Dashboard', 'Codigo' => 'dashboard', 'Ruta' => '/dashboard', 'Color' => '#6366f1', 'Icono' => 'layout-dashboard', 'OrdenMenu' => 1, 'Visible' => 1, 'Estado' => 1],
        ['Nombre' => 'Intranet', 'Codigo' => 'intranet', 'Ruta' => '/intranet', 'Color' => '#3b82f6', 'Icono' => 'book', 'OrdenMenu' => 2, 'Visible' => 1, 'Estado' => 1],
        ['Nombre' => 'Solicitudes', 'Codigo' => 'solicitudes', 'Ruta' => '/solicitudes', 'Color' => '#10b981', 'Icono' => 'clipboard-list', 'OrdenMenu' => 3, 'Visible' => 1, 'Estado' => 1],
        ['Nombre' => 'Gestión Tecnológica', 'Codigo' => 'gestion', 'Ruta' => '/gestion', 'Color' => '#f59e0b', 'Icono' => 'package', 'OrdenMenu' => 4, 'Visible' => 1, 'Estado' => 1],
        ['Nombre' => 'Seguridad', 'Codigo' => 'seguridad', 'Ruta' => '/security', 'Color' => '#ef4444', 'Icono' => 'shield', 'OrdenMenu' => 5, 'Visible' => 1, 'Estado' => 1],
    ];
    
    $appIds = [];
    foreach ($aplicaciones as $app) {
        $stmt = $pdo->prepare("SELECT Id FROM gcp_aplicaciones WHERE Codigo = :codigo");
        $stmt->execute([':codigo' => $app['Codigo']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $appIds[$app['Codigo']] = $existing['Id'];
            echo "✓ Aplicación {$app['Nombre']} ya existe (ID: {$existing['Id']})\n";
        } else {
            $sql = "INSERT INTO gcp_aplicaciones (Nombre, Codigo, Ruta, Color, Icono, OrdenMenu, Visible, Estado) VALUES (:Nombre, :Codigo, :Ruta, :Color, :Icono, :OrdenMenu, :Visible, :Estado)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($app);
            $appIds[$app['Codigo']] = (int)$pdo->lastInsertId();
            echo "✓ Aplicación {$app['Nombre']} creada (ID: {$appIds[$app['Codigo']]})\n";
        }
    }
    
    // Crear permisos
    $permisos = [
        // Dashboard
        [$appIds['dashboard'], 'dashboard', 'Ver', 'Ver dashboard', 'dashboard.ver'],
        
        // Intranet
        [$appIds['intranet'], 'intranet', 'Ver', 'Ver módulo de intranet', 'intranet.ver'],
        [$appIds['intranet'], 'intranet', 'Administrar Noticias', 'Administrar noticias y anuncios', 'intranet.administrar_noticias'],
        [$appIds['intranet'], 'intranet', 'Administrar Documentos', 'Administrar documentos compartidos', 'intranet.administrar_documentos'],
        [$appIds['intranet'], 'intranet', 'Administrar Eventos', 'Administrar calendario de eventos', 'intranet.administrar_eventos'],
        [$appIds['intranet'], 'intranet', 'Administrar Directorio', 'Editar los contactos del directorio', 'intranet.administrar_directorio'],
        
        // Solicitudes
        [$appIds['solicitudes'], 'solicitudes', 'Ver', 'Ver módulo de solicitudes', 'solicitudes.ver'],
        [$appIds['solicitudes'], 'solicitudes', 'Crear', 'Crear nuevas solicitudes', 'solicitudes.crear'],
        [$appIds['solicitudes'], 'solicitudes', 'Administrar', 'Administrar solicitudes completas', 'solicitudes.administrar'],
        [$appIds['solicitudes'], 'solicitudes', 'Administrar Categorías', 'Administrar categorías de solicitudes', 'solicitudes.administrar_categorias'],
        
        // Gestión Tecnológica
        [$appIds['gestion'], 'gestion', 'Ver', 'Ver módulo de gestión tecnológica', 'gestion.ver'],
        [$appIds['gestion'], 'gestion', 'Administrar Activos', 'Administrar activos tecnológicos', 'gestion.administrar_activos'],
        [$appIds['gestion'], 'gestion', 'Administrar Mantenimiento', 'Administrar mantenimientos', 'gestion.administrar_mantenimiento'],
        [$appIds['gestion'], 'gestion', 'Administrar Incidencias', 'Administrar incidencias tecnológicas', 'gestion.administrar_incidencias'],
        [$appIds['gestion'], 'gestion', 'Administrar Licencias', 'Administrar licencias de software', 'gestion.administrar_licencias'],
        
        // Seguridad
        [$appIds['seguridad'], 'seguridad', 'Ver', 'Ver módulo de seguridad', 'seguridad.ver'],
        [$appIds['seguridad'], 'seguridad', 'Administrar Aplicaciones', 'Administrar aplicaciones', 'seguridad.administrar_aplicaciones'],
        [$appIds['seguridad'], 'seguridad', 'Administrar Permisos', 'Administrar permisos', 'seguridad.administrar_permisos'],
        [$appIds['seguridad'], 'seguridad', 'Administrar Roles', 'Administrar roles', 'seguridad.administrar_roles'],
        [$appIds['seguridad'], 'seguridad', 'Administrar Usuarios', 'Administrar usuarios', 'seguridad.administrar_usuarios'],
    ];
    
    $permisoIds = [];
    foreach ($permisos as $perm) {
        list($appId, $modulo, $accion, $descripcion, $codigo) = $perm;
        
        $stmt = $pdo->prepare("SELECT Id FROM gcp_permisos WHERE CodigoUnico = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $permisoIds[$codigo] = $existing['Id'];
            echo "✓ Permiso {$codigo} ya existe (ID: {$existing['Id']})\n";
        } else {
            $sql = "INSERT INTO gcp_permisos (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado) VALUES (:AplicacionId, :Modulo, :Accion, :Descripcion, :CodigoUnico, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':AplicacionId' => $appId,
                ':Modulo' => $modulo,
                ':Accion' => $accion,
                ':Descripcion' => $descripcion,
                ':CodigoUnico' => $codigo,
            ]);
            $permisoIds[$codigo] = (int)$pdo->lastInsertId();
            echo "✓ Permiso {$codigo} creado (ID: {$permisoIds[$codigo]})\n";
        }
    }
    
    // Asignar todos los permisos al rol Administrador (RolId = 1)
    echo "\nAsignando permisos al rol Administrador...\n";
    foreach ($permisoIds as $permisoId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM gcp_rolespermisos WHERE RolId = 1 AND PermisoId = :permisoId");
        $stmt->execute([':permisoId' => $permisoId]);
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $sql = "INSERT INTO gcp_rolespermisos (RolId, PermisoId) VALUES (1, :permisoId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':permisoId' => $permisoId]);
            echo "✓ Permiso ID {$permisoId} asignado al rol Administrador\n";
        }
    }
    
    echo "\n✓ Configuración de permisos del Core completada\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}