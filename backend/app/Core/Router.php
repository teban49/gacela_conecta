<?php

namespace App\Core;

use App\Middleware\JwtMiddleware;
use App\Middleware\PermissionMiddleware;
use Throwable;

class Router
{
    private static array $routes = [];

    public static function get(string $path, callable $handler, array $middlewares = []): void
    {
        self::addRoute('GET', $path, $handler, $middlewares);
    }

    public static function post(string $path, callable $handler, array $middlewares = []): void
    {
        self::addRoute('POST', $path, $handler, $middlewares);
    }

    public static function put(string $path, callable $handler, array $middlewares = []): void
    {
        self::addRoute('PUT', $path, $handler, $middlewares);
    }

    public static function patch(string $path, callable $handler, array $middlewares = []): void
    {
        self::addRoute('PATCH', $path, $handler, $middlewares);
    }

    public static function delete(string $path, callable $handler, array $middlewares = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middlewares);
    }

    private static function addRoute(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        self::$routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public static function dispatch(): void
    {
        try {
            $request = new Request();
            $method = $request->getMethod();
            $uri = $request->getUri();

            // Set CORS headers
            self::setCorsHeaders();

            // Handle preflight requests
            if ($method === 'OPTIONS') {
                http_response_code(200);
                exit;
            }

            if (!isset(self::$routes[$method])) {
                Response::notFound('Método no permitido');
                return;
            }

            foreach (self::$routes[$method] as $path => $route) {
                $pattern = self::convertPathToRegex($path);

                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches); // Remove full match

                    // Execute middlewares
                    foreach ($route['middlewares'] as $middleware) {
                        self::runMiddleware($middleware);
                    }

                    // Call route handler
                    call_user_func_array($route['handler'], $matches);
                    return;
                }
            }

            Response::notFound('Ruta no encontrada');
        } catch (Throwable $e) {
            Logger::error('Error en Router dispatch: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            Response::serverError('Error interno del servidor: ' . $e->getMessage());
        }
    }

    private static function runMiddleware(mixed $middleware): void
    {
        if (is_string($middleware)) {
            if ($middleware === 'jwt') {
                $jwtMiddleware = new JwtMiddleware();
                $jwtMiddleware->handle();
            } else {
                // Es un permiso específico
                $permissionMiddleware = new PermissionMiddleware($middleware);
                $permissionMiddleware->handle();
            }
        } elseif (is_object($middleware) && method_exists($middleware, 'handle')) {
            $middleware->handle();
        } elseif (is_callable($middleware)) {
            call_user_func($middleware);
        }
    }

    private static function convertPathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private static function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
}
