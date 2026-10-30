<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;
use App\Services\NotificacionService;

class NotificacionController
{
    private NotificacionService $service;

    public function __construct()
    {
        $this->service = new NotificacionService();
    }

    public function index(): void
    {
        $user = $this->authenticatedUser();
        $roles = array_values(array_filter(array_map(
            static fn ($role) => is_array($role) ? ($role['Codigo'] ?? '') : $role,
            $user['roles'] ?? []
        )));

        Response::success($this->service->getForUser((int) $user['id'], $roles), 'Notificaciones obtenidas correctamente.');
    }

    public function markRead(int $id): void
    {
        $user = $this->authenticatedUser();
        $this->service->markRead((int) $user['id'], $id);
        Response::success([], 'Notificación marcada como leída.');
    }

    public function markAllRead(): void
    {
        $user = $this->authenticatedUser();
        $this->service->markAllRead((int) $user['id']);
        Response::success([], 'Notificaciones marcadas como leídas.');
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
