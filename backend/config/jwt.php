<?php

return [
    'secret' => $_ENV['JWT_SECRET'] ?? '67834e8e75b61fdb75d2eb2637584cf971faada269be36e9178ed2194cce0d57',
    'algorithm' => 'HS256',
    'expiration' => $_ENV['JWT_EXPIRATION'] ?? 43200, // 12 hours in seconds
    'magic_link_expiration' => 900, // 15 minutes in seconds
];
