<?php

// Ejecutar contra una base de pruebas con la migración 042 aplicada.
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\HorarioService;

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
    $agencia = $db->query('SELECT Id, Nombre FROM gcp_agencias ORDER BY Id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $cargo = $db->query('SELECT Id, RolId FROM gcp_cargos ORDER BY Id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    expect($agencia !== false && $cargo !== false, 'Faltan catálogos de prueba.');

    $insertUser = $db->prepare('
        INSERT INTO gcp_usuarios (AgenciaId, CargoId, RolId, Nombres, Apellidos, Area, EmailCorporativo, Estado)
        VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
    ');
    $insertAttendance = $db->prepare('INSERT INTO control_asistencia (person_id, nombre, fecha_hora) VALUES (?, ?, ?)');
    $checkId = $db->prepare('SELECT 1 FROM control_asistencia WHERE person_id = ? LIMIT 1');
    $people = [
        ['99999991', 'Zafiro Robledal', 'Área de prueba', 1],
        ['99999992', 'Turquesa Naranjal', null, 1],
        ['99999993', 'Ambar Guayabal', 'Área inactiva', 0],
    ];
    $ids = [];
    foreach ($people as [$pid, $name, $area, $active]) {
        $checkId->execute([$pid]);
        expect(!$checkId->fetchColumn(), 'El identificador de asistencia de prueba ya existe.');
        [$first, $last] = explode(' ', $name, 2);
        $insertUser->execute([$agencia['Id'], $cargo['Id'], $cargo['RolId'], $first, $last, $area, $active]);
        $ids[] = (int) $db->lastInsertId();
        $insertAttendance->execute([$pid, $name, '2025-01-13 08:16:00']);
        $insertAttendance->execute([$pid, $name, '2025-01-13 17:00:00']);
    }

    $report = (new HorarioService())->report('2025-01-13', '2025-01-13');
    $days = array_column($report['dias'], null, 'pid');
    expect($days['99999991']['area'] === 'Área de prueba', 'No se encontró el área del usuario sin correo.');
    expect($days['99999992']['area'] === $agencia['Nombre'], 'No se aplicó la agencia como área alternativa.');
    expect($days['99999993']['area'] === 'Sin área', 'Se utilizó un usuario inactivo.');
    expect($days['99999991']['tarde'] === 16, 'El cálculo de minutos cambió.');
    $view = $db->prepare('SELECT COUNT(*) FROM directorio_gacela_conecta WHERE id = ?');
    $view->execute([$ids[0]]);
    expect((int) $view->fetchColumn() === 0, 'El usuario de prueba no debe aparecer en el directorio.');

    $db->prepare('UPDATE gcp_usuarios SET Area = ? WHERE Id = ?')->execute(['Área actualizada', $ids[0]]);
    $updated = array_column((new HorarioService())->report('2025-01-13', '2025-01-13')['dias'], null, 'pid');
    expect($updated['99999991']['area'] === 'Área actualizada', 'Horario no refleja el cambio de área.');

    echo "Horario desde usuarios: pruebas correctas.\n";
} finally {
    $db->rollBack();
}
