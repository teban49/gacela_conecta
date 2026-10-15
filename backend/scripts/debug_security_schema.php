<?php
require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/database.php';
\App\Core\Database::init($config);
$db = \App\Core\Database::getConnection();

$tables = ['GCP_Permisos', 'GCP_Roles', 'GCP_Usuarios', 'GCP_RolesPermisos', 'GCP_UsuariosRoles'];
foreach ($tables as $table) {
    try {
        $count = $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
        echo $table . ' => ' . $count . PHP_EOL;
    } catch (Throwable $e) {
        echo $table . ' => ERROR: ' . $e->getMessage() . PHP_EOL;
    }
}

try {
    $stmt = $db->query('DESCRIBE GCP_Permisos');
    echo PHP_EOL . 'DESCRIBE GCP_Permisos' . PHP_EOL;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' | ' . $row['Type'] . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'DESCRIBE ERROR: ' . $e->getMessage() . PHP_EOL;
}
