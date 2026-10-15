<?php

namespace App\Controllers\Gestion;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\Gestion\MantenimientoService;

class MantenimientoController
{
    private MantenimientoService $service;

    public function __construct()
    {
        $this->service = new MantenimientoService();
    }

    public function pending(): void
    {
        Response::success($this->service->getPending(), 'Mantenimientos semestrales pendientes obtenidos.');
    }

    public function complete(int $assetId, int $year, int $semester): void
    {
        $user = Auth::user();
        $result = $this->service->complete($assetId, $year, $semester, Request::json(), (int) ($user['id'] ?? 0));
        if ($result['success']) {
            Response::success(null, $result['message']);
            return;
        }
        Response::error($result['message']);
    }
}
