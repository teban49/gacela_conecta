<?php

use App\Controllers\HorarioController;
use App\Core\Router;

Router::get('/api/v1/horario', function () {
    (new HorarioController())->report();
}, ['jwt']);

Router::post('/api/v1/horario/importar', function () {
    (new HorarioController())->import();
}, ['jwt']);
