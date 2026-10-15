<?php

namespace App\Controllers\Solicitudes;

use App\Core\Request;
use App\Core\Response;
use App\Services\Solicitudes\CategoriaService;

class CategoriaController
{
    private CategoriaService $service;

    public function __construct()
    {
        $this->service = new CategoriaService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Categorías obtenidas correctamente.');
    }

    public function active(): void
    {
        Response::success($this->service->getActive(), 'Categorías activas obtenidas correctamente.');
    }

    public function show(int $id): void
    {
        $categoria = $this->service->getById($id);

        if (!$categoria) {
            Response::notFound('Categoría no encontrada.');
            return;
        }

        Response::success($categoria, 'Categoría obtenida correctamente.');
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
        $status = (bool) ($data['Estado'] ?? true);
        $result = $this->service->changeStatus($id, $status);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}