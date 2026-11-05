<?php

use App\Controllers\HorarioController;
use App\Core\Router;

Router::get('/api/v1/horario', function () {
    (new HorarioController())->report();
}, ['jwt']);
