<?php

namespace App\Services\Intranet;

use App\Repositories\Intranet\NoticiaRepository;

class NoticiaService
{
    private NoticiaRepository $repository;

    public function __construct()
    {
        $this->repository = new NoticiaRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getPublished(): array
    {
        return $this->repository->getPublished();
    }

    public function getByCategory(string $categoria): array
    {
        return $this->repository->getByCategory($categoria);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->getById($id);
    }

    public function markAsViewed(int $noticiaId, int $usuarioId): array
    {
        $this->repository->markAsViewed($noticiaId, $usuarioId);

        return [
            'success' => true,
            'message' => 'Noticia marcada como vista.'
        ];
    }

    public function create(array $data): array
    {
        $id = $this->repository->create($data);

        return [
            'success' => true,
            'message' => 'Noticia creada correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $noticia = $this->repository->getById($id);

        if (!$noticia) {
            return [
                'success' => false,
                'message' => 'La noticia no existe.'
            ];
        }

        $this->repository->update($id, $data);

        return [
            'success' => true,
            'message' => 'Noticia actualizada correctamente.'
        ];
    }

    public function changeStatus(int $id, string $status): array
    {
        $noticia = $this->repository->getById($id);

        if (!$noticia) {
            return [
                'success' => false,
                'message' => 'La noticia no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado de la noticia actualizado correctamente.'
        ];
    }
}