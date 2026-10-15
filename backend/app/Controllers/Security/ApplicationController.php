<?php

namespace App\Controllers\Security;

use App\Core\Request;
use App\Core\Response;
use App\Services\Security\ApplicationService;

class ApplicationController
{
    private ApplicationService $service;

    public function __construct()
    {
        $this->service = new ApplicationService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Aplicaciones obtenidas correctamente.');
    }

    public function show(int $id): void
    {
        $application = $this->service->getById($id);

        if (!$application) {
            Response::notFound('Aplicación no encontrada.');
            return;
        }

        Response::success($application, 'Aplicación obtenida correctamente.');
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
        $status = (bool) ($data['Estado'] ?? false);
        $result = $this->service->changeStatus($id, $status);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function changeVisibility(int $id): void
    {
        $data = Request::json();
        $visible = (bool) ($data['Visible'] ?? false);
        $result = $this->service->changeVisibility($id, $visible);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}
