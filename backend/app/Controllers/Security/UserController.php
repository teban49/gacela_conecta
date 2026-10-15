<?php

namespace App\Controllers\Security;

use App\Core\Request;
use App\Core\Response;
use App\Services\Security\UserService;

class UserController
{
    private UserService $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Usuarios obtenidos correctamente.');
    }

    public function jobTitles(): void
    {
        Response::success($this->service->getActiveJobTitles(), 'Cargos activos obtenidos correctamente.');
    }

    public function show(int $id): void
    {
        $user = $this->service->getById($id);

        if (!$user) {
            Response::notFound('Usuario no encontrado.');
            return;
        }

        $user['roles'] = $this->service->getUserRoles($id);

        Response::success($user, 'Usuario obtenido correctamente.');
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

    public function setRole(int $id): void
    {
        $data = Request::json();
        $roleId = filter_var($data['RolId'] ?? null, FILTER_VALIDATE_INT);
        if ($roleId === false || $roleId < 1) {
            Response::validationError(['RolId' => ['Selecciona un rol válido.']]);
            return;
        }
        $result = $this->service->setRole($id, $roleId);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function permissions(int $id): void
    {
        $user = $this->service->getById($id);

        if (!$user) {
            Response::notFound('Usuario no encontrado.');
            return;
        }

        $permissions = $this->service->getUserPermissions($id);

        Response::success($permissions, 'Permisos del usuario obtenidos correctamente.');
    }
}
