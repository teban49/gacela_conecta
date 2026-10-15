<?php

use App\Controllers\Security\ApplicationController;
use App\Controllers\Security\PermissionController;
use App\Controllers\Security\RoleController;
use App\Controllers\Security\UserController;
use App\Core\Router;

/*
|--------------------------------------------------------------------------
| Aplicaciones
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/security/applications', function () {
    $controller = new ApplicationController();
    $controller->index();
}, ['jwt', 'seguridad.ver']);

Router::get('/api/v1/security/applications/{id}', function (int $id) {
    $controller = new ApplicationController();
    $controller->show($id);
}, ['jwt', 'seguridad.ver']);

Router::post('/api/v1/security/applications', function () {
    $controller = new ApplicationController();
    $controller->store();
}, ['jwt', 'seguridad.administrar_aplicaciones']);

Router::put('/api/v1/security/applications/{id}', function (int $id) {
    $controller = new ApplicationController();
    $controller->update($id);
}, ['jwt', 'seguridad.administrar_aplicaciones']);

Router::patch('/api/v1/security/applications/{id}/status', function (int $id) {
    $controller = new ApplicationController();
    $controller->changeStatus($id);
}, ['jwt', 'seguridad.administrar_aplicaciones']);

Router::patch('/api/v1/security/applications/{id}/visibility', function (int $id) {
    $controller = new ApplicationController();
    $controller->changeVisibility($id);
}, ['jwt', 'seguridad.administrar_aplicaciones']);

/*
|--------------------------------------------------------------------------
| Permisos
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/security/permissions', function () {
    $controller = new PermissionController();
    $controller->index();
}, ['jwt', 'seguridad.ver']);

Router::get('/api/v1/security/permissions/application/{applicationId}', function (int $applicationId) {
    $controller = new PermissionController();
    $controller->byApplication($applicationId);
}, ['jwt', 'seguridad.ver']);

Router::post('/api/v1/security/permissions', function () {
    $controller = new PermissionController();
    $controller->store();
}, ['jwt', 'seguridad.administrar_permisos']);

Router::put('/api/v1/security/permissions/{id}', function (int $id) {
    $controller = new PermissionController();
    $controller->update($id);
}, ['jwt', 'seguridad.administrar_permisos']);

Router::patch('/api/v1/security/permissions/{id}/status', function (int $id) {
    $controller = new PermissionController();
    $controller->changeStatus($id);
}, ['jwt', 'seguridad.administrar_permisos']);

/*
|--------------------------------------------------------------------------
| Roles
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/security/roles', function () {
    $controller = new RoleController();
    $controller->index();
}, ['jwt', 'seguridad.ver']);

Router::get('/api/v1/security/roles/{id}', function (int $id) {
    $controller = new RoleController();
    $controller->show($id);
}, ['jwt', 'seguridad.ver']);

Router::post('/api/v1/security/roles', function () {
    $controller = new RoleController();
    $controller->store();
}, ['jwt', 'seguridad.administrar_roles']);

Router::put('/api/v1/security/roles/{id}', function (int $id) {
    $controller = new RoleController();
    $controller->update($id);
}, ['jwt', 'seguridad.administrar_roles']);

Router::patch('/api/v1/security/roles/{id}/status', function (int $id) {
    $controller = new RoleController();
    $controller->changeStatus($id);
}, ['jwt', 'seguridad.administrar_roles']);

Router::post('/api/v1/security/roles/{id}/permissions', function (int $id) {
    $controller = new RoleController();
    $controller->syncPermissions($id);
}, ['jwt', 'seguridad.administrar_roles']);

/*
|--------------------------------------------------------------------------
| Usuarios
|--------------------------------------------------------------------------
*/
Router::get('/api/v1/security/job-titles', function () {
    $controller = new UserController();
    $controller->jobTitles();
}, ['jwt', 'seguridad.ver']);

Router::get('/api/v1/security/users', function () {
    $controller = new UserController();
    $controller->index();
}, ['jwt', 'seguridad.ver']);

Router::get('/api/v1/security/users/{id}', function (int $id) {
    $controller = new UserController();
    $controller->show($id);
}, ['jwt', 'seguridad.ver']);

Router::post('/api/v1/security/users', function () {
    $controller = new UserController();
    $controller->store();
}, ['jwt', 'seguridad.administrar_usuarios']);

Router::put('/api/v1/security/users/{id}', function (int $id) {
    $controller = new UserController();
    $controller->update($id);
}, ['jwt', 'seguridad.administrar_usuarios']);

Router::patch('/api/v1/security/users/{id}/status', function (int $id) {
    $controller = new UserController();
    $controller->changeStatus($id);
}, ['jwt', 'seguridad.administrar_usuarios']);

Router::post('/api/v1/security/users/{id}/roles', function (int $id) {
    $controller = new UserController();
    $controller->setRole($id);
}, ['jwt', 'seguridad.administrar_usuarios']);

Router::get('/api/v1/security/users/{id}/permissions', function (int $id) {
    $controller = new UserController();
    $controller->permissions($id);
}, ['jwt', 'seguridad.ver']);