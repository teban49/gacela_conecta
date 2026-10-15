<?php

use App\Controllers\Solicitudes\SolicitudController;
use App\Controllers\Solicitudes\SolicitudLaboralController;
use App\Controllers\Solicitudes\CategoriaController;
use App\Core\Router;

/*
|--------------------------------------------------------------------------
| Categorías
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/solicitudes/categorias', function () {
    $controller = new CategoriaController();
    $controller->index();
}, ['jwt', 'solicitudes.administrar']);

Router::get('/api/v1/solicitudes/categorias/active', function () {
    $controller = new CategoriaController();
    $controller->active();
}, ['jwt', 'solicitudes.administrar']);

Router::get('/api/v1/solicitudes/categorias/{id}', function (int $id) {
    $controller = new CategoriaController();
    $controller->show($id);
}, ['jwt', 'solicitudes.administrar']);

Router::post('/api/v1/solicitudes/categorias', function () {
    $controller = new CategoriaController();
    $controller->store();
}, ['jwt', 'solicitudes.administrar_categorias']);

Router::put('/api/v1/solicitudes/categorias/{id}', function (int $id) {
    $controller = new CategoriaController();
    $controller->update($id);
}, ['jwt', 'solicitudes.administrar_categorias']);

Router::patch('/api/v1/solicitudes/categorias/{id}/status', function (int $id) {
    $controller = new CategoriaController();
    $controller->changeStatus($id);
}, ['jwt', 'solicitudes.administrar_categorias']);

/*
|--------------------------------------------------------------------------
| Solicitudes
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/solicitudes', function () {
    $controller = new SolicitudController();
    $controller->index();
}, ['jwt', 'solicitudes.administrar']);

Router::get('/api/v1/solicitudes/status/{estado}', function (string $estado) {
    $controller = new SolicitudController();
    $controller->byStatus($estado);
}, ['jwt', 'solicitudes.administrar']);

Router::get('/api/v1/solicitudes/assignee/{usuarioId}', function (int $usuarioId) {
    $controller = new SolicitudController();
    $controller->byAssignee($usuarioId);
}, ['jwt', 'solicitudes.administrar']);

Router::get('/api/v1/solicitudes/requester/{usuarioId}', function (int $usuarioId) {
    $controller = new SolicitudController();
    $controller->byRequester($usuarioId);
}, ['jwt', 'solicitudes.administrar']);

Router::get('/api/v1/solicitudes/{id}', function (int $id) {
    $controller = new SolicitudController();
    $controller->show($id);
}, ['jwt', 'solicitudes.administrar']);

Router::post('/api/v1/solicitudes', function () {
    $controller = new SolicitudController();
    $controller->store();
}, ['jwt', 'solicitudes.administrar']);

Router::put('/api/v1/solicitudes/{id}', function (int $id) {
    $controller = new SolicitudController();
    $controller->update($id);
}, ['jwt', 'solicitudes.administrar']);

Router::patch('/api/v1/solicitudes/{id}/status', function (int $id) {
    $controller = new SolicitudController();
    $controller->changeStatus($id);
}, ['jwt', 'solicitudes.administrar']);

Router::post('/api/v1/solicitudes/{id}/assign', function (int $id) {
    $controller = new SolicitudController();
    $controller->assignTo($id);
}, ['jwt', 'solicitudes.administrar']);

/*
|--------------------------------------------------------------------------
| Solicitudes laborales y aprobaciones
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/solicitudes/laborales/tipos', function () {
    (new SolicitudLaboralController())->types();
}, ['jwt']);

Router::get('/api/v1/solicitudes/laborales/mis-solicitudes', function () {
    (new SolicitudLaboralController())->mine();
}, ['jwt']);

Router::get('/api/v1/solicitudes/laborales/pendientes', function () {
    (new SolicitudLaboralController())->pending();
}, ['jwt']);

Router::post('/api/v1/solicitudes/laborales', function () {
    (new SolicitudLaboralController())->store();
}, ['jwt']);

Router::get('/api/v1/solicitudes/laborales/{id}/adjuntos/{attachmentId}', function (int $id, int $attachmentId) {
    (new SolicitudLaboralController())->download($id, $attachmentId);
}, ['jwt']);

Router::post('/api/v1/solicitudes/laborales/{id}/decision', function (int $id) {
    (new SolicitudLaboralController())->decide($id);
}, ['jwt']);

Router::get('/api/v1/solicitudes/laborales/{id}', function (int $id) {
    (new SolicitudLaboralController())->show($id);
}, ['jwt']);