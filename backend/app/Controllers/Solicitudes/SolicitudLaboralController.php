<?php

namespace App\Controllers\Solicitudes;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\Solicitudes\SolicitudLaboralService;

class SolicitudLaboralController
{
    private SolicitudLaboralService $service;

    public function __construct()
    {
        $this->service = new SolicitudLaboralService();
    }

    public function types(): void
    {
        Response::success($this->service->getTypes(), 'Tipos de solicitud obtenidos correctamente.');
    }

    public function mine(): void
    {
        $user = $this->authenticatedUser();
        Response::success($this->service->getMine((int) $user['id']), 'Tus solicitudes se obtuvieron correctamente.');
    }

    public function pending(): void
    {
        $user = $this->authenticatedUser();
        Response::success(
            $this->service->getPending((int) $user['id'], $this->roleCodes($user)),
            'Solicitudes pendientes de tu aprobación obtenidas correctamente.'
        );
    }

    public function show(int $id): void
    {
        $user = $this->authenticatedUser();
        $request = $this->service->getDetails($id, (int) $user['id'], $this->roleCodes($user));

        if (!$request) {
            Response::notFound('Solicitud no encontrada o no tienes acceso.');
            return;
        }

        Response::success($request, 'Solicitud obtenida correctamente.');
    }

    public function store(): void
    {
        $user = $this->authenticatedUser();
        if (!in_array('solicitudes.crear', $user['permissions'] ?? [], true)) {
            Response::forbidden('No tienes permiso para crear solicitudes.');
            return;
        }

        $data = $_POST;
        if (!$data && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $data = Request::json();
        }

        $upload = $this->storeUploadedFile($_FILES['Adjunto'] ?? null);
        if (isset($upload['error'])) {
            Response::validationError(['Adjunto' => [$upload['error']]], 'No se pudo adjuntar el archivo.');
            return;
        }

        try {
            $result = $this->service->create((int) $user['id'], $data, $upload);
        } catch (\Throwable $e) {
            if ($upload) {
                $this->removeStoredFile($upload['RutaArchivo']);
            }
            throw $e;
        }
        if (!$result['success']) {
            if ($upload) {
                $this->removeStoredFile($upload['RutaArchivo']);
            }
            Response::validationError($result['errors'] ?? [], $result['message']);
            return;
        }

        Response::created($result['data'], $result['message']);
    }

    public function decide(int $id): void
    {
        $user = $this->authenticatedUser();
        $data = Request::json();
        $decision = ($data['Decision'] ?? '') === 'Rechazada' ? 'Rechazada' : (($data['Decision'] ?? '') === 'Aprobada' ? 'Aprobada' : '');
        $comment = is_string($data['Comentario'] ?? null) ? trim($data['Comentario']) : '';
        $result = $this->service->decide($id, (int) $user['id'], $this->roleCodes($user), $decision, $comment);

        if (!$result['success']) {
            Response::error($result['message'], [], $result['status'] ?? 400);
            return;
        }

        Response::success($result['data'], $result['message']);
    }

    public function download(int $id, int $attachmentId): void
    {
        $user = $this->authenticatedUser();
        $attachment = $this->service->getAttachment(
            $id,
            $attachmentId,
            (int) $user['id'],
            $this->roleCodes($user)
        );

        if (!$attachment) {
            Response::notFound('Archivo no encontrado o no tienes acceso.');
            return;
        }

        $backendRoot = dirname(__DIR__, 3);
        $path = $backendRoot . DIRECTORY_SEPARATOR . str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $attachment['RutaArchivo']
        );
        if (!is_file($path)) {
            Response::notFound('El archivo adjunto ya no está disponible.');
            return;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentTypes = ['pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg'];
        header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . basename($attachment['NombreArchivo']) . '"');
        readfile($path);
        exit;
    }

    private function authenticatedUser(): array
    {
        $user = Auth::user();
        if (!$user || (int) ($user['id'] ?? 0) < 1) {
            Response::unauthorized('Usuario no autenticado.');
        }

        return $user;
    }

    private function roleCodes(array $user): array
    {
        return array_values(array_filter(array_map(
            static fn ($role) => is_array($role) ? ($role['Codigo'] ?? '') : $role,
            $user['roles'] ?? []
        )));
    }

    private function storeUploadedFile(?array $file): ?array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['error' => 'No fue posible recibir el archivo adjunto.'];
        }
        if (($file['size'] ?? 0) < 1 || $file['size'] > 10 * 1024 * 1024) {
            return ['error' => 'El archivo debe pesar menos de 10 MB.'];
        }

        $handle = fopen($file['tmp_name'], 'rb');
        if ($handle === false) {
            return ['error' => 'No fue posible validar el archivo adjunto.'];
        }
        $signature = fread($handle, 8);
        fclose($handle);
        if (!is_string($signature)) {
            return ['error' => 'No fue posible validar el archivo adjunto.'];
        }

        $extension = match (true) {
            str_starts_with($signature, '%PDF-') => 'pdf',
            $signature === "\x89PNG\r\n\x1a\n" => 'png',
            str_starts_with($signature, "\xFF\xD8\xFF") => 'jpg',
            default => null,
        };
        if ($extension === null) {
            return ['error' => 'Solo se permiten archivos PDF, JPG o PNG.'];
        }

        $relativeDirectory = 'storage' . DIRECTORY_SEPARATOR . 'solicitudes';
        $absoluteDirectory = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $relativeDirectory;
        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0770, true) && !is_dir($absoluteDirectory)) {
            return ['error' => 'No fue posible preparar el almacenamiento de archivos.'];
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            return ['error' => 'No fue posible guardar el archivo adjunto.'];
        }

        return [
            'NombreArchivo' => preg_replace('/[\r\n"]/', '', basename(str_replace('\\', '/', (string) $file['name']))),
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
}
