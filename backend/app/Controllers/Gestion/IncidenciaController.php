<?php

namespace App\Controllers\Gestion;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\PermissionMiddleware;
use App\Services\Gestion\IncidenciaService;

class IncidenciaController
{
    private IncidenciaService $service;

    public function __construct()
    {
        $this->service = new IncidenciaService();
    }

    public function index(): void
    {
        $user = $this->authenticatedUser();
        $canManage = PermissionMiddleware::check('gestion.administrar_incidencias');
        Response::success(
            $this->service->getAll($canManage ? null : (int) $user['id']),
            'Tickets de incidencia obtenidos correctamente.'
        );
    }

    public function categories(): void
    {
        Response::success($this->service->getCategories(), 'Categorías de tickets obtenidas correctamente.');
    }

    public function context(): void
    {
        $user = $this->authenticatedUser();
        Response::success(
            $this->service->getContext((int) $user['id']),
            'Ubicación y empresas del usuario obtenidas correctamente.'
        );
    }

    public function assignableUsers(): void
    {
        Response::success($this->service->getAssignableUsers(), 'Usuarios asignables obtenidos correctamente.');
    }

    public function show(int $id): void
    {
        $user = $this->authenticatedUser();
        $incident = $this->service->getById($id);
        if (!$incident) {
            Response::notFound('Ticket de incidencia no encontrado.');
            return;
        }

        if ((int) $incident['SolicitanteId'] !== (int) $user['id']
            && !PermissionMiddleware::check('gestion.administrar_incidencias')) {
            Response::forbidden('Solo puedes consultar tus propias incidencias.');
            return;
        }

        Response::success($incident, 'Ticket de incidencia obtenido correctamente.');
    }

    public function store(): void
    {
        $user = $this->authenticatedUser();
        $data = $_POST;
        if (!$data && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $data = Request::json();
        }
        $upload = $this->storeUploadedFile($_FILES['Adjunto'] ?? null);
        if (isset($upload['error'])) {
            Response::validationError(['Adjunto' => [$upload['error']]], 'No se pudo adjuntar la evidencia.');
            return;
        }

        try {
            $result = $this->service->create(
                (int) $user['id'],
                $data,
                $upload
            );
        } catch (\Throwable $e) {
            if ($upload) {
                $this->removeStoredFile($upload['RutaArchivo']);
            }
            throw $e;
        }
        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
            return;
        }

        if ($upload) {
            $this->removeStoredFile($upload['RutaArchivo']);
        }
        if (!empty($result['errors'])) {
            Response::validationError($result['errors'], $result['message']);
            return;
        }
        Response::error($result['message']);
    }

    public function update(int $id): void
    {
        $user = $this->authenticatedUser();
        $result = $this->service->update($id, (int) $user['id'], Request::json());
        if (!$result['success']) {
            if (!empty($result['errors'])) {
                Response::validationError($result['errors'], $result['message']);
                return;
            }
            Response::error($result['message'], [], $result['status'] ?? 400);
            return;
        }

        Response::success(null, $result['message']);
    }

    public function closeByRequester(int $id): void
    {
        $user = $this->authenticatedUser();
        $result = $this->service->closeByRequester($id, (int) $user['id'], Request::json());
        if (!$result['success']) {
            if (!empty($result['errors'])) {
                Response::validationError($result['errors'], $result['message']);
                return;
            }
            Response::error($result['message'], [], $result['status'] ?? 400);
            return;
        }
        Response::success(null, $result['message']);
    }

    public function downloadAttachment(int $id): void
    {
        $user = $this->authenticatedUser();
        $incident = $this->service->getById($id);
        if (!$incident || empty($incident['ArchivoAdjunto'])) {
            Response::notFound('No se encontró evidencia adjunta para este ticket.');
            return;
        }
        if ((int) $incident['SolicitanteId'] !== (int) $user['id']
            && !PermissionMiddleware::check('gestion.administrar_incidencias')) {
            Response::forbidden('No tienes acceso a la evidencia de esta incidencia.');
            return;
        }

        $relativePath = str_replace('\\', '/', $incident['ArchivoAdjunto']);
        if (!str_starts_with($relativePath, 'storage/incidencias/')) {
            Response::notFound('La ruta de evidencia no es válida.');
            return;
        }
        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $relativePath
        );
        $storageDirectory = realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'incidencias');
        $resolvedPath = realpath($path);
        if (!$storageDirectory || !$resolvedPath || !is_file($resolvedPath)
            || !str_starts_with(
                strtolower($resolvedPath),
                strtolower($storageDirectory . DIRECTORY_SEPARATOR)
            )) {
            Response::notFound('El archivo de evidencia ya no está disponible.');
            return;
        }
        $path = $resolvedPath;

        $name = basename((string) ($incident['ArchivoAdjuntoNombre'] ?: $incident['ArchivoAdjunto']));
        $name = preg_replace('/[\r\n]/', '', $name);
        $extension = strtolower(pathinfo($incident['ArchivoAdjunto'], PATHINFO_EXTENSION));
        $contentTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'txt' => 'text/plain; charset=utf-8',
            'csv' => 'text/csv; charset=utf-8',
        ];
        header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . addcslashes($name, "\\\"") . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    private function storeUploadedFile(?array $file): ?array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['error' => 'No fue posible recibir el archivo de evidencia.'];
        }
        if (($file['size'] ?? 0) < 1 || $file['size'] > 10 * 1024 * 1024) {
            return ['error' => 'El archivo debe pesar menos de 10 MB.'];
        }

        $originalName = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = [
            'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'zip', 'rar', '7z',
        ];
        if (!in_array($extension, $allowedExtensions, true)) {
            return ['error' => 'Tipo de archivo no permitido. Adjunta una imagen, PDF o documento compatible.'];
        }

        $relativeDirectory = 'storage' . DIRECTORY_SEPARATOR . 'incidencias';
        $absoluteDirectory = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $relativeDirectory;
        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0770, true) && !is_dir($absoluteDirectory)) {
            return ['error' => 'No fue posible preparar el almacenamiento de evidencias.'];
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            return ['error' => 'No fue posible guardar el archivo de evidencia.'];
        }

        return [
            'NombreArchivo' => preg_replace('/[\r\n"]/', '', $originalName),
            'RutaArchivo' => $relativeDirectory . DIRECTORY_SEPARATOR . $storedName,
        ];
    }

    private function removeStoredFile(string $relativePath): void
    {
        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $relativePath;
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function authenticatedUser(): array
    {
        $user = Auth::user();
        if (!$user || (int) ($user['id'] ?? 0) < 1) {
            Response::unauthorized('Usuario no autenticado.');
        }

        return $user;
    }
}
