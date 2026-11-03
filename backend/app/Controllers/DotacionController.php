<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\DotacionService;

class DotacionController
{
    private DotacionService $service;

    public function __construct()
    {
        $this->service = new DotacionService();
    }

    public function index(): void
    {
        $user = $this->authenticatedUser();
        Response::success($this->service->getDashboard((int) $user['id'], $this->isAdmin($user)), 'Información de dotación obtenida correctamente.');
    }

    public function deliveries(): void
    {
        $user = $this->authenticatedUser();
        $result = $this->service->getDeliveries(
            (int) $user['id'],
            $this->isAdmin($user),
            (string) ($_GET['q'] ?? ''),
            (int) ($_GET['page'] ?? 1)
        );
        if ($result === null) {
            Response::forbidden('No tienes permiso para consultar las entregas de dotación.');
            return;
        }
        Response::success($result, 'Entregas de dotación obtenidas correctamente.');
    }

    private function isAdmin(array $user): bool
    {
        foreach ($user['roles'] ?? [] as $role) {
            $code = is_array($role) ? ($role['Codigo'] ?? '') : (string) $role;
            $name = is_array($role) ? ($role['Nombre'] ?? '') : '';
            if (in_array($code, ['admin', 'superadmin'], true) || strtolower($name) === 'administrador') {
                return true;
            }
        }

        return false;
    }

    public function show(int $id): void
    {
        $user = $this->authenticatedUser();
        $request = $this->service->getDetails($id, (int) $user['id']);
        if (!$request) {
            Response::notFound('Solicitud no encontrada o no tienes acceso.');
            return;
        }
        Response::success($request, 'Solicitud de dotación obtenida correctamente.');
    }

    public function store(): void
    {
        $user = $this->authenticatedUser();
        $result = $this->service->create((int) $user['id'], Request::json());
        if (!$result['success']) {
            if (($result['status'] ?? 400) === 422) {
                Response::validationError($result['errors'] ?? [], $result['message']);
                return;
            }
            Response::error($result['message'], [], $result['status'] ?? 400);
            return;
        }
        Response::created($result['data'], $result['message']);
    }

    public function decide(int $id): void
    {
        $user = $this->authenticatedUser();
        $data = Request::json();
        $decision = $data['Decision'] ?? '';
        if (!in_array($decision, ['Aprobada', 'Rechazada'], true)) {
            Response::validationError(['Decision' => ['Selecciona aceptar o denegar la solicitud.']]);
            return;
        }
        $comment = is_string($data['Comentario'] ?? null) ? trim($data['Comentario']) : '';
        $result = $this->service->decide($id, (int) $user['id'], $decision === 'Aprobada', $comment);
        if (!$result['success']) {
            Response::error($result['message'], [], $result['status'] ?? 400);
            return;
        }
        Response::success($result['data'], $result['message']);
    }

    public function received(int $id): void
    {
        $user = $this->authenticatedUser();
        $result = $this->service->markReceived($id, (int) $user['id']);
        if (!$result['success']) {
            Response::error($result['message'], [], $result['status'] ?? 400);
            return;
        }
        Response::success([], $result['message']);
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
