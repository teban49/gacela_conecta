<?php

namespace App\Core;

class Request
{
    private static array $requestData = [];
    private array $data = [];
    private array $headers = [];
    private string $method;
    private string $uri;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->headers = $this->parseHeaders();
        $this->data = $this->parseData();
        self::$requestData = $this->data;
    }

    private function parseHeaders(): array
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        return $headers;
    }

    private function parseData(): array
    {
        $contentType = $this->getHeader('Content-Type');

        if ($this->method === 'GET') {
            return $_GET;
        }

        $rawData = file_get_contents('php://input');
        if ($rawData !== '') {
            $decodedData = json_decode($rawData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                return $decodedData;
            }
        }

        if (strpos((string) $contentType, 'application/json') !== false) {
            return [];
        }

        return $_POST;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public static function json(): array
    {
        return self::$requestData;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    public function getBearerToken(): ?string
    {
        $authorization = $this->getHeader('Authorization');
        
        if ($authorization && strpos($authorization, 'Bearer ') === 0) {
            return substr($authorization, 7);
        }

        return null;
    }

    public function getIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        return '0.0.0.0';
    }

    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
