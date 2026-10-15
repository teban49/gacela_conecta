<?php

namespace App\Core;

class Response
{
    public static function json(
        bool $success = true,
        mixed $data = null,
        string $message = '',
        array $errors = [],
        int $statusCode = 200
    ): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $keySuccess = defined('RESPONSE_KEY_SUCCESS') ? RESPONSE_KEY_SUCCESS : 'success';
        $keyData    = defined('RESPONSE_KEY_DATA')    ? RESPONSE_KEY_DATA    : 'data';
        $keyMessage = defined('RESPONSE_KEY_MESSAGE') ? RESPONSE_KEY_MESSAGE : 'message';
        $keyErrors  = defined('RESPONSE_KEY_ERRORS')  ? RESPONSE_KEY_ERRORS  : 'errors';

        $response = [
            $keySuccess => $success,
            $keyData    => $data,
            $keyMessage => $message,
            $keyErrors  => $errors
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Operación ejecutada correctamente'): void
    {
        $code = defined('HTTP_OK') ? HTTP_OK : 200;
        self::json(true, $data, $message, [], $code);
    }

    public static function created(mixed $data = null, string $message = 'Recurso creado exitosamente'): void
    {
        $code = defined('HTTP_CREATED') ? HTTP_CREATED : 201;
        self::json(true, $data, $message, [], $code);
    }

    public static function error(string $message = 'Error en la operación', array $errors = [], int $statusCode = 400): void
    {
        self::json(false, null, $message, $errors, $statusCode);
    }

    public static function unauthorized(string $message = 'No autorizado'): void
    {
        $code = defined('HTTP_UNAUTHORIZED') ? HTTP_UNAUTHORIZED : 401;
        self::json(false, null, $message, [], $code);
    }

    public static function forbidden(string $message = 'Acceso denegado'): void
    {
        $code = defined('HTTP_FORBIDDEN') ? HTTP_FORBIDDEN : 403;
        self::json(false, null, $message, [], $code);
    }

    public static function notFound(string $message = 'Recurso no encontrado'): void
    {
        $code = defined('HTTP_NOT_FOUND') ? HTTP_NOT_FOUND : 404;
        self::json(false, null, $message, [], $code);
    }

    public static function validationError(array $errors, string $message = 'Error de validación'): void
    {
        $code = defined('HTTP_UNPROCESSABLE_ENTITY') ? HTTP_UNPROCESSABLE_ENTITY : 422;
        self::json(false, null, $message, $errors, $code);
    }

    public static function serverError(string $message = 'Error interno del servidor'): void
    {
        $code = defined('HTTP_INTERNAL_SERVER_ERROR') ? HTTP_INTERNAL_SERVER_ERROR : 500;
        self::json(false, null, $message, [], $code);
    }
}