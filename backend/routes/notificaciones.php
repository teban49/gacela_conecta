<?php

use App\Controllers\NotificacionController;
use App\Core\Router;

Router::get('/api/v1/notificaciones', function () {
    (new NotificacionController())->index();
}, ['jwt']);

Router::post('/api/v1/notificaciones/leer-todas', function () {
    (new NotificacionController())->markAllRead();
}, ['jwt']);

Router::post('/api/v1/notificaciones/{id}/leer', function (int $id) {
    (new NotificacionController())->markRead($id);
}, ['jwt']);
