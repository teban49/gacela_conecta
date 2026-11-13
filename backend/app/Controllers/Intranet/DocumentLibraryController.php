<?php

namespace App\Controllers\Intranet;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\RoleAccess;
use App\Services\Intranet\DocumentLibraryService;

class DocumentLibraryController
{
    private DocumentLibraryService $service;

    public function __construct()
    {
        $this->service = new DocumentLibraryService();
    }

    public function store(string $kind): void
    {
        $this->authorizeAdmin();
        $this->respond($this->service->create($kind, $_POST, $_FILES['Archivo'] ?? null), true);
    }

    public function destroy(string $kind): void
    {
        $this->authorizeAdmin();
        $this->respond($this->service->delete($kind, Request::json()));
    }

    private function authorizeAdmin(): void
    {
        if (!RoleAccess::isAdmin(Auth::user())) {
            Response::forbidden('Solo un administrador puede gestionar estos documentos.');
        }
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
