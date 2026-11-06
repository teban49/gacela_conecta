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

        return $this->buildSession($user);
    }

    private function buildSession(array $user): array
    {
        $permissions = $this->userModel->getUserPermissions($user['Id']);
        $organizationData = $this->userModel->getOrganizationData($user['Id']);

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
                'user' => $this->userPayload($user, $user['Roles'], $permissions, $organizationData)
            ]
        ];
    }

    private function userPayload(array $user, array $roles, array $permissions, $organization): array
    {
        return [
            'id' => $user['Id'],
            'nombres' => $user['Nombres'],
            'apellidos' => $user['Apellidos'],
            'email' => $user['EmailCorporativo'],
            'usuario' => $user['Usuario'] ?? null,
            'cedula' => $user['Cedula'] ?? null,
            'telefono' => $user['Telefono'],
            'fechaNacimiento' => $user['FechaNacimiento'] ?? null,
            'direccion' => $user['Direccion'] ?? null,
            'perfilConfirmado' => !empty($user['PerfilConfirmadoEn']),
            'perfilFaltantes' => $this->missingProfileFields($user),
            'roles' => $roles,
            'permissions' => $permissions,
            'organization' => $organization
        ];
    }

    private function missingProfileFields(array $user): array
    {
        $required = [
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'cedula' => 'Cedula',
            'telefono' => 'Telefono',
            'fechaNacimiento' => 'FechaNacimiento',
        ];
        $missing = [];
        foreach ($required as $key => $column) {
            if (trim((string) ($user[$column] ?? '')) === '') {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    public function loginWithPassword(string $username, string $password, string $ip): array
    {
        $username = strtolower(trim($username));
        $genericError = ['success' => false, 'message' => 'Usuario o contraseña incorrectos.'];

        if ($username === '' || $password === '') {
            return $genericError;
        }

        $db = \App\Core\Database::getConnection();
        $count = $db->prepare("
            SELECT COUNT(*) FROM gcp_loginintentos
            WHERE Exitoso = 0 AND Fecha > (NOW() - INTERVAL 15 MINUTE) AND (Usuario = :usuario OR Ip = :ip)
        ");
        $count->execute([':usuario' => $username, ':ip' => $ip]);
        if ((int) $count->fetchColumn() >= 5) {
            return ['success' => false, 'status' => 429, 'message' => 'Demasiados intentos fallidos. Intenta de nuevo en 15 minutos.'];
        }

        $user = $this->userModel->findByUsername($username);
        $valid = $user && !empty($user['PasswordHash']) && password_verify($password, $user['PasswordHash']);

        $log = $db->prepare('INSERT INTO gcp_loginintentos (Usuario, Ip, Exitoso) VALUES (:usuario, :ip, :ok)');
        $log->execute([':usuario' => $username, ':ip' => $ip, ':ok' => $valid ? 1 : 0]);

        if (!$valid) {
            return $genericError;
        }

        $db->prepare('DELETE FROM gcp_loginintentos WHERE Usuario = :usuario AND Exitoso = 0')->execute([':usuario' => $username]);

        return $this->buildSession($user);
    }

    public function updateProfile(int $userId, array $data): array
    {
        $db = \App\Core\Database::getConnection();
        $clean = static fn($v) => trim((string) ($v ?? ''));

        $nombres = $clean($data['nombres'] ?? null);
        $apellidos = $clean($data['apellidos'] ?? null);
        $cedula = preg_replace('/\D+/', '', $clean($data['cedula'] ?? null));
        $telefono = $clean($data['telefono'] ?? null);
        $nacimiento = $clean($data['fechaNacimiento'] ?? null);
        $direccion = $clean($data['direccion'] ?? null);

        $errors = [];
        if ($nombres === '') $errors[] = 'Los nombres son obligatorios.';
        if ($apellidos === '') $errors[] = 'Los apellidos son obligatorios.';
        if (strlen($cedula) < 5 || strlen($cedula) > 15) $errors[] = 'La cédula no es válida.';
        if ($telefono === '' || strlen($telefono) > 20) $errors[] = 'El teléfono es obligatorio (máximo 20 caracteres).';
        $date = \DateTime::createFromFormat('Y-m-d', $nacimiento);
        if (!$date || $date->format('Y-m-d') !== $nacimiento || $nacimiento > date('Y-m-d')) {
            $errors[] = 'La fecha de nacimiento no es válida.';
        }
        if ($errors) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $dup = $db->prepare('SELECT COUNT(*) FROM gcp_usuarios WHERE Cedula = :c AND Id <> :id');
        $dup->execute([':c' => $cedula, ':id' => $userId]);
        if ((int) $dup->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Esa cédula ya está registrada en otro usuario.'];
        }

        $db->prepare('
            UPDATE gcp_usuarios
            SET Nombres = :n, Apellidos = :a, Cedula = :c, Telefono = :t, FechaNacimiento = :f,
                Direccion = :d, PerfilConfirmadoEn = NOW()
            WHERE Id = :id
        ')->execute([
            ':n' => $nombres, ':a' => $apellidos, ':c' => $cedula, ':t' => $telefono,
            ':f' => $nacimiento, ':d' => $direccion !== '' ? $direccion : null, ':id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Perfil actualizado.'];
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

        return $this->userPayload($user, $roles, $permissions, $organizationData) + ['Estado' => $user['Estado']];
    }
}