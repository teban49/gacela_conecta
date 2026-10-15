<?php

namespace App\Controllers\Intranet;

use App\Core\Request;
use App\Core\Response;
use App\Services\Intranet\EventoService;

class EventoController
{
    private EventoService $service;

    public function __construct()
    {
        $this->service = new EventoService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Eventos obtenidos correctamente.');
    }

    public function byDateRange(): void
    {
        $data = Request::json();
        $fechaInicio = $data['FechaInicio'] ?? date('Y-m-01');
        $fechaFin = $data['FechaFin'] ?? date('Y-m-t');
        
        Response::success($this->service->getByDateRange($fechaInicio, $fechaFin), 'Eventos por rango de fechas obtenidos correctamente.');
    }

    public function upcoming(): void
    {
        $data = Request::json();
        $limit = $data['limit'] ?? 10;
        
        Response::success($this->service->getUpcoming($limit), 'Próximos eventos obtenidos correctamente.');
    }

    public function show(int $id): void
    {
        $evento = $this->service->getById($id);

        if (!$evento) {
            Response::notFound('Evento no encontrado.');
            return;
        }

        Response::success($evento, 'Evento obtenido correctamente.');
    }

    public function store(): void
    {
        $data = Request::json();
        $result = $this->service->create($data);

        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
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
        $status = $data['Estado'] ?? 'planeado';
        $result = $this->service->changeStatus($id, $status);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}