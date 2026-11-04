<?php

namespace App\Services\Intranet;

use App\Repositories\Intranet\IntranetRepository;
use App\Repositories\Intranet\DocumentRepository;
use App\Repositories\Intranet\DocumentoRepository;
use App\Repositories\Intranet\NoticiaRepository;
use App\Repositories\Intranet\CalidadDocumentoRepository;
use App\Helpers\UploadHelper;

class IntranetService
{
    private IntranetRepository $intranetRepo;
    private DocumentRepository $documentRepo;
    private CalidadDocumentoRepository $calidadDocumentoRepo;

    public function __construct()
    {
        $this->intranetRepo = new IntranetRepository();
        $this->documentRepo = new DocumentRepository();
        $this->calidadDocumentoRepo = new CalidadDocumentoRepository();
    }

    public function getHomeData(): array
    {
        $noticias = array_map([NoticiaService::class, 'withImageUrl'], (new NoticiaRepository())->getPublished());

        $manuales = (new DocumentoRepository())->getByCategory('manuales');
        foreach ($manuales as &$manual) {
            $manual['Titulo'] = $manual['Nombre'];
        }
        unset($manual);

        return [
            'empresas' => $this->intranetRepo->getEmpresas(),
            'areas' => $this->intranetRepo->getAreas(),
            'categorias' => $this->intranetRepo->getCategorias(),
            'noticias' => $noticias,
            'manuales' => $manuales,
        ];
    }

    public function getDirectorio(): array
    {
        return $this->intranetRepo->getDirectorio();
    }

    public function createDirectorio(array $data): array
    {
        $validated = $this->validateDirectorio($data);
        if ($validated['errors']) {
            return ['success' => false, 'errors' => $validated['errors'], 'message' => 'Revisa los datos del contacto.'];
        }

        $id = $this->intranetRepo->createDirectorio($validated['values']);
        return ['success' => true, 'message' => 'Contacto agregado correctamente.', 'data' => ['id' => $id]];
    }

    public function deleteDirectorio(int $id): array
    {
        if (!$this->intranetRepo->directorioExists($id)) {
            return ['success' => false, 'message' => 'El contacto no existe.'];
        }
        if (!$this->intranetRepo->deleteDirectorio($id)) {
            return ['success' => false, 'message' => 'No fue posible eliminar el contacto.'];
        }

        return ['success' => true, 'message' => 'Contacto eliminado correctamente.'];
    }

    public function updateDirectorio(int $id, array $data): array
    {
        $validated = $this->validateDirectorio($data);
        if ($validated['errors']) {
            return ['success' => false, 'errors' => $validated['errors'], 'message' => 'Revisa los datos del contacto.'];
        }

        if (!$this->intranetRepo->directorioExists($id)) {
            return ['success' => false, 'message' => 'El contacto no existe.'];
        }
        if (!$this->intranetRepo->updateDirectorio($id, $validated['values'])) {
            return ['success' => false, 'message' => 'No fue posible actualizar el contacto.'];
        }

        return ['success' => true, 'message' => 'Contacto actualizado correctamente.'];
    }

    private function validateDirectorio(array $data): array
    {
        $limits = [
            'first_name' => 100,
            'last_name' => 120,
            'address' => 255,
            'phone' => 30,
            'email' => 150,
            'extension' => 30,
            'area_agencia' => 120,
            'cargo' => 120,
            'empresa' => 120,
        ];
        $values = [];
        $errors = [];

        foreach ($limits as $field => $limit) {
            $value = $data[$field] ?? null;
            if ($value !== null && !is_string($value)) {
                $errors[$field] = ['El valor debe ser texto.'];
                continue;
            }
            $value = is_string($value) ? trim($value) : '';
            if (($field === 'first_name' || $field === 'last_name') && $value === '') {
                $errors[$field] = ['Este campo es obligatorio.'];
            } elseif (mb_strlen($value) > $limit) {
                $errors[$field] = ["El campo no puede superar {$limit} caracteres."];
            }
            $values[$field] = $value === '' ? null : $value;
        }

        if (($values['email'] ?? null) !== null && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Ingresa un correo electrónico válido.'];
        }

        return ['values' => $values, 'errors' => $errors];
    }

    public function getDocumentos(?int $empresaId = null, ?int $areaId = null, ?int $categoriaId = null, ?string $search = null): array
    {
        return $this->documentRepo->getDocumentos($empresaId, $areaId, $categoriaId, $search);
    }

    public function createDocumento(array $data): array
    {
        $id = $this->documentRepo->create($data);
        return [
            'success' => true,
            'message' => 'Documento cargado exitosamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function updateDocumentoVersion(int $id, string $nuevaVersion, string $nuevaRuta): array
    {
        $updated = $this->documentRepo->updateVersion($id, $nuevaVersion, $nuevaRuta);
        return [
            'success' => $updated,
            'message' => $updated ? 'Versión de documento actualizada.' : 'No fue posible actualizar la versión.'
        ];
    }

    public function userBelongsToCompany(int $userId, int $companyId): bool
    {
        return $this->intranetRepo->userBelongsToCompany($userId, $companyId);
    }

    public function getCalidadDocumentos(int $companyId): array
    {
        return $this->calidadDocumentoRepo->getDocuments($companyId);
    }

    public function getCalidadDocumentoPath(int $companyId, string $area, string $categoria, string $filename): ?string
    {
        return $this->calidadDocumentoRepo->findDocument($companyId, $area, $categoria, $filename);
    }

    public function uploadCalidadDocumento(int $companyId, string $area, string $categoria, ?array $file): array
    {
        $directory = $this->calidadDocumentoRepo->getUploadDirectory($companyId, $area, $categoria);
        if ($directory === null) {
            return ['success' => false, 'status' => 422, 'message' => 'Selecciona un área y una categoría válidas.'];
        }
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'status' => 422, 'message' => 'Selecciona el archivo PDF a subir.'];
        }

        $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
        $name = trim(preg_replace('/[^\p{L}\p{N} ._()\-]/u', '', pathinfo($original, PATHINFO_FILENAME)));
        $name = mb_substr($name, 0, 150);
        if ($name === '') {
            return ['success' => false, 'status' => 422, 'message' => 'El nombre del archivo no es válido.'];
        }
        $name .= '.pdf';

        if (is_file($directory . DIRECTORY_SEPARATOR . $name)) {
            return ['success' => false, 'status' => 409, 'message' => 'Ya existe un documento con ese nombre. Elimínalo primero o cambia el nombre del archivo.'];
        }

        $upload = UploadHelper::store($file, $directory, UploadHelper::PDF_TYPES, 20 * 1024 * 1024, $name);
        if (isset($upload['error'])) {
            return ['success' => false, 'status' => 422, 'message' => $upload['error']];
        }

        return ['success' => true, 'message' => 'Documento cargado correctamente.', 'data' => ['archivo' => $name]];
    }

    public function deleteCalidadDocumento(int $companyId, string $area, string $categoria, string $filename): array
    {
        $path = $this->calidadDocumentoRepo->findDocument($companyId, $area, $categoria, $filename);
        if ($path === null) {
            return ['success' => false, 'status' => 404, 'message' => 'El documento no existe.'];
        }
        if (!unlink($path)) {
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible eliminar el documento.'];
        }

        return ['success' => true, 'message' => 'Documento eliminado correctamente.'];
    }
}
