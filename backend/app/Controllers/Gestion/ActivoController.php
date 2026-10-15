<?php

namespace App\Controllers\Gestion;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\Gestion\ActivoService;

class ActivoController
{
    private ActivoService $service;

    public function __construct()
    {
        $this->service = new ActivoService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Activos obtenidos correctamente.');
    }

    public function assignableUsers(): void
    {
        Response::success($this->service->getAssignableUsers(), 'Usuarios activos obtenidos correctamente.');
    }

    public function pendingAssignments(): void
    {
        $user = Auth::user();
        Response::success(
            $this->service->getPendingAssignmentsForUser((int) ($user['id'] ?? 0)),
            'Solicitudes de asignación pendientes obtenidas correctamente.'
        );
    }

    public function respondToAssignment(int $assignmentId, bool $accept): void
    {
        $user = Auth::user();
        $result = $this->service->respondToAssignment(
            $assignmentId,
            (int) ($user['id'] ?? 0),
            $accept
        );
        if ($result['success']) {
            Response::success(null, $result['message']);
            return;
        }
        Response::error($result['message']);
    }

    public function categories(): void
    {
        Response::success($this->service->getActiveCategories(), 'Categorías de activos obtenidas correctamente.');
    }

    public function companies(): void
    {
        Response::success($this->service->getActiveCompanies(), 'Empresas activas obtenidas correctamente.');
    }

    public function suppliers(): void
    {
        Response::success($this->service->getActiveSuppliers(), 'Proveedores activos obtenidos correctamente.');
    }

    public function storeSupplier(): void
    {
        $result = $this->service->createSupplier(Request::json());
        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
            return;
        }
        Response::validationError(['Nombre' => [$result['message']]], 'Revisa el nombre del proveedor.');
    }

    public function byCategory(int $categoriaId): void
    {
        Response::success($this->service->getByCategory($categoriaId), 'Activos por categoría obtenidos correctamente.');
    }

    public function byAssignee(int $usuarioId): void
    {
        Response::success($this->service->getByAssignee($usuarioId), 'Activos asignados obtenidos correctamente.');
    }

    public function show(int $id): void
    {
        $activo = $this->service->getById($id);

        if (!$activo) {
            Response::notFound('Activo no encontrado.');
            return;
        }

        Response::success($activo, 'Activo obtenido correctamente.');
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

    public function changeAssetStatus(int $id): void
    {
        $data = Request::json();
        $estadoActivo = $data['EstadoActivo'] ?? 'nuevo';
        $result = $this->service->changeAssetStatus($id, $estadoActivo);

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
        $user = Auth::user();
        $result = $this->service->assignTo($id, (int) $usuarioId, (int) ($user['id'] ?? 0));

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function unassign(int $id): void
    {
        $result = $this->service->unassign($id);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function retire(int $id): void
    {
        $result = $this->service->retire($id);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}