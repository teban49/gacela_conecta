<?php

namespace App\Services\Security;

use App\Services\BaseService;
use App\Repositories\Security\ApplicationRepository;

class ApplicationService extends BaseService
{
    private ApplicationRepository $repository;

    public function __construct()
    {
        $this->repository = new ApplicationRepository();
    }

    protected function getRepository(): ApplicationRepository
    {
        return $this->repository;
    }

    protected function getCreateSuccessMessage(): string
    {
        return 'Aplicación creada correctamente.';
    }

    protected function getUpdateSuccessMessage(): string
    {
        return 'Aplicación actualizada correctamente.';
    }

    protected function getNotFoundMessage(): string
    {
        return 'La aplicación no existe.';
    }

    protected function getStatusChangeMessage(bool $status): string
    {
        return $status ? 'Aplicación activada correctamente.' : 'Aplicación desactivada correctamente.';
    }

    public function changeVisibility(int $id, bool $visible): array
    {
        $application = $this->repository->getById($id);

        if (!$application) {
            return [
                'success' => false,
                'message' => $this->getNotFoundMessage()
            ];
        }

        $this->repository->changeVisibility($id, $visible);

        return [
            'success' => true,
            'message' => $visible ? 'Aplicación visible en menú.' : 'Aplicación oculta del menú.'
        ];
    }
}
