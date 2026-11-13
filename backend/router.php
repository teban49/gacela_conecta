<?php

$publicDirectory = realpath(__DIR__ . '/public');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestedFile = realpath(
    $publicDirectory . DIRECTORY_SEPARATOR . ltrim(rawurldecode((string) $requestPath), '/\\')
);

if (
    $requestedFile !== false
    && is_file($requestedFile)
    && str_starts_with($requestedFile, $publicDirectory . DIRECTORY_SEPARATOR)
) {
    return false;
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
if ($origin !== '*') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require $publicDirectory . DIRECTORY_SEPARATOR . 'index.php';
