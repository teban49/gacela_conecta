<?php

namespace App\Controllers\Security;

use App\Core\Request;
use App\Core\Response;
use App\Services\Security\PermissionService;

class PermissionController
{
    private PermissionService $service;

    public function __construct()
    {
        $this->service = new PermissionService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Permisos obtenidos correctamente.');
    }

    public function byApplication(int $applicationId): void
    {
        Response::success($this->service->getByApplication($applicationId), 'Permisos por aplicación consultados correctamente.');
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
}
