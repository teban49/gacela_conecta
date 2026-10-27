<?php

return [
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'true') === 'true',
    'url' => $_ENV['APP_URL'] ?? 'localhost:8001',
    'timezone' => 'America/Bogota',
    'locale' => 'es',
];
