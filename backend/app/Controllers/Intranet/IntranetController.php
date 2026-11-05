<?php

namespace App\Controllers\Intranet;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\RoleAccess;
use App\Services\Intranet\IntranetService;

class IntranetController
{
    private IntranetService $service;

    public function __construct()
    {
        $this->service = new IntranetService();
    }

    public function home(): void
    {
        Response::success($this->service->getHomeData(), 'Datos principales de intranet obtenidos.');
    }

    public function directorio(): void
    {
        $rows = $this->service->getDirectorio();
        if (!RoleAccess::isAdmin(Auth::user())) {
            $rows = array_map(static function (array $row): array {
                unset($row['fecha_nacimiento']);
                return $row;
            }, $rows);
        }
        Response::success($rows, 'Directorio de contactos obtenido.');
    }

    /** Cumpleaños sin año de nacimiento: visible para todo el personal. */
    public function cumpleanos(): void
    {
        $rows = array_filter($this->service->getDirectorio(), static fn (array $row) => !empty($row['fecha_nacimiento']));
        $result = array_map(static fn (array $row) => [
            'id' => (int) $row['id'],
            'nombre' => trim($row['first_name'] . ' ' . $row['last_name']),
            'area' => $row['area_agencia'],
            'cargo' => $row['cargo'],
            'empresa' => $row['empresa'],
            'mes' => (int) substr($row['fecha_nacimiento'], 5, 2),
            'dia' => (int) substr($row['fecha_nacimiento'], 8, 2),
        ], $rows);
        Response::success(array_values($result), 'Cumpleaños obtenidos.');
    }

    public function updateDirectorio(int $id): void
    {
        $result = $this->service->updateDirectorio($id, Request::json());
        if ($result['success']) {
            Response::success(null, $result['message']);
            return;
        }
        if (!empty($result['errors'])) {
            Response::validationError($result['errors'], $result['message']);
            return;
        }
        Response::error($result['message'], [], 404);
    }

    public function createDirectorio(): void
    {
        $result = $this->service->createDirectorio(Request::json());
        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
            return;
        }
        Response::validationError($result['errors'] ?? [], $result['message']);
    }

    public function deleteDirectorio(int $id): void
    {
        $result = $this->service->deleteDirectorio($id);
        if ($result['success']) {
            Response::success(null, $result['message']);
            return;
        }
        Response::error($result['message'], [], 404);
    }

    public function documentos(): void
    {
        $request = new Request();
        $empresaId = $request->get('EmpresaId') ? (int) $request->get('EmpresaId') : null;
        $areaId = $request->get('AreaId') ? (int) $request->get('AreaId') : null;
        $categoriaId = $request->get('CategoriaId') ? (int) $request->get('CategoriaId') : null;
        $search = $request->get('q') ? (string) $request->get('q') : null;

        $docs = $this->service->getDocumentos($empresaId, $areaId, $categoriaId, $search);
        Response::success($docs, 'Documentos de intranet obtenidos.');
    }

    public function storeDocumento(): void
    {
        $user = Auth::user();
        $data = Request::json();
        $data['CreadoPorUsuarioId'] = $user['id'] ?? 1;

        $result = $this->service->createDocumento($data);
        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function updateVersion(int $id): void
    {
        $data = Request::json();
        $nuevaVersion = $data['Version'] ?? '1.1';
        $nuevaRuta = $data['RutaArchivo'] ?? '';

        $result = $this->service->updateDocumentoVersion($id, $nuevaVersion, $nuevaRuta);
        if ($result['success']) {
            Response::success(null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function calidadDocumentos(): void
    {
        $user = Auth::user();
        $request = new Request();
        $companyId = (int) $request->get('EmpresaId');
        if ($companyId < 1) {
            Response::validationError(['EmpresaId' => ['Selecciona una empresa válida.']]);
        }

        if (!$user || !$this->canAccessCompany($user, $companyId)) {
            Response::forbidden('No tienes acceso a los documentos de esta empresa.');
        }

        $documents = $this->service->getCalidadDocumentos($companyId);
        $areas = RoleAccess::calidadAreas($user);
        if ($areas !== null) {
            $documents = array_values(array_filter($documents, static fn (array $doc) => in_array($doc['area'] ?? '', $areas, true)));
        }

        Response::success($documents, 'Documentos de Calidad obtenidos.');
    }

    public function descargarCalidadDocumento(int $companyId, string $area, string $filename): void
    {
        $user = Auth::user();
        if (!$user || !$this->canAccessCompany($user, $companyId)) {
            Response::forbidden('No tienes acceso a los documentos de esta empresa.');
        }
        if (!RoleAccess::canViewCalidadArea($user, rawurldecode($area))) {
            Response::forbidden('Tu rol no tiene acceso a los documentos de esta área.');
        }

        $path = $this->service->getCalidadDocumentoPath(
            $companyId,
            $area,
            is_string($_GET['Categoria'] ?? null) ? $_GET['Categoria'] : '',
            rawurldecode($filename)
        );
        if ($path === null) {
            Response::notFound('El documento no existe.');
        }

        $name = basename($path);
        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . addcslashes($name, "\\\"") . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    public function uploadCalidadDocumento(int $companyId, string $area): void
    {
        $this->authorizeCalidadAdmin($companyId);
        $categoria = is_string($_POST['Categoria'] ?? null) ? $_POST['Categoria'] : '';
        $result = $this->service->uploadCalidadDocumento($companyId, rawurldecode($area), $categoria, $_FILES['Archivo'] ?? null);

        if (!$result['success']) {
            Response::error($result['message'], [], $result['status'] ?? 400);
        }
        Response::created($result['data'] ?? null, $result['message']);
    }

    public function eliminarCalidadDocumento(int $companyId, string $area, string $filename): void
    {
        $this->authorizeCalidadAdmin($companyId);
        $categoria = is_string($_GET['Categoria'] ?? null) ? $_GET['Categoria'] : '';
        $result = $this->service->deleteCalidadDocumento($companyId, rawurldecode($area), $categoria, rawurldecode($filename));

        if (!$result['success']) {
            Response::error($result['message'], [], $result['status'] ?? 400);
        }
        Response::success(null, $result['message']);
    }

    private function canAccessCompany(array $user, int $companyId): bool
    {
        return RoleAccess::seesAllCompanies($user)
            || $this->service->userBelongsToCompany((int) ($user['id'] ?? 0), $companyId);
    }

    private function authorizeCalidadAdmin(int $companyId): void
    {
        $user = Auth::user();
        if (!$user || !RoleAccess::isAdmin($user)) {
            Response::forbidden('Solo el administrador puede gestionar los documentos de Calidad.');
        }
        if (!$this->canAccessCompany($user, $companyId)) {
            Response::forbidden('No tienes acceso a los documentos de esta empresa.');
        }
    }
}
