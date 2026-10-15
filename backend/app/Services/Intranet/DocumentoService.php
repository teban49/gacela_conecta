<?php

namespace App\Services\Intranet;

use App\Repositories\Intranet\DocumentoRepository;

class DocumentoService
{
    private DocumentoRepository $repository;

    public function __construct()
    {
        $this->repository = new DocumentoRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getByCategory(string $categoria): array
    {
        return $this->repository->getByCategory($categoria);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->getById($id);
    }

    public function incrementDownloads(int $id): array
    {
        $this->repository->incrementDownloads($id);

        return [
            'success' => true,
            'message' => 'Contador de descargas actualizado.'
        ];
    }

    public function create(array $data): array
    {
        $id = $this->repository->create($data);

        return [
            'success' => true,
            'message' => 'Documento creado correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $documento = $this->repository->getById($id);

        if (!$documento) {
            return [
                'success' => false,
                'message' => 'El documento no existe.'
            ];
        }

        $this->repository->update($id, $data);

        return [
            'success' => true,
            'message' => 'Documento actualizado correctamente.'
        ];
    }

    public function changeStatus(int $id, string $status): array
    {
        $documento = $this->repository->getById($id);

        if (!$documento) {
            return [
                'success' => false,
                'message' => 'El documento no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado del documento actualizado correctamente.'
        ];
    }
}