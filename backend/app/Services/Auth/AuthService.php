<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\AuthToken;
use App\Core\JwtService;
use App\Core\Mail;
use App\Core\Response;

class AuthService
{
    private User $userModel;
    private AuthToken $authTokenModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->authTokenModel = new AuthToken();
    }

    public function requestMagicLink(string $email, string $ip, string $userAgent, ?string $origin = null): array
    {
        // Find user by email
        $user = $this->userModel->findByEmail($email);

        // Always return the same message for security (prevent user enumeration)
        if (!$user) {
            return [
                'success' => true,
                'message' => 'Si tu correo está registrado, recibirás un enlace de acceso en tu bandeja de entrada.'
            ];
        }

        // Generate magic link token
        $token = JwtService::generateMagicLinkToken($user['Id']);
        $tokenHash = JwtService::hashToken($token);

        // Calculate expiration (15 minutes)
        $expiration = date('Y-m-d H:i:s', time() + 900);

        // Store token in database
        $tokenType = defined('TOKEN_TYPE_MAGIC_LINK') ? TOKEN_TYPE_MAGIC_LINK : 'MAGIC_LINK';
        
        $tokenId = $this->authTokenModel->create([
            'UsuarioId' => $user['Id'],
            'TokenHash' => $tokenHash,
            'Tipo' => $tokenType,
            'FechaExpiracion' => $expiration,
            'IpAddress' => $ip,
            'UserAgent' => $userAgent
        ]);

        // Generate magic link URL
        $appUrl = $this->resolveAppUrl($origin);
        $magicLink = "{$appUrl}/auth/verify?token={$token}";

        // Send email
        $userName = trim($user['Nombres'] . ' ' . $user['Apellidos']);
        $emailSent = Mail::sendMagicLink($user['EmailCorporativo'], $userName, $magicLink);

        if (!$emailSent) {
            return [
                'success' => false,
                'message' => 'Error al enviar el correo de acceso. Por favor intenta nuevamente.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Si tu correo está registrado, recibirás un enlace de acceso en tu bandeja de entrada.'
        ];
    }

    /**
     * APP_URL fija la URL pública del frontend (producción). Si está vacía, se usa el origen
     * desde el que el usuario pidió el enlace, solo si es localhost, una IP privada o un host
     * listado en APP_ALLOWED_HOSTS; así el enlace nunca apunta a un dominio arbitrario.
     */
    private function resolveAppUrl(?string $origin): string
    {
        $configured = trim((string) (getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '')));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $parts = $origin ? parse_url($origin) : false;
        $host = strtolower($parts['host'] ?? '');
        if ($host !== '' && in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            $allowed = array_filter(array_map('trim', explode(',', strtolower((string) (getenv('APP_ALLOWED_HOSTS') ?: ($_ENV['APP_ALLOWED_HOSTS'] ?? ''))))));
            $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
            $isPrivateIp = $isIp && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
            if ($host === 'localhost' || $isPrivateIp || in_array($host, $allowed, true)) {
                return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
            }
        }

        return 'http://localhost:5173';
    }

    public function verifyToken(string $token, string $ip, string $userAgent): array
    {
        // Hash the token to compare with stored hash
        $tokenHash = JwtService::hashToken($token);

        // Find token in database
        $authToken = $this->authTokenModel->findByTokenHash($tokenHash);

        if (!$authToken) {
            return [
                'success' => false,
                'message' => 'El enlace de acceso es inválido o ha expirado.'
            ];
        }

        // Get user data
        $user = $this->userModel->findById($authToken['UsuarioId']);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ];
        }

        // Mark token as used
        $this->authTokenModel->markAsUsed($authToken['Id']);

        // Get user permissions
        $permissions = $this->userModel->getUserPermissions($user['Id']);

        // Get organization data
        $organizationData = $this->userModel->getOrganizationData($user['Id']);
        error_log("Organization Data:");
        error_log(print_r($organizationData, true));

        // Generate JWT session token
        $jwtPayload = [
            'sub' => $user['Id'],
            'email' => $user['EmailCorporativo'],
            'roles' => $user['Roles'],
            'permissions' => $permissions
        ];

        $sessionToken = JwtService::encode($jwtPayload);

        return [
            'success' => true,
            'message' => 'Autenticación exitosa.',
            'data' => [
                'token' => $sessionToken,
                'user' => [
                    'id' => $user['Id'],
                    'nombres' => $user['Nombres'],
                    'apellidos' => $user['Apellidos'],
                    'email' => $user['EmailCorporativo'],
                    'telefono' => $user['Telefono'],
                    'roles' => $user['Roles'],
                    'permissions' => $permissions,
                    'organization' => $organizationData
                ]
            ]
        ];
    }

    public function validateSessionToken(string $token): ?array
    {
        $decoded = JwtService::decode($token);

        if (!$decoded) {
            return null;
        }

        $user = $this->userModel->findById($decoded->sub);

        if (!$user) {
            return null;
        }

        // Verificar estado del usuario
        if (!$user['Estado']) {
            return null;
        }

        // Obtener roles y permisos actualizados en cada validación
        $permissions = $this->userModel->getUserPermissions($user['Id']);
        $roles = $this->userModel->getUserRoles($user['Id']);

        $organizationData = $this->userModel->getOrganizationData($user['Id']);

        return [
            'id' => $user['Id'],
            'nombres' => $user['Nombres'],
            'apellidos' => $user['Apellidos'],
            'email' => $user['EmailCorporativo'],
            'telefono' => $user['Telefono'],
            'Estado' => $user['Estado'],
            'roles' => $roles,
            'permissions' => $permissions,
            'organization' => $organizationData
        ];
    }
}