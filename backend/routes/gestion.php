<?php

use App\Controllers\Gestion\ActivoController;
use App\Controllers\Gestion\IncidenciaController;
use App\Controllers\Gestion\LicenciaController;
use App\Controllers\Gestion\MantenimientoController;
use App\Core\Router;

Router::get('/api/v1/gestion/licencias/opciones', function () {
    (new LicenciaController())->options();
}, ['jwt', 'gestion.ver']);

Router::get('/api/v1/gestion/licencias', function () {
    (new LicenciaController())->index();
}, ['jwt', 'gestion.ver']);

Router::post('/api/v1/gestion/licencias', function () {
    (new LicenciaController())->store();
}, ['jwt', 'gestion.administrar_licencias']);

Router::put('/api/v1/gestion/licencias/{id}', function (int $id) {
    (new LicenciaController())->update($id);
}, ['jwt', 'gestion.administrar_licencias']);

Router::get('/api/v1/gestion/mantenimientos/pendientes', function () {
    (new MantenimientoController())->pending();
}, ['jwt', 'gestion.ver']);

Router::post('/api/v1/gestion/mantenimientos/{activoId}/anio/{anio}/semestre/{semestre}/completar', function (int $activoId, int $anio, int $semestre) {
    (new MantenimientoController())->complete($activoId, $anio, $semestre);
}, ['jwt', 'gestion.administrar_mantenimiento']);

Router::get('/api/v1/gestion/incidencias/categorias', function () {
    (new IncidenciaController())->categories();
}, ['jwt', 'gestion.incidencias.ver']);

Router::get('/api/v1/gestion/incidencias/contexto', function () {
    (new IncidenciaController())->context();
}, ['jwt', 'gestion.incidencias.ver']);

Router::get('/api/v1/gestion/incidencias/usuarios', function () {
    (new IncidenciaController())->assignableUsers();
}, ['jwt', 'gestion.administrar_incidencias']);

Router::get('/api/v1/gestion/incidencias', function () {
    (new IncidenciaController())->index();
}, ['jwt', 'gestion.incidencias.ver']);

Router::post('/api/v1/gestion/incidencias', function () {
    (new IncidenciaController())->store();
}, ['jwt', 'gestion.incidencias.ver']);

Router::get('/api/v1/gestion/incidencias/{id}/adjunto', function (int $id) {
    (new IncidenciaController())->downloadAttachment($id);
}, ['jwt', 'gestion.incidencias.ver']);

Router::get('/api/v1/gestion/incidencias/{id}', function (int $id) {
    (new IncidenciaController())->show($id);
}, ['jwt', 'gestion.incidencias.ver']);

Router::patch('/api/v1/gestion/incidencias/{id}', function (int $id) {
    (new IncidenciaController())->update($id);
}, ['jwt', 'gestion.administrar_incidencias']);

Router::post('/api/v1/gestion/incidencias/{id}/cerrar', function (int $id) {
    (new IncidenciaController())->closeByRequester($id);
}, ['jwt']);

Router::get('/api/v1/gestion/usuarios', function () {
    $controller = new ActivoController();
    $controller->assignableUsers();
}, ['jwt', 'gestion.administrar_activos']);

Router::get('/api/v1/gestion/activos/asignaciones-pendientes', function () {
    (new ActivoController())->pendingAssignments();
}, ['jwt']);

Router::post('/api/v1/gestion/activos/asignaciones/{asignacionId}/aceptar', function (int $asignacionId) {
    (new ActivoController())->respondToAssignment($asignacionId, true);
}, ['jwt']);

Router::post('/api/v1/gestion/activos/asignaciones/{asignacionId}/rechazar', function (int $asignacionId) {
    (new ActivoController())->respondToAssignment($asignacionId, false);
}, ['jwt']);

Router::get('/api/v1/gestion/activos/categorias', function () {
    $controller = new ActivoController();
    $controller->categories();
}, ['jwt', 'gestion.ver']);

Router::get('/api/v1/gestion/empresas', function () {
    $controller = new ActivoController();
    $controller->companies();
}, ['jwt', 'gestion.ver']);

Router::get('/api/v1/gestion/proveedores', function () {
    $controller = new ActivoController();
    $controller->suppliers();
}, ['jwt', 'gestion.ver']);

Router::post('/api/v1/gestion/proveedores', function () {
    $controller = new ActivoController();
    $controller->storeSupplier();
}, ['jwt', 'gestion.administrar_activos']);

/*
|--------------------------------------------------------------------------
| Activos Tecnológicos
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/gestion/activos', function () {
    $controller = new ActivoController();
    $controller->index();
}, ['jwt', 'gestion.ver']);

Router::get('/api/v1/gestion/activos/category/{categoriaId}', function (int $categoriaId) {
    $controller = new ActivoController();
    $controller->byCategory($categoriaId);
}, ['jwt', 'gestion.ver']);

Router::get('/api/v1/gestion/activos/assignee/{usuarioId}', function (int $usuarioId) {
    $controller = new ActivoController();
    $controller->byAssignee($usuarioId);
}, ['jwt', 'gestion.ver']);

Router::get('/api/v1/gestion/activos/{id}', function (int $id) {
    $controller = new ActivoController();
    $controller->show($id);
}, ['jwt', 'gestion.ver']);

Router::post('/api/v1/gestion/activos', function () {
    $controller = new ActivoController();
    $controller->store();
}, ['jwt', 'gestion.administrar_activos']);

Router::put('/api/v1/gestion/activos/{id}', function (int $id) {
    $controller = new ActivoController();
    $controller->update($id);
}, ['jwt', 'gestion.administrar_activos']);

Router::patch('/api/v1/gestion/activos/{id}/status', function (int $id) {
    $controller = new ActivoController();
    $controller->changeStatus($id);
}, ['jwt', 'gestion.administrar_activos']);

Router::patch('/api/v1/gestion/activos/{id}/asset-status', function (int $id) {
    $controller = new ActivoController();
    $controller->changeAssetStatus($id);
}, ['jwt', 'gestion.administrar_activos']);

Router::post('/api/v1/gestion/activos/{id}/assign', function (int $id) {
    $controller = new ActivoController();
    $controller->assignTo($id);
}, ['jwt', 'gestion.administrar_activos']);

Router::delete('/api/v1/gestion/activos/{id}/assignment', function (int $id) {
    $controller = new ActivoController();
    $controller->unassign($id);
}, ['jwt', 'gestion.administrar_activos']);

Router::post('/api/v1/gestion/activos/{id}/retire', function (int $id) {
    $controller = new ActivoController();
    $controller->retire($id);
}, ['jwt', 'gestion.administrar_activos']);