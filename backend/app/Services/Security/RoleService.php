<?php

namespace App\Services\Security;

use App\Repositories\Security\RoleRepository;

class RoleService
{
    private RoleRepository $repository;

    public function __construct()
    {
        $this->repository = new RoleRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getById(int $id): ?array
    {
        return $this->repository->getById($id);
    }

    public function getByIdWithPermissions(int $id): array
    {
        $role = $this->repository->getById($id);

        if (!$role) {
            return [];
        }

        return [
            'Id' => $role['Id'],
            'Nombre' => $role['Nombre'],
            'Codigo' => $role['Codigo'],
            'Descripcion' => $role['Descripcion'],
            'NivelAcceso' => $role['NivelAcceso'],
            'Estado' => $role['Estado'],
            'FechaCreacion' => $role['FechaCreacion'],
            'FechaActualizacion' => $role['FechaActualizacion'],
            'permissions' => $this->repository->getRolePermissions($id)
        ];
    }

    public function create(array $data): array
    {
        $id = $this->repository->create($data);

        return [
            'success' => true,
            'message' => 'Rol creado correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $role = $this->repository->getById($id);

        if (!$role) {
            return [
                'success' => false,
                'message' => 'El rol no existe.'
            ];
        }

        $this->repository->update($id, $data);

        return [
            'success' => true,
            'message' => 'Rol actualizado correctamente.'
        ];
    }

    public function changeStatus(int $id, bool $status): array
    {
        $role = $this->repository->getById($id);

        if (!$role) {
            return [
                'success' => false,
                'message' => 'El rol no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado del rol actualizado correctamente.'
        ];
    }

    public function syncPermissions(int $id, array $permissionIds): array
    {
        $role = $this->repository->getById($id);

        if (!$role) {
            return [
                'success' => false,
                'message' => 'El rol no existe.'
            ];
        }

        $updated = $this->repository->syncPermissions($id, $permissionIds);

        return [
            'success' => $updated,
            'message' => $updated ? 'Permisos sincronizados correctamente.' : 'No fue posible sincronizar los permisos.'
        ];
    }
}
