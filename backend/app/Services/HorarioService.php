<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Cálculo de cumplimiento de horario a partir de las marcaciones de control_asistencia.
 */
class HorarioService
{
    private const START_MINUTES = 480;          // 8:00 AM
    private const LATE_TOLERANCE = 15;          // solo se cuenta tarde después de las 8:15
    private const END_MONDAY = 1020;            // 5:00 PM
    private const END_OTHER = 1050;             // 5:30 PM
    private const MIN_SPAN_MINUTES = 60;        // marcaciones muy cercanas se consideran duplicadas

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public static function canView(int $userId, array $roleCodes): bool
    {
        if (array_intersect(['admin', 'gerencia', 'directivos'], $roleCodes)) {
            return true;
        }
        $stmt = Database::getConnection()->prepare(
            'SELECT c.Nombre FROM gcp_usuarios u INNER JOIN gcp_cargos c ON c.Id = u.CargoId WHERE u.Id = :id'
        );
        $stmt->execute([':id' => $userId]);
        $cargo = (string) $stmt->fetchColumn();

        return stripos($cargo, 'jefe') !== false;
    }

    /** Devuelve null para roles globales; en los demás casos resuelve el alcance de asistencia. */
    public function visibleAttendancePersonIds(int $userId, array $roleCodes): ?array
    {
        if (array_intersect(['admin', 'directivos'], $roleCodes)) {
            return null;
        }

        if (in_array('gerencia', $roleCodes, true)) {
            $manager = $this->db->prepare('SELECT Nombres, Apellidos FROM gcp_usuarios WHERE Id = :userId AND Estado = 1');
            $manager->execute([':userId' => $userId]);
            $managerRow = $manager->fetch(PDO::FETCH_ASSOC);
            if (!$managerRow) {
                return [];
            }

            $managerName = $this->attendanceNameKey(trim($managerRow['Nombres'] . ' ' . $managerRow['Apellidos']));
            $rdManagers = ['diego romero', 'diego romero sanabria'];
            $pdrManagers = ['hugo romero victor', 'romero victor'];
            $isRdManager = in_array($managerName, $rdManagers, true);
            $isPdrManager = in_array($managerName, $pdrManagers, true);

            $companies = $this->db->prepare('
                SELECT DISTINCT e.Id, UPPER(e.Codigo) AS Codigo
                FROM gcp_usuariosempresas ue
                INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId
                WHERE ue.UsuarioId = :userId
            ');
            $companies->execute([':userId' => $userId]);
            $companyIds = [];
            foreach ($companies->fetchAll(PDO::FETCH_ASSOC) as $company) {
                $code = strtoupper((string) $company['Codigo']);
                if (($code === 'RD' && !$isRdManager)
                    || ($code === 'PDR' && !$isPdrManager)
                    || ($isRdManager && $code !== 'RD')
                    || ($isPdrManager && $code !== 'PDR')) {
                    continue;
                }
                $companyIds[] = (int) $company['Id'];
            }
            if ($companyIds === []) {
                return [];
            }

            $params = [];
            $placeholders = [];
            foreach ($companyIds as $index => $companyId) {
                $placeholder = ':company' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $companyId;
            }
            $staff = $this->db->prepare('
                SELECT DISTINCT staff.Id
                FROM gcp_usuariosempresas staff_company
                INNER JOIN gcp_usuarios staff ON staff.Id = staff_company.UsuarioId
                WHERE staff_company.EmpresaId IN (' . implode(', ', $placeholders) . ')
                  AND staff.Estado = 1
            ');
            $staff->execute($params);

            return $this->attendanceIdsForUsers(array_map('intval', $staff->fetchAll(PDO::FETCH_COLUMN)));
        }

        $areaLimit = $this->areaLimitForUser($userId, $roleCodes);
        $departmentRoles = array_values(array_diff(
            $roleCodes,
            ['admin', 'directivos', 'gerencia', 'jefe', 'usuario', 'usuario_pruebas', 'conductor']
        ));
        if ($departmentRoles !== []) {
            if ($areaLimit === null) {
                return [];
            }
            $rolePlaceholders = [];
            $params = [];
            foreach ($departmentRoles as $index => $roleCode) {
                $placeholder = ':role' . $index;
                $rolePlaceholders[] = $placeholder;
                $params[$placeholder] = $roleCode;
            }

            $stmt = $this->db->prepare('
                SELECT DISTINCT u.Id
                FROM gcp_usuarios u
                INNER JOIN gcp_roles r ON r.Id = u.RolId
                INNER JOIN gcp_agencias a ON a.Id = u.AgenciaId
                WHERE u.Estado = 1
                  AND r.Codigo IN (' . implode(', ', $rolePlaceholders) . ')
                  AND COALESCE(NULLIF(TRIM(u.Area), \'\'), a.Nombre) = :areaLimit
            ');
            $params[':areaLimit'] = $areaLimit;
            $stmt->execute($params);

            return $this->attendanceIdsForUsers(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
        }

        $manager = $this->db->prepare('SELECT CargoId FROM gcp_usuarios WHERE Id = :userId AND Estado = 1');
        $manager->execute([':userId' => $userId]);
        $cargoId = $manager->fetchColumn();
        if (!$cargoId) {
            return [];
        }

        $hasManagerCargo = $this->db->prepare("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'gcp_usuarios'
              AND COLUMN_NAME = 'CargoJefeDirectoId'
        ");
        $hasManagerCargo->execute();
        if (!$hasManagerCargo->fetchColumn()) {
            error_log('[Horarios API] No se puede limitar un jefe sin gcp_usuarios.CargoJefeDirectoId.');
            return [];
        }

        $staff = $this->db->prepare('
            SELECT u.Id
            FROM gcp_usuarios u
            INNER JOIN gcp_agencias a ON a.Id = u.AgenciaId
            WHERE u.Estado = 1
              AND (u.Id = :userId OR u.CargoJefeDirectoId = :cargoId)
              AND (:areaLimit IS NULL OR COALESCE(NULLIF(TRIM(u.Area), \'\'), a.Nombre) = :staffAreaLimit)
        ');
        $staff->execute([
            ':userId' => $userId,
            ':cargoId' => $cargoId,
            ':areaLimit' => $areaLimit,
            ':staffAreaLimit' => $areaLimit,
        ]);

        return $this->attendanceIdsForUsers(array_map('intval', $staff->fetchAll(PDO::FETCH_COLUMN)));
    }

    public function areaLimitForUser(int $userId, array $roleCodes): ?string
    {
        if (array_intersect(['admin', 'directivos', 'gerencia'], $roleCodes)) {
            return null;
        }

        $stmt = $this->db->prepare('
            SELECT c.Nombre AS Cargo, COALESCE(NULLIF(TRIM(u.Area), \'\'), a.Nombre) AS Area
            FROM gcp_usuarios u
            INNER JOIN gcp_cargos c ON c.Id = u.CargoId
            INNER JOIN gcp_agencias a ON a.Id = u.AgenciaId
            WHERE u.Id = :userId AND u.Estado = 1
        ');
        $stmt->execute([':userId' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || stripos((string) $user['Cargo'], 'jefe') === false || trim((string) $user['Area']) === '') {
            return null;
        }

        return trim((string) $user['Area']);
    }

    /**
     * Inserta marcaciones nuevas; las que ya existen (misma persona y fecha-hora) se omiten.
     *
     * @return array{recibidos:int,nuevos:int,duplicados:int,invalidos:int,desde:?string,hasta:?string}
     */
    public function import(array $records): array
    {
        $valid = [];
        $invalid = 0;
        foreach ($records as $record) {
            $pid = preg_replace('/\D/', '', (string) ($record['person_id'] ?? ''));
            $name = trim((string) ($record['nombre'] ?? ''));
            $dt = \DateTime::createFromFormat('!Y-m-d H:i:s', (string) ($record['fecha_hora'] ?? ''));
            if ($pid === '' || strlen($pid) > 8 || $name === '' || strlen($name) > 255 || !$dt || $dt->format('Y-m-d H:i:s') !== $record['fecha_hora']) {
                $invalid++;
                continue;
            }
            $valid[str_pad($pid, 8, '0', STR_PAD_LEFT) . '|' . $record['fecha_hora']] = [str_pad($pid, 8, '0', STR_PAD_LEFT), $name, $record['fecha_hora']];
        }

        $inserted = 0;
        $from = $to = null;
        if ($valid) {
            $dates = array_column($valid, 2);
            sort($dates);
            $from = substr($dates[0], 0, 10);
            $to = substr(end($dates), 0, 10);

            $existing = [];
            $stmt = $this->db->prepare('SELECT person_id, fecha_hora FROM control_asistencia WHERE fecha_hora >= :from AND fecha_hora < DATE_ADD(:to, INTERVAL 1 DAY)');
            $stmt->execute([':from' => $from, ':to' => $to]);
            foreach ($stmt->fetchAll(PDO::FETCH_NUM) as [$pid, $dt]) {
                $existing[$pid . '|' . $dt] = true;
            }

            $insert = $this->db->prepare('INSERT INTO control_asistencia (person_id, nombre, fecha_hora) VALUES (?, ?, ?)');
            $this->db->beginTransaction();
            try {
                foreach ($valid as $key => $row) {
                    if (isset($existing[$key])) {
                        continue;
                    }
                    $insert->execute($row);
                    $inserted++;
                }
                $this->db->commit();
            } catch (\Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        return [
            'recibidos' => count($records),
            'nuevos' => $inserted,
            'duplicados' => count($valid) - $inserted,
            'invalidos' => $invalid,
            'desde' => $from,
            'hasta' => $to,
        ];
    }

    public function report(?string $from, ?string $to, ?array $visiblePersonIds = null, ?string $areaLimit = null): array
    {
        $params = [];
        $personFilter = $this->personFilter($visiblePersonIds, $params);
        $rangeQuery = $this->db->prepare(
            'SELECT DATE(MIN(fecha_hora)) a, DATE(MAX(fecha_hora)) b FROM control_asistencia WHERE 1 = 1' . $personFilter
        );
        $rangeQuery->execute($params);
        $range = $rangeQuery->fetch(PDO::FETCH_ASSOC);
        $minDate = $range['a'] ?? null;
        $maxDate = $range['b'] ?? null;
        if (!$minDate) {
            return ['desde' => null, 'hasta' => null, 'minDate' => null, 'maxDate' => null, 'areas' => $areaLimit === null ? [] : [$areaLimit], 'dias' => [], 'ausencias' => [], 'areaLimit' => $areaLimit];
        }

        $from = $this->validDate($from) ?? date('Y-m-01', strtotime($maxDate));
        $to = $this->validDate($to) ?? $maxDate;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $areaByPerson = $this->areaByPerson($visiblePersonIds);

        $firstSeen = [];
        $params = [];
        $firstSeenQuery = $this->db->prepare(
            'SELECT person_id, DATE(MIN(fecha_hora)) d FROM control_asistencia WHERE 1 = 1'
            . $this->personFilter($visiblePersonIds, $params)
            . ' GROUP BY person_id'
        );
        $firstSeenQuery->execute($params);
        foreach ($firstSeenQuery as $row) {
            $firstSeen[$row['person_id']] = $row['d'];
        }

        $params = [':from' => $from, ':to' => $to];
        $stmt = $this->db->prepare(
            'SELECT person_id, nombre, fecha_hora FROM control_asistencia
             WHERE fecha_hora >= :from AND fecha_hora < DATE_ADD(:to, INTERVAL 1 DAY)'
            . $this->personFilter($visiblePersonIds, $params) . '
             ORDER BY person_id, fecha_hora'
        );
        $stmt->execute($params);

        $grouped = [];
        $names = [];
        $canonicalUsers = $this->canonicalUsers();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $date = substr($row['fecha_hora'], 0, 10);
            $grouped[$row['person_id']][$date][] = substr($row['fecha_hora'], 11, 5);
            $name = (string) $row['nombre'];
            if (str_contains($name, "\u{FFFD}")) {
                $matchedUserId = $this->matchAttendanceUserId($name, $canonicalUsers['tokensById']);
                $name = $canonicalUsers['namesById'][$matchedUserId] ?? $name;
            }
            $names[$row['person_id']] = $name;
        }

        $holidays = $this->holidaysBetween($from, $to);
        $dias = [];
        $areas = [];

        foreach ($grouped as $pid => $byDate) {
            $area = $areaLimit ?? $areaByPerson[$pid] ?? 'Sin área';
            $areas[$area] = true;
            foreach ($byDate as $date => $times) {
                $dias[] = $this->evaluateDay((string) $pid, $names[$pid], $area, $date, $times, $holidays[$date] ?? null);
            }
        }

        $ausencias = [];
        $today = date('Y-m-d');
        foreach ($names as $pid => $name) {
            $area = $areaLimit ?? $areaByPerson[$pid] ?? 'Sin área';
            $start = max($from, $firstSeen[$pid] ?? $from);
            for ($ts = strtotime($start), $end = strtotime($to); $ts <= $end; $ts = strtotime('+1 day', $ts)) {
                $date = date('Y-m-d', $ts);
                if ($date >= $today || (int) date('N', $ts) >= 6 || isset($holidays[$date]) || isset($grouped[$pid][$date])) {
                    continue;
                }
                $ausencias[] = ['pid' => (string) $pid, 'nombre' => $name, 'area' => $area, 'fecha' => $date];
            }
        }

        $areaList = array_keys($areas);
        if ($areaLimit !== null && $areaList === []) {
            $areaList = [$areaLimit];
        }
        sort($areaList);
        usort($dias, static fn ($a, $b) => [$a['fecha'], $a['nombre']] <=> [$b['fecha'], $b['nombre']]);

        return [
            'desde' => $from,
            'hasta' => $to,
            'minDate' => $minDate,
            'maxDate' => $maxDate,
            'areas' => $areaList,
            'dias' => $dias,
            'ausencias' => $ausencias,
            'areaLimit' => $areaLimit,
        ];
    }

    private function evaluateDay(string $pid, string $name, string $area, string $date, array $times, ?string $holiday): array
    {
        $ts = strtotime($date);
        $dow = (int) date('N', $ts);
        $first = $times[0];
        $last = $times[count($times) - 1];
        $inMin = $this->toMinutes($first);
        $outMin = $this->toMinutes($last);

        $row = [
            'pid' => $pid, 'nombre' => $name, 'area' => $area, 'fecha' => $date, 'dow' => $dow,
            'festivo' => $holiday, 'entrada' => $first, 'salida' => $last,
            'tipo' => 'normal', 'inconsistencia' => null,
            'tarde' => 0, 'temprano' => 0, 'salidaTemprano' => 0, 'salidaTarde' => 0,
            'favor' => 0, 'contra' => 0,
        ];

        if (count($times) < 2 || ($outMin - $inMin) < self::MIN_SPAN_MINUTES) {
            $row['tipo'] = 'inconsistente';
            $row['inconsistencia'] = $inMin < 720 ? 'sin_salida' : 'sin_entrada';
            if ($inMin < 720) {
                $row['salida'] = null;
            } else {
                $row['entrada'] = null;
                $row['salida'] = $first;
            }

            return $row;
        }

        if ($dow >= 6 || $holiday !== null) {
            $row['tipo'] = 'festivo';
            $row['favor'] = $outMin - $inMin;

            return $row;
        }

        if ($inMin > self::START_MINUTES + self::LATE_TOLERANCE) {
            $row['tarde'] = $inMin - self::START_MINUTES;
        } elseif ($inMin < self::START_MINUTES) {
            $row['temprano'] = self::START_MINUTES - $inMin;
        }

        $endMin = $dow === 1 ? self::END_MONDAY : self::END_OTHER;
        if ($outMin < $endMin) {
            $row['salidaTemprano'] = $endMin - $outMin;
        } elseif ($outMin > $endMin) {
            $row['salidaTarde'] = $outMin - $endMin;
        }

        $row['favor'] = $row['temprano'] + $row['salidaTarde'];
        $row['contra'] = $row['tarde'] + $row['salidaTemprano'];

        return $row;
    }

    private function toMinutes(string $time): int
    {
        return ((int) substr($time, 0, 2)) * 60 + (int) substr($time, 3, 2);
    }

    private function validDate(?string $value): ?string
    {
        return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false ? $value : null;
    }

    /** Relaciona cada person_id con su área por coincidencia de nombre en usuarios activos. */
    private function areaByPerson(?array $visiblePersonIds = null): array
    {
        $users = [];
        $stmt = $this->db->query("
            SELECT u.Nombres, u.Apellidos, COALESCE(NULLIF(u.Area, ''), a.Nombre) AS area
            FROM gcp_usuarios u
            INNER JOIN gcp_agencias a ON a.Id = u.AgenciaId
            WHERE u.Estado = 1
        ");
        foreach ($stmt as $row) {
            $tokens = $this->tokens(trim($row['Nombres'] . ' ' . $row['Apellidos']));
            if ($tokens) {
                $users[] = [$tokens, $row['area']];
            }
        }

        $result = [];
        $params = [];
        $people = $this->db->prepare(
            'SELECT DISTINCT person_id, nombre FROM control_asistencia WHERE 1 = 1'
            . $this->personFilter($visiblePersonIds, $params)
        );
        $people->execute($params);
        foreach ($people as $person) {
            $tokens = $this->tokens($person['nombre']);
            $best = null;
            $bestScore = 0;
            foreach ($users as [$userTokens, $area]) {
                $common = count(array_intersect($tokens, $userTokens));
                $needed = min(count($tokens), count($userTokens));
                if ($common >= 2 && $common === $needed && $common > $bestScore) {
                    $best = $area;
                    $bestScore = $common;
                }
            }
            if ($best !== null) {
                $result[$person['person_id']] = $best;
            }
        }

        return $result;
    }

    private function attendanceIdsForUsers(array $targetUserIds): array
    {
        if ($targetUserIds === []) {
            return [];
        }

        $stmt = $this->db->query('SELECT Id, Nombres, Apellidos FROM gcp_usuarios WHERE Estado = 1');
        $usersById = [];
        foreach ($stmt as $user) {
            $usersById[(int) $user['Id']] = $this->tokens(trim($user['Nombres'] . ' ' . $user['Apellidos']));
        }

        $targetUserIds = array_fill_keys($targetUserIds, true);
        $attendees = $this->db->query('SELECT DISTINCT person_id, nombre FROM control_asistencia');
        $userIdsByPerson = [];
        $unmatchedPeople = [];
        foreach ($attendees as $attendee) {
            $personId = (string) $attendee['person_id'];
            $matchedUserId = $this->matchAttendanceUserId((string) $attendee['nombre'], $usersById);
            if ($matchedUserId === null) {
                $unmatchedPeople[$personId] = true;
                continue;
            }

            $userIdsByPerson[$personId][$matchedUserId] = true;
        }

        $visiblePersonIds = [];
        foreach ($userIdsByPerson as $personId => $matchedUserIds) {
            if (isset($unmatchedPeople[$personId]) || count($matchedUserIds) !== 1) {
                continue;
            }
            $matchedUserId = (int) array_key_first($matchedUserIds);
            if (isset($targetUserIds[$matchedUserId])) {
                $visiblePersonIds[] = (string) $personId;
            }
        }

        return $visiblePersonIds;
    }

    private function matchAttendanceUserId(string $name, array $usersById): ?int
    {
        $hasReplacementCharacter = str_contains($name, "\u{FFFD}");
        $tokens = $this->tokens(str_replace("\u{FFFD}", '', $name));
        if (count($tokens) < 2) {
            return null;
        }

        $matches = [];
        foreach ($usersById as $userId => $userTokens) {
            $used = [];
            $common = 0;
            $approximateMatches = 0;
            foreach ($tokens as $token) {
                $matchedIndex = null;
                foreach ($userTokens as $index => $userToken) {
                    if (!isset($used[$index]) && $token === $userToken) {
                        $matchedIndex = $index;
                        break;
                    }
                }

                if ($matchedIndex === null && $hasReplacementCharacter && $approximateMatches === 0) {
                    foreach ($userTokens as $index => $userToken) {
                        if (
                            !isset($used[$index])
                            && strlen($token) >= 4
                            && strlen($userToken) >= 4
                            && levenshtein($token, $userToken) === 1
                        ) {
                            $matchedIndex = $index;
                            $approximateMatches++;
                            break;
                        }
                    }
                }

                if ($matchedIndex !== null) {
                    $used[$matchedIndex] = true;
                    $common++;
                }
            }

            $needed = min(count($tokens), count($userTokens));
            if ($common >= 2 && $common === $needed) {
                $matches[(int) $userId] = max($matches[(int) $userId] ?? 0, $common);
            }
        }

        if ($matches === []) {
            return null;
        }

        $bestScore = max($matches);
        $bestUserIds = array_keys(array_filter($matches, static fn ($score) => $score === $bestScore));

        return count($bestUserIds) === 1 ? (int) $bestUserIds[0] : null;
    }

    private function canonicalUsers(): array
    {
        $namesById = [];
        $tokensById = [];
        $stmt = $this->db->query('SELECT Id, Nombres, Apellidos FROM gcp_usuarios WHERE Estado = 1');
        foreach ($stmt as $user) {
            $name = trim($user['Nombres'] . ' ' . $user['Apellidos']);
            if ($name === '' || str_contains($name, "\u{FFFD}")) {
                continue;
            }
            $userId = (int) $user['Id'];
            $namesById[$userId] = $name;
            $tokensById[$userId] = $this->tokens($name);
        }

        return ['namesById' => $namesById, 'tokensById' => $tokensById];
    }

    private function personFilter(?array $personIds, array &$params): string
    {
        if ($personIds === null) {
            return '';
        }
        if ($personIds === []) {
            return ' AND 1 = 0';
        }

        $placeholders = [];
        foreach (array_values(array_unique($personIds)) as $index => $personId) {
            $placeholder = ':person' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $personId;
        }

        return ' AND person_id IN (' . implode(', ', $placeholders) . ')';
    }

    private function attendanceNameKey(string $name): string
    {
        if (str_contains($name, "\u{FFFD}")) {
            return '';
        }

        $tokens = $this->tokens($name);
        sort($tokens);

        return implode(' ', $tokens);
    }

    private function tokens(string $name): array
    {
        $name = strtr(strtolower($name), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n',
        ]);
        $name = preg_replace('/[^a-z ]+/', ' ', $name);
        $stop = ['de', 'del', 'la', 'las', 'los'];

        return array_values(array_unique(array_filter(
            preg_split('/\s+/', trim($name)),
            static fn ($t) => $t !== '' && !in_array($t, $stop, true)
        )));
    }

    /** @return array<string,string> fecha => nombre del festivo */
    private function holidaysBetween(string $from, string $to): array
    {
        $holidays = [];
        for ($year = (int) date('Y', strtotime($from)); $year <= (int) date('Y', strtotime($to)); $year++) {
            $holidays += $this->holidaysOfYear($year);
        }

        return $holidays;
    }

    private function holidaysOfYear(int $year): array
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;
        $easter = mktime(0, 0, 0, $month, $day, $year);

        $fmt = static fn (int $ts): string => date('Y-m-d', $ts);
        $toMonday = static function (int $ts): int {
            $dow = (int) date('N', $ts);

            return $dow === 1 ? $ts : strtotime('+' . (8 - $dow) . ' days', $ts);
        };
        $fixed = static fn (int $m, int $d): int => mktime(0, 0, 0, $m, $d, $year);

        $list = [
            $fmt($fixed(1, 1)) => 'Año Nuevo',
            $fmt($toMonday($fixed(1, 6))) => 'Reyes Magos',
            $fmt($toMonday($fixed(3, 19))) => 'San José',
            $fmt(strtotime('-3 days', $easter)) => 'Jueves Santo',
            $fmt(strtotime('-2 days', $easter)) => 'Viernes Santo',
            $fmt($fixed(5, 1)) => 'Día del Trabajo',
            $fmt($toMonday(strtotime('+39 days', $easter))) => 'Ascensión del Señor',
            $fmt($toMonday(strtotime('+60 days', $easter))) => 'Corpus Christi',
            $fmt($toMonday(strtotime('+68 days', $easter))) => 'Sagrado Corazón',
            $fmt($toMonday($fixed(6, 29))) => 'San Pedro y San Pablo',
            $fmt($fixed(7, 20)) => 'Día de la Independencia',
            $fmt($fixed(8, 7)) => 'Batalla de Boyacá',
            $fmt($toMonday($fixed(8, 15))) => 'Asunción de la Virgen',
            $fmt($toMonday($fixed(10, 12))) => 'Día de la Raza',
            $fmt($toMonday($fixed(11, 1))) => 'Todos los Santos',
            $fmt($toMonday($fixed(11, 11))) => 'Independencia de Cartagena',
            $fmt($fixed(12, 8)) => 'Inmaculada Concepción',
            $fmt($fixed(12, 25)) => 'Navidad',
        ];

        return $list;
    }
}
