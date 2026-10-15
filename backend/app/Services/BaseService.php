<?php

namespace App\Services;

abstract class BaseService
{
    abstract protected function getRepository();

    public function getAll(): array
    {
        return $this->getRepository()->getAll();
    }

    public function getById(int $id): ?array
    {
        return $this->getRepository()->getById($id);
    }

    public function create(array $data): array
    {
        $id = $this->getRepository()->create($data);

        return [
            'success' => true,
            'message' => $this->getCreateSuccessMessage(),
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            return [
                'success' => false,
                'message' => $this->getNotFoundMessage()
            ];
        }

        $this->getRepository()->update($id, $data);

        return [
            'success' => true,
            'message' => $this->getUpdateSuccessMessage()
        ];
    }

    public function changeStatus(int $id, bool $status): array
    {
        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            return [
                'success' => false,
                'message' => $this->getNotFoundMessage()
            ];
        }

        $this->getRepository()->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => $this->getStatusChangeMessage($status)
        ];
    }

    protected function getCreateSuccessMessage(): string
    {
        return 'Registro creado correctamente.';
    }

    protected function getUpdateSuccessMessage(): string
    {
        return 'Registro actualizado correctamente.';
    }

    protected function getNotFoundMessage(): string
    {
        return 'El registro no existe.';
    }

    protected function getStatusChangeMessage(bool $status): string
    {
        return $status ? 'Registro activado correctamente.' : 'Registro desactivado correctamente.';
    }
}