<?php

namespace App\Services\Security;

use App\Repositories\Security\PermissionRepository;

class PermissionService
{
    private PermissionRepository $repository;

    public function __construct()
    {
        $this->repository = new PermissionRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getByApplication(int $applicationId): array
    {
        return $this->repository->getByApplication($applicationId);
    }

    public function create(array $data): array
    {
        if ($this->repository->getByCode($data['CodigoUnico'] ?? '')) {
            return [
                'success' => false,
                'message' => 'Ya existe ese permiso con ese código único.'
            ];
        }

        if (empty($data['AplicacionId'])) {
            return [
                'success' => false,
                'message' => 'El ID de aplicación es requerido.'
            ];
        }

        $id = $this->repository->create($data);

        return [
            'success' => true,
            'message' => 'Permiso creado correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $permission = $this->repository->getById($id);

        if (!$permission) {
            return [
                'success' => false,
                'message' => 'El permiso no existe.'
            ];
        }

        $this->repository->update($id, $data);

        return [
            'success' => true,
            'message' => 'Permiso actualizado correctamente.'
        ];
    }

    public function changeStatus(int $id, bool $status): array
    {
        $permission = $this->repository->getById($id);

        if (!$permission) {
            return [
                'success' => false,
                'message' => 'El permiso no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado del permiso actualizado correctamente.'
        ];
    }
}
