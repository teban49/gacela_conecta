<?php

use App\Controllers\Intranet\NoticiaController;
use App\Controllers\Intranet\DocumentoController;
use App\Controllers\Intranet\EventoController;
use App\Controllers\Intranet\IntranetController;
use App\Core\Router;

Router::get('/api/v1/intranet/home', function () {
    $controller = new IntranetController();
    $controller->home();
}, ['jwt', 'intranet.ver']);

Router::get('/api/v1/intranet/directorio', function () {
    $controller = new IntranetController();
    $controller->directorio();
}, ['jwt', 'intranet.ver']);

Router::put('/api/v1/intranet/directorio/{id}', function (int $id) {
    $controller = new IntranetController();
    $controller->updateDirectorio($id);
}, ['jwt', 'intranet.administrar_directorio']);

/*
|--------------------------------------------------------------------------
| Noticias
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/intranet/noticias', function () {
    $controller = new NoticiaController();
    $controller->index();
}, ['jwt', 'intranet.ver']);

Router::get('/api/v1/intranet/noticias/published', function () {
    $controller = new NoticiaController();
    $controller->published();
}, ['jwt', 'intranet.ver']);

Router::get('/api/v1/intranet/noticias/category/{categoria}', function (string $categoria) {
    $controller = new NoticiaController();
    $controller->byCategory($categoria);
}, ['jwt', 'intranet.ver']);

Router::get('/api/v1/intranet/noticias/{id}', function (int $id) {
    $controller = new NoticiaController();
    $controller->show($id);
}, ['jwt', 'intranet.ver']);

Router::post('/api/v1/intranet/noticias/{id}/view', function (int $id) {
    $controller = new NoticiaController();
    $controller->markAsViewed($id);
}, ['jwt', 'intranet.ver']);

Router::post('/api/v1/intranet/noticias', function () {
    $controller = new NoticiaController();
    $controller->store();
}, ['jwt', 'intranet.administrar_noticias']);

Router::put('/api/v1/intranet/noticias/{id}', function (int $id) {
    $controller = new NoticiaController();
    $controller->update($id);
}, ['jwt', 'intranet.administrar_noticias']);

Router::patch('/api/v1/intranet/noticias/{id}/status', function (int $id) {
    $controller = new NoticiaController();
    $controller->changeStatus($id);
}, ['jwt', 'intranet.administrar_noticias']);

/*
|--------------------------------------------------------------------------
| Documentos
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/intranet/documentos', function () {
    $controller = new DocumentoController();
    $controller->index();
}, ['jwt', 'intranet.ver']);

Router::get('/api/v1/intranet/documentos/category/{categoria}', function (string $categoria) {
    $controller = new DocumentoController();
    $controller->byCategory($categoria);
}, ['jwt', 'intranet.ver']);

Router::get('/api/v1/intranet/documentos/{id}', function (int $id) {
    $controller = new DocumentoController();
    $controller->show($id);
}, ['jwt', 'intranet.ver']);

Router::post('/api/v1/intranet/documentos/{id}/download', function (int $id) {
    $controller = new DocumentoController();
    $controller->download($id);
}, ['jwt', 'intranet.ver']);

Router::post('/api/v1/intranet/documentos', function () {
    $controller = new DocumentoController();
    $controller->store();
}, ['jwt', 'intranet.administrar_documentos']);

Router::put('/api/v1/intranet/documentos/{id}', function (int $id) {
    $controller = new DocumentoController();
    $controller->update($id);
}, ['jwt', 'intranet.administrar_documentos']);

Router::patch('/api/v1/intranet/documentos/{id}/status', function (int $id) {
    $controller = new DocumentoController();
    $controller->changeStatus($id);
}, ['jwt', 'intranet.administrar_documentos']);

/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/intranet/eventos', function () {
    $controller = new EventoController();
    $controller->index();
}, ['jwt', 'intranet.ver']);

Router::post('/api/v1/intranet/eventos/daterange', function () {
    $controller = new EventoController();
    $controller->byDateRange();
}, ['jwt', 'intranet.ver']);

Router::post('/api/v1/intranet/eventos/upcoming', function () {
    $controller = new EventoController();
    $controller->upcoming();
}, ['jwt', 'intranet.ver']);

Router::get('/api/v1/intranet/eventos/{id}', function (int $id) {
    $controller = new EventoController();
    $controller->show($id);
}, ['jwt', 'intranet.ver']);

Router::post('/api/v1/intranet/eventos', function () {
    $controller = new EventoController();
    $controller->store();
}, ['jwt', 'intranet.administrar_eventos']);

Router::put('/api/v1/intranet/eventos/{id}', function (int $id) {
    $controller = new EventoController();
    $controller->update($id);
}, ['jwt', 'intranet.administrar_eventos']);

Router::patch('/api/v1/intranet/eventos/{id}/status', function (int $id) {
    $controller = new EventoController();
    $controller->changeStatus($id);
}, ['jwt', 'intranet.administrar_eventos']);