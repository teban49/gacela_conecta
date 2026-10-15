<?php

$getEnvVar = function ($key, $default = '') {
    return getenv($key) ?: ($_ENV[$key] ?? $_SERVER[$key] ?? $default);
};

return [
    'host'       => $getEnvVar('MAIL_HOST', 'smtp.gmail.com'),
    'port'       => (int)$getEnvVar('MAIL_PORT', 587),
    'encryption' => $getEnvVar('MAIL_ENCRYPTION', 'tls'),
    'username'   => $getEnvVar('MAIL_USERNAME'),
    'password'   => $getEnvVar('MAIL_PASSWORD'),
    'from_email' => $getEnvVar('MAIL_FROM_ADDRESS') ?: $getEnvVar('MAIL_USERNAME'),
    'from_name'  => $getEnvVar('MAIL_FROM_NAME', 'Gacela Conecta'),
];