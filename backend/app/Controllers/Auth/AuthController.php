<?php

namespace App\Controllers\Auth;

use App\Services\Auth\AuthService;
use App\Core\Request;
use App\Core\Response;

class AuthController
{
    private AuthService $authService;
    private Request $request;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->request = new Request();
    }

    /**
     * POST /api/v1/auth/magic-link
     * Request a magic link for authentication
     */
    public function requestMagicLink(): void
    {
        $email = $this->request->get('email');

        if (empty($email)) {
            Response::validationError(['email' => 'El correo electrónico es requerido.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::validationError(['email' => 'El formato del correo electrónico es inválido.']);
            return;
        }

        $ip = $this->request->getIp();
        $userAgent = $this->request->getUserAgent();

        $result = $this->authService->requestMagicLink($email, $ip, $userAgent);

        if ($result['success']) {
            Response::success(null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    /**
     * POST /api/v1/auth/verify-token
     * Verify magic link token and create session
     */
    public function verifyToken(): void
    {
        $token = $this->request->get('token');

        if (empty($token)) {
            Response::validationError(['token' => 'El token es requerido.']);
            return;
        }

        $ip = $this->request->getIp();
        $userAgent = $this->request->getUserAgent();

        $result = $this->authService->verifyToken($token, $ip, $userAgent);

        if ($result['success']) {
            Response::success($result['data'], $result['message']);
        } else {
            Response::unauthorized($result['message']);
        }
    }

    /**
     * GET /api/v1/auth/me
     * Get current authenticated user
     */
    public function me(): void
    {
        $token = $this->request->getBearerToken();

        if (!$token) {
            Response::unauthorized('Token de autenticación no proporcionado.');
            return;
        }

        $user = $this->authService->validateSessionToken($token);

        if (!$user) {
            Response::unauthorized('Token inválido o expirado.');
            return;
        }

        Response::success($user, 'Usuario autenticado.');
    }

    /**
     * POST /api/v1/auth/logout
     * Logout current user (client-side token removal)
     */
    public function logout(): void
    {
        Response::success(null, 'Sesión cerrada exitosamente.');
    }
}
