<?php

namespace App\Services\Security;

use App\Repositories\Security\UserRepository;

class UserService
{
    private UserRepository $repository;

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getActiveJobTitles(): array
    {
        return $this->repository->getActiveJobTitles();
    }

    public function getById(int $id): ?array
    {
        return $this->repository->getById($id);
    }

    public function getUserRoles(int $id): array
    {
        return $this->repository->getUserRoles($id);
    }

    public function create(array $data): array
    {
        try {
            $id = $this->repository->create($data);
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $user = $this->repository->getById($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'El usuario no existe.'
            ];
        }

        try {
            $this->repository->update($id, $data);
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Usuario actualizado correctamente.'
        ];
    }

    public function changeStatus(int $id, bool $status): array
    {
        $user = $this->repository->getById($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'El usuario no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado del usuario actualizado correctamente.'
        ];
    }

    public function setRole(int $id, int $roleId): array
    {
        $user = $this->repository->getById($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'El usuario no existe.'
            ];
        }

        $updated = $this->repository->setRole($id, $roleId);

        return [
            'success' => $updated,
            'message' => $updated ? 'Rol del usuario actualizado correctamente.' : 'No fue posible actualizar el rol. Verifica que el rol esté activo.'
        ];
    }

    public function getUserPermissions(int $userId): array
    {
        return $this->repository->getUserPermissions($userId);
    }
}
