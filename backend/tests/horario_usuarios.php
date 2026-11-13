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
    $companyId = $db->query('SELECT Id FROM gcp_empresas ORDER BY Id LIMIT 1')->fetchColumn();
    $rdCompanyId = $db->query("SELECT Id FROM gcp_empresas WHERE Codigo = 'RD' LIMIT 1")->fetchColumn();
    $pdrCompanyId = $db->query("SELECT Id FROM gcp_empresas WHERE Codigo = 'PDR' LIMIT 1")->fetchColumn();
    $fmCompanyId = $db->query("SELECT Id FROM gcp_empresas WHERE Codigo = 'FM' LIMIT 1")->fetchColumn();
    $accountingRoleId = $db->query("SELECT Id FROM gcp_roles WHERE Codigo = 'contabilidad' AND Estado = 1")->fetchColumn();
    $standardRoleId = $db->query("SELECT Id FROM gcp_roles WHERE Codigo = 'usuario' AND Estado = 1")->fetchColumn();
    $managementRoleId = $db->query("SELECT Id FROM gcp_roles WHERE Codigo = 'gerencia' AND Estado = 1")->fetchColumn();
    $chiefCargo = $db->prepare("SELECT Id FROM gcp_cargos WHERE RolId = :roleId AND LOWER(Nombre) LIKE '%jefe%' ORDER BY Id LIMIT 1");
    $chiefCargo->execute([':roleId' => $accountingRoleId]);
    $chiefCargoId = $chiefCargo->fetchColumn();
    expect($agencia !== false && $cargo !== false && $companyId, 'Faltan catálogos de prueba.');
    expect($accountingRoleId && $standardRoleId && $managementRoleId, 'Faltan los roles de prueba.');
    expect($chiefCargoId, 'Falta un cargo jefe para probar el límite de área.');

    $insertUser = $db->prepare('
        INSERT INTO gcp_usuarios (AgenciaId, CargoId, RolId, Nombres, Apellidos, Area, EmailCorporativo, Estado)
        VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
    ');
    $insertAttendance = $db->prepare('INSERT INTO control_asistencia (person_id, nombre, fecha_hora) VALUES (?, ?, ?)');
    $checkId = $db->prepare('SELECT 1 FROM control_asistencia WHERE person_id = ? LIMIT 1');
    $people = [
        ['99999991', 'Zafiro Robledal', 'Área de prueba', $accountingRoleId, 1],
        ['99999992', 'Turquesa Naranjal', null, $accountingRoleId, 1],
        ['99999993', 'Ambar Guayabal', 'Área inactiva', $accountingRoleId, 0],
        ['99999994', 'Menta Guayacán', 'Área diferente', $standardRoleId, 1],
        ['99999995', 'Aurelio Leonard Roderiguez Gonzales', 'Área de prueba', $accountingRoleId, 1],
        ['99999996', 'Violeta Rocio Cabrera Herrera', 'Área de prueba', $accountingRoleId, 1],
        ['99999997', 'Zorelia Alejandra Urueña Solano', 'Área de prueba', $accountingRoleId, 1],
    ];
    $attendanceNameVariants = [
        '99999995' => 'Aurelio Leonard Roder�guez',
        '99999996' => 'Violeta Rocio Cabrera',
        '99999997' => 'Zorelia Alejandra Urue�a Solano',
    ];
    $ids = [];
    foreach ($people as [$pid, $name, $area, $roleId, $active]) {
        $checkId->execute([$pid]);
        expect(!$checkId->fetchColumn(), 'El identificador de asistencia de prueba ya existe.');
        [$first, $last] = explode(' ', $name, 2);
        $insertUser->execute([$agencia['Id'], $chiefCargoId, $roleId, $first, $last, $area, $active]);
        $ids[] = (int) $db->lastInsertId();
        $insertAttendance->execute([$pid, $name, '2025-01-13 08:16:00']);
        $insertAttendance->execute([$pid, $name, '2025-01-13 17:00:00']);
        if (isset($attendanceNameVariants[$pid])) {
            $insertAttendance->execute([$pid, $attendanceNameVariants[$pid], '2025-01-13 07:00:00']);
        }
    }

    $insertUser->execute([$agencia['Id'], $cargo['Id'], $managementRoleId, 'Gerente', 'Prueba', 'Empresa de prueba', 1]);
    $managerId = (int) $db->lastInsertId();
    $assignCompany = $db->prepare('INSERT IGNORE INTO gcp_usuariosempresas (UsuarioId, EmpresaId) VALUES (?, ?)');
    $assignCompany->execute([$managerId, $companyId]);
    $assignCompany->execute([$ids[0], $companyId]);

    $insertUser->execute([$agencia['Id'], $chiefCargoId, $standardRoleId, 'Jefe', 'Directo', 'Área de prueba', 1]);
    $directManagerId = (int) $db->lastInsertId();
    $db->prepare('UPDATE gcp_usuarios SET CargoJefeDirectoId = ? WHERE Id = ?')->execute([$chiefCargoId, $ids[0]]);

    $report = (new HorarioService())->report('2025-01-13', '2025-01-13');
    $days = array_column($report['dias'], null, 'pid');
    expect($days['99999991']['area'] === 'Área de prueba', 'No se encontró el área del usuario sin correo.');
    expect($days['99999992']['area'] === $agencia['Nombre'], 'No se aplicó la agencia como área alternativa.');
    expect($days['99999993']['area'] === 'Sin área', 'Se utilizó un usuario inactivo.');
    expect($days['99999995']['nombre'] === 'Aurelio Leonard Roderiguez Gonzales', 'No se corrigió el nombre dañado con el nombre maestro.');
    expect($days['99999997']['nombre'] === 'Zorelia Alejandra Urueña Solano', 'No se corrigió la ñ dañada con el nombre maestro.');
    expect($days['99999991']['tarde'] === 16, 'El cálculo de minutos cambió.');
    $service = new HorarioService();
    expect($service->visibleAttendancePersonIds($ids[0], ['admin']) === null, 'Administración debe conservar acceso global.');
    expect($service->visibleAttendancePersonIds($ids[0], ['directivos']) === null, 'Directivos deben conservar acceso global.');
    $accountingPersonIds = $service->visibleAttendancePersonIds($ids[0], ['contabilidad']);
    expect(in_array('99999991', $accountingPersonIds, true), 'No se incluyó a una persona del rol Contabilidad.');
    foreach (['99999995', '99999996', '99999997'] as $pid) {
        expect(in_array($pid, $accountingPersonIds, true), 'No se asoció una marcación con nombre parcial o carácter dañado: ' . $pid);
    }
    expect(!in_array('99999992', $accountingPersonIds, true), 'Se incluyó una persona de otra área.');
    expect(!in_array('99999994', $accountingPersonIds, true), 'Se incluyó una persona de otro rol.');
    expect($service->areaLimitForUser($ids[0], ['contabilidad']) === 'Área de prueba', 'No se limitó el jefe a su área asignada.');

    $companyPersonIds = $service->visibleAttendancePersonIds($managerId, ['gerencia']);
    expect(in_array('99999991', $companyPersonIds, true), 'No se incluyó a una persona de la empresa asignada.');
    expect(!in_array('99999994', $companyPersonIds, true), 'Se incluyó a una persona sin empresa compartida.');

    expect($rdCompanyId && $pdrCompanyId && $fmCompanyId, 'Faltan las empresas FM, RD o PDR.');
    $diegoId = $db->query("SELECT Id FROM gcp_usuarios WHERE Nombres = 'Diego' AND Apellidos = 'Romero Sanabria' AND Estado = 1 LIMIT 1")->fetchColumn();
    $victorId = $db->query("SELECT Id FROM gcp_usuarios WHERE Nombres = 'Victor Hugo' AND Apellidos = 'Romero' AND Estado = 1 LIMIT 1")->fetchColumn();
    expect($diegoId && $victorId, 'Faltan las cuentas gerenciales de RD y PDR.');
    $db->prepare('DELETE FROM gcp_usuariosempresas WHERE UsuarioId = ? AND EmpresaId = ?')->execute([$ids[0], $companyId]);
    $assignCompany->execute([$ids[0], $rdCompanyId]);
    $assignCompany->execute([$ids[1], $pdrCompanyId]);
    $assignCompany->execute([$ids[3], $fmCompanyId]);
    $assignCompany->execute([$managerId, $rdCompanyId]);
    $assignCompany->execute([$managerId, $pdrCompanyId]);
    $assignCompany->execute([$diegoId, $rdCompanyId]);
    $assignCompany->execute([$victorId, $pdrCompanyId]);

    $rdPeople = $service->visibleAttendancePersonIds((int) $diegoId, ['gerencia']);
    expect(in_array('99999991', $rdPeople, true), 'Diego Romero no ve los registros de RD.');
    expect(!in_array('99999992', $rdPeople, true), 'Diego Romero puede ver registros de PDR.');
    expect(!in_array('99999994', $rdPeople, true), 'Diego Romero puede ver registros de Flota.');

    $pdrPeople = $service->visibleAttendancePersonIds((int) $victorId, ['gerencia']);
    expect(in_array('99999992', $pdrPeople, true), 'Víctor Hugo Romero no ve los registros de PDR.');
    expect(!in_array('99999991', $pdrPeople, true), 'Víctor Hugo Romero puede ver registros de RD.');

    $unapprovedManagerPeople = $service->visibleAttendancePersonIds($managerId, ['gerencia']);
    expect(!in_array('99999991', $unapprovedManagerPeople, true), 'Otro gerente puede ver registros de RD.');
    expect(!in_array('99999992', $unapprovedManagerPeople, true), 'Otro gerente puede ver registros de PDR.');

    $directReportPersonIds = $service->visibleAttendancePersonIds($directManagerId, ['usuario']);
    expect(in_array('99999991', $directReportPersonIds, true), 'No se incluyó a un reporte directo del jefe.');
    expect(!in_array('99999994', $directReportPersonIds, true), 'Se incluyó a una persona fuera del equipo directo.');

    $scopedReport = $service->report('2025-01-13', '2025-01-13', $accountingPersonIds, 'Área de prueba');
    $scopedPids = array_unique(array_column($scopedReport['dias'], 'pid'));
    expect(in_array('99999991', $scopedPids, true), 'El informe acotado omitió una persona permitida.');
    expect(!in_array('99999994', $scopedPids, true), 'El informe acotado expuso una persona de otro rol.');
    expect($scopedReport['areas'] === ['Área de prueba'] && $scopedReport['areaLimit'] === 'Área de prueba', 'El informe no limitó el área devuelta.');
    $emptyReport = $service->report('2025-01-13', '2025-01-13', []);
    expect($emptyReport['minDate'] === null && $emptyReport['dias'] === [] && $emptyReport['ausencias'] === [], 'Un alcance vacío no debe devolver datos.');

    $view = $db->prepare('SELECT COUNT(*) FROM directorio_gacela_conecta WHERE id = ?');
    $view->execute([$ids[0]]);
    expect((int) $view->fetchColumn() === 0, 'El usuario de prueba no debe aparecer en el directorio.');

    $db->prepare('UPDATE gcp_usuarios SET Area = ? WHERE Id = ?')->execute(['Área actualizada', $ids[0]]);
    $updated = array_column($service->report('2025-01-13', '2025-01-13')['dias'], null, 'pid');
    expect($updated['99999991']['area'] === 'Área actualizada', 'Horario no refleja el cambio de área.');

    echo "Horario desde usuarios: pruebas correctas.\n";
} finally {
    $db->rollBack();
}
