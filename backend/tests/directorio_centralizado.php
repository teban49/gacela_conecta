<?php

// Ejecutar contra una base de pruebas con la migración 042 aplicada.
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\Intranet\IntranetRepository;

$baseDir = dirname(__DIR__);
if (file_exists($baseDir . '/.env')) {
    Dotenv\Dotenv::createImmutable($baseDir)->safeLoad();
}
Database::init(require $baseDir . '/config/database.php');
$db = Database::getConnection();

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$db->beginTransaction();
try {
    $agencia = $db->query('SELECT Id FROM gcp_agencias ORDER BY Id LIMIT 1')->fetchColumn();
    $cargo = $db->query('SELECT Id, RolId FROM gcp_cargos ORDER BY Id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $empresa = $db->query('SELECT Id, Nombre FROM gcp_empresas ORDER BY Id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    expect($agencia !== false && $cargo !== false && $empresa !== false, 'Faltan catálogos de prueba.');

    $insert = $db->prepare('
        INSERT INTO gcp_usuarios (AgenciaId, CargoId, RolId, Nombres, Apellidos, EmailCorporativo, Estado)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $ids = [];
    foreach ([
        ['Con correo', 'prueba-' . bin2hex(random_bytes(8)) . '@example.test', 1],
        ['Sin correo', null, 1],
        ['Correo distinto', 'prueba-' . bin2hex(random_bytes(8)), 1],
        ['Inactivo', null, 0],
    ] as [$nombre, $email, $estado]) {
        $insert->execute([$agencia, $cargo['Id'], $cargo['RolId'], $nombre, 'Prueba directorio', $email, $estado]);
        $ids[] = (int) $db->lastInsertId();
    }

    $db->prepare('INSERT INTO gcp_usuariosempresas (UsuarioId, EmpresaId) VALUES (?, ?)')
        ->execute([$ids[0], $empresa['Id']]);
    $repository = new IntranetRepository();
    $entries = array_column($repository->getDirectorio(), null, 'id');
    expect(isset($entries[$ids[0]]), 'Falta un usuario activo con correo.');
    foreach (array_slice($ids, 1) as $id) {
        expect(!isset($entries[$id]), 'Un usuario sin correo válido o inactivo aparece en el directorio.');
    }
    expect($entries[$ids[0]]['empresa'] === $empresa['Nombre'], 'No se refleja la empresa vinculada.');

    $db->prepare('UPDATE gcp_usuarios SET Nombres = ?, Telefono = ? WHERE Id = ?')
        ->execute(['Nombre actualizado', '1234567890', $ids[0]]);
    $entries = array_column($repository->getDirectorio(), null, 'id');
    expect($entries[$ids[0]]['first_name'] === 'Nombre actualizado', 'No se refleja la edición.');
    expect($entries[$ids[0]]['phone'] === '1234567890', 'No se refleja el teléfono actualizado.');

    $db->prepare('DELETE FROM gcp_usuarios WHERE Id = ?')->execute([$ids[0]]);
    $entries = array_column($repository->getDirectorio(), null, 'id');
    expect(!isset($entries[$ids[0]]), 'Un usuario eliminado sigue en el directorio.');

    $missing = $db->query("
        SELECT COUNT(*)
        FROM gcp_usuarios u
        LEFT JOIN directorio_gacela_conecta d ON d.id = u.Id
        WHERE u.Estado = 1 AND u.EmailCorporativo LIKE '%@%.%' AND d.id IS NULL
    ")->fetchColumn();
    expect((int) $missing === 0, 'Hay usuarios activos con correo que faltan en el directorio.');

    echo "Directorio centralizado: pruebas correctas.\n";
} finally {
    $db->rollBack();
}
