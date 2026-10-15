<?php

namespace App\Controllers\Intranet;

use App\Core\Request;
use App\Core\Response;
use App\Services\Intranet\NoticiaService;

class NoticiaController
{
    private NoticiaService $service;

    public function __construct()
    {
        $this->service = new NoticiaService();
    }

    public function index(): void
    {
        Response::success($this->service->getAll(), 'Noticias obtenidas correctamente.');
    }

    public function published(): void
    {
        Response::success($this->service->getPublished(), 'Noticias publicadas obtenidas correctamente.');
    }

    public function byCategory(string $categoria): void
    {
        Response::success($this->service->getByCategory($categoria), 'Noticias por categoría obtenidas correctamente.');
    }

    public function show(int $id): void
    {
        $noticia = $this->service->getById($id);

        if (!$noticia) {
            Response::notFound('Noticia no encontrada.');
            return;
        }

        Response::success($noticia, 'Noticia obtenida correctamente.');
    }

    public function markAsViewed(int $id): void
    {
        $data = Request::json();
        $usuarioId = $data['UsuarioId'] ?? 0;
        $result = $this->service->markAsViewed($id, $usuarioId);

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
        $status = $data['Estado'] ?? 'borrador';
        $result = $this->service->changeStatus($id, $status);

        if ($result['success']) {
            Response::success($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}