<?php

use App\Controllers\Auth\AuthController;
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

// Auth routes
Router::post('/api/v1/auth/magic-link', function() {
    $controller = new AuthController();
    $controller->requestMagicLink();
});

Router::post('/api/v1/auth/verify-token', function() {
    $controller = new AuthController();
    $controller->verifyToken();
});

Router::get('/api/v1/auth/me', function() {
    $controller = new AuthController();
    $controller->me();
}, ['jwt']);

Router::post('/api/v1/auth/logout', function() {
    $controller = new AuthController();
    $controller->logout();
});
