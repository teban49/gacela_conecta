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

    public function report(?string $from, ?string $to): array
    {
        $range = $this->db->query('SELECT DATE(MIN(fecha_hora)) a, DATE(MAX(fecha_hora)) b FROM control_asistencia')->fetch(PDO::FETCH_ASSOC);
        $minDate = $range['a'] ?? null;
        $maxDate = $range['b'] ?? null;
        if (!$minDate) {
            return ['desde' => null, 'hasta' => null, 'minDate' => null, 'maxDate' => null, 'areas' => [], 'dias' => [], 'ausencias' => []];
        }

        $from = $this->validDate($from) ?? date('Y-m-01', strtotime($maxDate));
        $to = $this->validDate($to) ?? $maxDate;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $areaByPerson = $this->areaByPerson();

        $firstSeen = [];
        foreach ($this->db->query('SELECT person_id, DATE(MIN(fecha_hora)) d FROM control_asistencia GROUP BY person_id') as $row) {
            $firstSeen[$row['person_id']] = $row['d'];
        }

        $stmt = $this->db->prepare(
            'SELECT person_id, nombre, fecha_hora FROM control_asistencia
             WHERE fecha_hora >= :from AND fecha_hora < DATE_ADD(:to, INTERVAL 1 DAY)
             ORDER BY person_id, fecha_hora'
        );
        $stmt->execute([':from' => $from, ':to' => $to]);

        $grouped = [];
        $names = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $date = substr($row['fecha_hora'], 0, 10);
            $grouped[$row['person_id']][$date][] = substr($row['fecha_hora'], 11, 5);
            $names[$row['person_id']] = $row['nombre'];
        }

        $holidays = $this->holidaysBetween($from, $to);
        $dias = [];
        $areas = [];

        foreach ($grouped as $pid => $byDate) {
            $area = $areaByPerson[$pid] ?? 'Sin área';
            $areas[$area] = true;
            foreach ($byDate as $date => $times) {
                $dias[] = $this->evaluateDay((string) $pid, $names[$pid], $area, $date, $times, $holidays[$date] ?? null);
            }
        }

        $ausencias = [];
        $today = date('Y-m-d');
        foreach ($names as $pid => $name) {
            $area = $areaByPerson[$pid] ?? 'Sin área';
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

    /** Relaciona cada person_id con su área por coincidencia de nombre en el directorio. */
    private function areaByPerson(): array
    {
        $directory = [];
        foreach ($this->db->query('SELECT first_name, last_name, area_agencia FROM directorio_gacela_conecta WHERE area_agencia IS NOT NULL') as $row) {
            $tokens = $this->tokens(trim($row['first_name'] . ' ' . $row['last_name']));
            if ($tokens) {
                $directory[] = [$tokens, $row['area_agencia']];
            }
        }

        $result = [];
        foreach ($this->db->query('SELECT DISTINCT person_id, nombre FROM control_asistencia') as $person) {
            $tokens = $this->tokens($person['nombre']);
            $best = null;
            $bestScore = 0;
            foreach ($directory as [$dirTokens, $area]) {
                $common = count(array_intersect($tokens, $dirTokens));
                $needed = min(count($tokens), count($dirTokens));
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
