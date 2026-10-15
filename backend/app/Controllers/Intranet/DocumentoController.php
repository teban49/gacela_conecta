<?php

namespace App\Controllers\Intranet;

use App\Core\Request;
use App\Core\Response;
use App\Services\Intranet\DocumentoService;

class DocumentoController
{
    private DocumentoService $service;

    public function __construct()
    {
        $this->service = new DocumentoService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Documentos obtenidos correctamente.');
    }

    public function byCategory(string $categoria): void
    {
        Response::success($this->service->getByCategory($categoria), 'Documentos por categoría obtenidos correctamente.');
    }

    public function show(int $id): void
    {
        $documento = $this->service->getById($id);

        if (!$documento) {
            Response::notFound('Documento no encontrado.');
            return;
        }

        Response::success($documento, 'Documento obtenido correctamente.');
    }

    public function download(int $id): void
    {
        $result = $this->service->incrementDownloads($id);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function store(): void
    {
        $data = Request::json();
        $result = $this->service->create($data);

        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function update(int $id): void
    {
        $data = Request::json();
        $result = $this->service->update($id, $data);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function changeStatus(int $id): void
    {
        $data = Request::json();
        $status = $data['Estado'] ?? 'activo';
        $result = $this->service->changeStatus($id, $status);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}