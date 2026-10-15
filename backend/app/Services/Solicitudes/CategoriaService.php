<?php

namespace App\Services\Solicitudes;

use App\Repositories\Solicitudes\CategoriaRepository;

class CategoriaService
{
    private CategoriaRepository $repository;

    public function __construct()
    {
        $this->repository = new CategoriaRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getActive(): array
    {
        return $this->repository->getActive();
    }

    public function getById(int $id): ?array
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): array
    {
        $id = $this->repository->create($data);

        return [
            'success' => true,
            'message' => 'Categoría creada correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $categoria = $this->repository->getById($id);

        if (!$categoria) {
            return [
                'success' => false,
                'message' => 'La categoría no existe.'
            ];
        }

        $this->repository->update($id, $data);

        return [
            'success' => true,
            'message' => 'Categoría actualizada correctamente.'
        ];
    }

    public function changeStatus(int $id, bool $status): array
    {
        $categoria = $this->repository->getById($id);

        if (!$categoria) {
            return [
                'success' => false,
                'message' => 'La categoría no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado de la categoría actualizado correctamente.'
        ];
    }
}