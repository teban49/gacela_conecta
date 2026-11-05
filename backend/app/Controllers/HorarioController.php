<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;
use App\Core\RoleAccess;
use App\Services\HorarioService;

class HorarioController
{
    public function report(): void
    {
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            Response::unauthorized('Usuario no autenticado.');
            return;
        }
        if (!HorarioService::canView($userId, RoleAccess::roleCodes($user))) {
            Response::forbidden('No tienes permiso para consultar el control de horario.');
            return;
        }

        $report = (new HorarioService())->report($_GET['desde'] ?? null, $_GET['hasta'] ?? null);
        Response::success($report, 'Informe de horario obtenido correctamente.');
    }
}
