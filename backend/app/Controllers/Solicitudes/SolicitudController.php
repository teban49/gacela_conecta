<?php

namespace App\Controllers\Solicitudes;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\Solicitudes\SolicitudService;

class SolicitudController
{
    private SolicitudService $service;

    public function __construct()
    {
        $this->service = new SolicitudService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Solicitudes obtenidas correctamente.');
    }

    public function byStatus(string $estado): void
    {
        Response::success($this->service->getByStatus($estado), 'Solicitudes por estado obtenidas correctamente.');
    }

    public function byAssignee(int $usuarioId): void
    {
        Response::success($this->service->getByAssignee($usuarioId), 'Solicitudes asignadas obtenidas correctamente.');
    }

    public function byRequester(int $usuarioId): void
    {
        Response::success($this->service->getByRequester($usuarioId), 'Solicitudes del usuario obtenidas correctamente.');
    }

    public function show(int $id): void
    {
        $solicitud = $this->service->getById($id);

        if (!$solicitud) {
            Response::notFound('Solicitud no encontrada.');
            return;
        }

        Response::success($solicitud, 'Solicitud obtenida correctamente.');
    }

    public function store(): void
    {
        $data = Request::json();
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            Response::unauthorized('Usuario no autenticado');
            return;
        }
        $data['SolicitanteId'] = $userId;

        $result = $this->service->create($data);

        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
        } elseif (!empty($result['errors'])) {
            Response::validationError($result['errors'], $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function update(int $id): void
    {
        $data = Request::json();
        $result = $this->service->update($id, $data);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function changeStatus(int $id): void
    {
        $data = Request::json();
        $estado = $data['Estado'] ?? 'nueva';
        $result = $this->service->changeStatus($id, $estado);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function assignTo(int $id): void
    {
        $data = Request::json();
        $usuarioId = $data['UsuarioId'] ?? 0;
        $result = $this->service->assignTo($id, $usuarioId);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}