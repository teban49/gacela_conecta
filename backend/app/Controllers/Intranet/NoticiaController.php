<?php

namespace App\Controllers\Intranet;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\UploadHelper;
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
        $user = Auth::user();
        $data['AutorId'] = (int) ($user['id'] ?? 0);

        $errors = [];
        if (!is_string($data['Titulo'] ?? null) || trim($data['Titulo']) === '' || mb_strlen($data['Titulo']) > 255) {
            $errors['Titulo'] = ['El título es obligatorio y debe tener máximo 255 caracteres.'];
        }
        if (!is_string($data['Contenido'] ?? null) || trim($data['Contenido']) === '') {
            $errors['Contenido'] = ['El contenido es obligatorio.'];
        }
        if (isset($data['Resumen']) && (!is_string($data['Resumen']) || mb_strlen($data['Resumen']) > 500)) {
            $errors['Resumen'] = ['El resumen debe tener máximo 500 caracteres.'];
        }

        $upload = UploadHelper::store($_FILES['Imagen'] ?? null, NoticiaService::imageDirectory(), UploadHelper::IMAGE_TYPES, 5 * 1024 * 1024);
        if (isset($upload['error'])) {
            $errors['Imagen'] = [$upload['error']];
        }
        if ($errors) {
            if (isset($upload['path'])) {
                @unlink($upload['path']);
            }
            Response::validationError($errors, 'Revisa los datos de la noticia.');
            return;
        }

        if ($upload) {
            $data['Imagen'] = $upload['name'];
        }

        try {
            $result = $this->service->create($data);
        } catch (\Throwable $e) {
            if ($upload) {
                @unlink($upload['path']);
            }
            throw $e;
        }

        Response::created($result['data'] ?? null, $result['message']);
    }

    public function image(string $filename): void
    {
        $path = $this->service->getImagePath($filename);
        if ($path === null) {
            Response::notFound('La imagen no existe.');
            return;
        }

        $contentTypes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'];
        header('Content-Type: ' . $contentTypes[strtolower(pathinfo($path, PATHINFO_EXTENSION))]);
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        readfile($path);
        exit;
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