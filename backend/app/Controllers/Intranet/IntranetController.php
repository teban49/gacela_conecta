<?php

namespace App\Controllers\Intranet;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
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
        Response::success($this->service->getDirectorio(), 'Directorio de contactos obtenido.');
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

    public function organigrama(): void
    {
        Response::success($this->service->getOrganigrama(), 'Organigrama corporativo obtenido.');
    }

    public function calidadDocumentos(): void
    {
        $user = Auth::user();
        $request = new Request();
        $companyId = (int) $request->get('EmpresaId');
        if ($companyId < 1) {
            Response::validationError(['EmpresaId' => ['Selecciona una empresa válida.']]);
        }

        if (!$user || !$this->service->userBelongsToCompany((int) ($user['id'] ?? 0), $companyId)) {
            Response::forbidden('No tienes acceso a los documentos de esta empresa.');
        }

        Response::success($this->service->getCalidadDocumentos($companyId), 'Documentos de Calidad obtenidos.');
    }

    public function descargarCalidadDocumento(int $companyId, string $area, string $filename): void
    {
        $user = Auth::user();
        if (!$user || !$this->service->userBelongsToCompany((int) ($user['id'] ?? 0), $companyId)) {
            Response::forbidden('No tienes acceso a los documentos de esta empresa.');
        }

        $path = $this->service->getCalidadDocumentoPath($companyId, $area, rawurldecode($filename));
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

    public function upsertOrganigrama(): void
    {
        $data = Request::json();
        $result = $this->service->upsertOrganigramaNodo($data);
        if ($result['success']) {
            Response::success(null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    public function storeNoticia(): void
    {
        $user = Auth::user();
        $data = Request::json();
        $data['CreadoPorUsuarioId'] = $user['id'] ?? 1;

        $result = $this->service->createNoticia($data);
        if ($result['success']) {
            Response::created($result['data'] ?? null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}
