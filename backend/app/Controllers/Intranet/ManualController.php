<?php

namespace App\Controllers\Intranet;

use App\Core\Response;
use App\Services\Intranet\ManualService;

class ManualController
{
    private ManualService $service;

    public function __construct()
    {
        $this->service = new ManualService();
    }

    public function store(): void
    {
        $this->respond($this->service->create($_POST, $_FILES['Archivo'] ?? null), true);
    }

    public function update(string $id): void
    {
        $this->respond($this->service->update(rawurldecode($id), $_POST, $_FILES['Archivo'] ?? null));
    }

    public function destroy(string $id): void
    {
        $this->respond($this->service->delete(rawurldecode($id)));
    }

    private function respond(array $result, bool $created = false): void
    {
        if (!$result['success']) {
            if (!empty($result['errors'])) {
                Response::validationError($result['errors'], $result['message']);
                return;
            }
            Response::error($result['message'], [], $result['status'] ?? 400);
            return;
        }

        $created
            ? Response::created($result['data'] ?? null, $result['message'])
            : Response::success($result['data'] ?? null, $result['message']);
    }
}
