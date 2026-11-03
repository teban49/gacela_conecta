<?php

use App\Controllers\DotacionController;
use App\Core\Router;

Router::get('/api/v1/dotacion', function () {
    (new DotacionController())->index();
}, ['jwt']);

Router::post('/api/v1/dotacion', function () {
    (new DotacionController())->store();
}, ['jwt']);

Router::post('/api/v1/dotacion/{id}/decision', function (int $id) {
    (new DotacionController())->decide($id);
}, ['jwt']);

Router::post('/api/v1/dotacion/{id}/recibido', function (int $id) {
    (new DotacionController())->received($id);
}, ['jwt']);

Router::get('/api/v1/dotacion/entregas', function () {
    (new DotacionController())->deliveries();
}, ['jwt']);

Router::get('/api/v1/dotacion/{id}', function (int $id) {
    (new DotacionController())->show($id);
}, ['jwt']);
