<?php

namespace App\Services\Intranet;

use App\Repositories\Intranet\EventoRepository;

class EventoService
{
    private EventoRepository $repository;

    public function __construct()
    {
        $this->repository = new EventoRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getByDateRange(string $fechaInicio, string $fechaFin): array
    {
        return $this->repository->getByDateRange($fechaInicio, $fechaFin);
    }

    public function getUpcoming(int $limit = 10): array
    {
        return $this->repository->getUpcoming($limit);
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
            'message' => 'Evento creado correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $evento = $this->repository->getById($id);

        if (!$evento) {
            return [
                'success' => false,
                'message' => 'El evento no existe.'
            ];
        }

        $this->repository->update($id, $data);

        return [
            'success' => true,
            'message' => 'Evento actualizado correctamente.'
        ];
    }

    public function changeStatus(int $id, string $status): array
    {
        $evento = $this->repository->getById($id);

        if (!$evento) {
            return [
                'success' => false,
                'message' => 'El evento no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado del evento actualizado correctamente.'
        ];
    }
}