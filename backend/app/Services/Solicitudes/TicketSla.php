<?php

namespace App\Services\Solicitudes;

use DateTimeImmutable;
use InvalidArgumentException;

final class TicketSla
{
    private const PRIORITY_DAYS = [
        'media' => 21,
        'alta' => 14,
        'critica' => 7,
    ];

    public static function deadlineForPriority(string $priority, ?string $baseDate = null): string
    {
        $base = $baseDate === null ? new DateTimeImmutable() : new DateTimeImmutable($baseDate);
        if ($priority === 'baja') {
            $nextMonth = $base->modify('first day of next month');
            $day = min((int) $base->format('d'), (int) $nextMonth->format('t'));
            return $nextMonth
                ->setDate((int) $nextMonth->format('Y'), (int) $nextMonth->format('m'), $day)
                ->format('Y-m-d H:i:s');
        }

        $days = self::PRIORITY_DAYS[$priority] ?? null;
        if ($days === null) {
            throw new InvalidArgumentException('La prioridad del ticket no tiene un tiempo de respuesta configurado.');
        }

        return $base->modify("+{$days} days")->format('Y-m-d H:i:s');
    }
}
