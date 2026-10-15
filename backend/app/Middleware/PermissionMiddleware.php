<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

class PermissionMiddleware
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function handle(): void
    {
        $user = Auth::user();

        if (!$user) {
            Response::unauthorized('Usuario no autenticado');
            return;
        }

        if ($this->isAdmin($user)) {
            return;
        }

        $permissions = $user['permissions'] ?? [];

        if (!in_array($this->permission, $permissions)) {
            Response::forbidden('No tienes permiso para realizar esta acción');
            return;
        }
    }

    private function isAdmin(array $user): bool
    {
        $roles = $user['roles'] ?? [];
        foreach ($roles as $role) {
            $code = is_array($role) ? ($role['Codigo'] ?? '') : (string) $role;
            $name = is_array($role) ? ($role['Nombre'] ?? '') : '';
            if (in_array($code, ['admin', 'superadmin'], true) || strtolower($name) === 'administrador') {
                return true;
            }
        }
        return false;
    }

    public static function check(string $permission): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $roles = $user['roles'] ?? [];
        foreach ($roles as $role) {
            $code = is_array($role) ? ($role['Codigo'] ?? '') : (string) $role;
            $name = is_array($role) ? ($role['Nombre'] ?? '') : '';
            if (in_array($code, ['admin', 'superadmin'], true) || strtolower($name) === 'administrador') {
                return true;
            }
        }

        $permissions = $user['permissions'] ?? [];
        return in_array($permission, $permissions, true);
    }

    public static function checkAny(array $permissions): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ((new self(''))->isAdmin($user)) {
            return true;
        }

        $userPermissions = $user['permissions'] ?? [];
        return !empty(array_intersect($permissions, $userPermissions));
    }

    public static function checkAll(array $permissions): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ((new self(''))->isAdmin($user)) {
            return true;
        }

        $userPermissions = $user['permissions'] ?? [];
        return empty(array_diff($permissions, $userPermissions));
    }
}
