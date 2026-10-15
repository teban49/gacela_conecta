<?php

namespace App\Controllers\Gestion;

use App\Core\Request;
use App\Core\Response;
use App\Services\Gestion\LicenciaService;

class LicenciaController
{
    private LicenciaService $service;

    public function __construct()
    {
        $this->service = new LicenciaService();
    }

    public function index(): void
    {
        Response::success($this->service->getActive(), 'Licencias activas obtenidas correctamente.');
    }

    public function options(): void
    {
        Response::success($this->service->getOptions(), 'Opciones de licencias obtenidas correctamente.');
    }

    public function store(): void
    {
        $result = $this->service->create(Request::json());
        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
            return;
        }
        Response::validationError($result['errors'], $result['message']);
    }

    public function update(int $id): void
    {
        $result = $this->service->update($id, Request::json());
        if (!$result['success']) {
            if (!empty($result['errors'])) {
                Response::validationError($result['errors'], $result['message']);
                return;
            }
            Response::error($result['message'], [], $result['status'] ?? 400);
            return;
        }
        Response::success($result['data'] ?? null, $result['message']);
    }
}
