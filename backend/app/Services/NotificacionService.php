<?php

namespace App\Services;

use App\Repositories\Gestion\ActivoRepository;
use App\Repositories\NotificacionRepository;
use App\Repositories\Solicitudes\SolicitudLaboralRepository;

/**
 * Reúne en un solo listado lo que requiere atención del usuario:
 * - Pendientes calculados en vivo (aprobaciones, nómina, activos e incidencias). Desaparecen al resolverse.
 * - Novedades persistidas (notificaciones) que el usuario puede marcar como leídas.
 */
class NotificacionService
{
    private NotificacionRepository $repository;

    public function __construct()
    {
        $this->repository = new NotificacionRepository();
    }

    public function getForUser(int $userId, array $roles): array
    {
        $items = [];

        $solicitudes = new SolicitudLaboralRepository();
        $pending = $solicitudes->getPending($userId, in_array('rrhh', $roles, true), in_array('gerencia', $roles, true));
        foreach ($pending as $request) {
            $items[] = [
                'Clave' => 'aprobacion-' . $request['Id'],
                'Tipo' => 'pendiente',
                'Titulo' => 'Solicitud por aprobar',
                'Mensaje' => trim("{$request['SolicitanteNombres']} {$request['SolicitanteApellidos']}") . ' · ' . $request['TipoNombre'],
                'Enlace' => '/solicitudes?tab=pending&id=' . $request['Id'],
                'Leida' => 0,
                'Persistente' => false,
                'Fecha' => $request['FechaActualizacion'] ?? $request['FechaCreacion'],
            ];
        }

        $payrollRole = $solicitudes->getPayrollRole($userId);
        if ($payrollRole !== null) {
            foreach ($solicitudes->getPayrollQueue($payrollRole) as $request) {
                $items[] = [
                    'Clave' => 'nomina-' . $request['Id'],
                    'Tipo' => 'pendiente',
                    'Titulo' => $this->payrollTitle($request['EstadoNomina']),
                    'Mensaje' => trim("{$request['SolicitanteNombres']} {$request['SolicitanteApellidos']}") . ' · ' . $request['TipoNombre'],
                    'Enlace' => '/solicitudes?tab=payroll&id=' . $request['Id'],
                    'Leida' => 0,
                    'Persistente' => false,
                    'Fecha' => $request['FechaActualizacion'],
                ];
            }
        }

        foreach ((new ActivoRepository())->getPendingAssignmentsForUser($userId) as $assignment) {
            $items[] = [
                'Clave' => 'activo-' . $assignment['AsignacionId'],
                'Tipo' => 'pendiente',
                'Titulo' => 'Activo pendiente de tu aceptación',
                'Mensaje' => trim("{$assignment['TipoActivo']} · {$assignment['CodigoInventario']}"),
                'Enlace' => '/gestion',
                'Leida' => 0,
                'Persistente' => false,
                'Fecha' => $assignment['FechaSolicitud'],
            ];
        }

        foreach ($this->repository->getTicketsNeedingAttention($userId) as $ticket) {
            $mustConfirm = $ticket['Estado'] === 'pendiente' && (int) $ticket['SolicitanteId'] === $userId;
            $items[] = [
                'Clave' => 'ticket-' . $ticket['Id'],
                'Tipo' => 'pendiente',
                'Titulo' => $mustConfirm ? 'Confirma el cierre de tu incidencia' : 'Incidencia nueva asignada',
                'Mensaje' => "{$ticket['Ticket']} · {$ticket['Titulo']}",
                'Enlace' => '/gestion',
                'Leida' => 0,
                'Persistente' => false,
                'Fecha' => $ticket['FechaActualizacion'],
            ];
        }

        foreach ($this->repository->getRecent($userId) as $notification) {
            $items[] = [
                'Id' => (int) $notification['Id'],
                'Clave' => 'notificacion-' . $notification['Id'],
                'Tipo' => $notification['Tipo'],
                'Titulo' => $notification['Titulo'],
                'Mensaje' => $notification['Mensaje'],
                'Enlace' => $notification['Enlace'],
                'Leida' => (int) $notification['Leida'],
                'Persistente' => true,
                'Fecha' => $notification['FechaCreacion'],
            ];
        }

        usort($items, static function (array $a, array $b): int {
            if ($a['Leida'] !== $b['Leida']) {
                return $a['Leida'] <=> $b['Leida'];
            }
            return strcmp((string) $b['Fecha'], (string) $a['Fecha']);
        });

        return [
            'Items' => $items,
            'NoLeidas' => count(array_filter($items, static fn (array $item): bool => $item['Leida'] === 0)),
        ];
    }

    public function markRead(int $userId, int $id): void
    {
        $this->repository->markRead($userId, $id);
    }

    public function markAllRead(int $userId): void
    {
        $this->repository->markAllRead($userId);
    }

    private function payrollTitle(string $state): string
    {
        return match ($state) {
            'Pendiente_Liquidacion' => 'Subir liquidación de nómina',
            'Con_Errores' => 'Liquidación con errores: sube la corrección',
            'Pendiente_Revision_Nomina' => 'Liquidación por revisar',
            'Pendiente_Pago' => 'Solicitud pendiente de pago',
            default => 'Solicitud de nómina',
        };
    }
}
