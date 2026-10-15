<?php

namespace App\Controllers\Security;

use App\Core\Request;
use App\Core\Response;
use App\Services\Security\RoleService;

class RoleController
{
    private RoleService $service;

    public function __construct()
    {
        $this->service = new RoleService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Roles obtenidos correctamente.');
    }

    public function show(int $id): void
    {
        $role = $this->service->getByIdWithPermissions($id);

        if (!$role) {
            Response::notFound('Rol no encontrado.');
            return;
        }

        Response::success($role, 'Rol obtenido correctamente.');
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

    public function syncPermissions(int $id): void
    {
        $data = Request::json();
        $permissionIds = array_map('intval', $data['Permisos'] ?? []);
        $result = $this->service->syncPermissions($id, $permissionIds);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}
