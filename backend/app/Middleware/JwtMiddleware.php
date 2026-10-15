<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\JwtService;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\Security\UserRepository;
use App\Repositories\Security\RoleRepository;

class JwtMiddleware
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->roleRepository = new RoleRepository();
    }

    public function handle(): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            Response::unauthorized('Token no proporcionado');
            return;
        }

        $token = substr($authHeader, 7);

        if (!JwtService::validate($token)) {
            Response::unauthorized('Token inválido o expirado');
            return;
        }

        $payload = JwtService::decode($token);

        if (!$payload || !isset($payload->sub)) {
            Response::unauthorized('Token malformado');
            return;
        }

        $userId = (int) $payload->sub;

        // Verificar estado del usuario en cada petición
        $user = $this->userRepository->getById($userId);

        if (!$user) {
            Response::unauthorized('Usuario no encontrado');
            return;
        }

        if (!$user['Estado']) {
            Response::unauthorized('Usuario desactivado. Contacte al administrador.');
            return;
        }

        // Cargar roles y permisos del usuario
        $userRoles = $this->userRepository->getUserRoles($userId);
        $permissions = $this->getUserPermissions($userId);

        // Establecer usuario autenticado en el sistema
        Auth::setUser([
            'id' => $user['Id'],
            'email' => $user['EmailCorporativo'],
            'nombres' => $user['Nombres'],
            'apellidos' => $user['Apellidos'],
            'roles' => $userRoles,
            'permissions' => $permissions
        ]);
    }

    private function getUserPermissions(int $userId): array
    {
        $sql = "
            SELECT DISTINCT p.CodigoUnico
            FROM gcp_usuarios u
            INNER JOIN gcp_rolespermisos rp ON rp.RolId = u.RolId
            INNER JOIN gcp_permisos p ON p.Id = rp.PermisoId
            WHERE u.Id = :userId AND p.Estado = 1
        ";

        $stmt = $this->userRepository->getDb()->prepare($sql);
        $stmt->execute([':userId' => $userId]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_column($results, 'CodigoUnico');
    }
}
