<?php

namespace App\Services\Intranet;

use App\Helpers\UploadHelper;
use JsonException;

class DocumentLibraryService
{
    private const CATEGORIES = [
        'gestion humana',
        'compras',
        'servicio al cliente',
        'sistemas',
    ];

    private function libraryConfig(string $kind): ?array
    {
        if (!in_array($kind, ['formatos', 'documentacion'], true)) {
            return null;
        }

        $frontend = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'frontend';
        $mediaPath = DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'intranet' . DIRECTORY_SEPARATOR . $kind;
        $base = $frontend . DIRECTORY_SEPARATOR . 'public' . $mediaPath;

        return [
            'base' => $base,
            'catalog' => $base . DIRECTORY_SEPARATOR . 'catalog.json',
            'distRoot' => $frontend . DIRECTORY_SEPARATOR . 'dist',
            'distBase' => $frontend . DIRECTORY_SEPARATOR . 'dist' . $mediaPath,
        ];
    }

    private function syncCatalogToDist(array $config): bool
    {
        if (!is_dir($config['distRoot'])) {
            return true;
        }
        if (!is_dir($config['distBase']) && !mkdir($config['distBase'], 0770, true) && !is_dir($config['distBase'])) {
            return false;
        }

        return copy($config['catalog'], $config['distBase'] . DIRECTORY_SEPARATOR . 'catalog.json');
    }

    private function syncPdfToDist(array $config, string $company, string $category, string $filename): bool
    {
        if (!is_dir($config['distRoot'])) {
            return true;
        }

        $sourceDirectory = $config['base'] . DIRECTORY_SEPARATOR . $company;
        $distDirectory = $config['distBase'] . DIRECTORY_SEPARATOR . $company;
        if ($category !== '') {
            $sourceDirectory .= DIRECTORY_SEPARATOR . $category;
            $distDirectory .= DIRECTORY_SEPARATOR . $category;
        }
        if (!is_dir($distDirectory) && !mkdir($distDirectory, 0770, true) && !is_dir($distDirectory)) {
            return false;
        }

        return copy($sourceDirectory . DIRECTORY_SEPARATOR . $filename, $distDirectory . DIRECTORY_SEPARATOR . $filename);
    }

    private function deletePdfFromDist(array $config, string $company, string $category, string $filename): bool
    {
        if (!is_dir($config['distRoot'])) {
            return true;
        }

        $path = $config['distBase'] . DIRECTORY_SEPARATOR . $company;
        if ($category !== '') {
            $path .= DIRECTORY_SEPARATOR . $category;
        }
        $path .= DIRECTORY_SEPARATOR . $filename;

        return !is_file($path) || unlink($path);
    }

    private function readCatalog(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return ['error' => 'No se pudo leer el catálogo de documentos.'];
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return ['error' => 'No se pudo leer el catálogo de documentos.'];
        }

        try {
            $catalog = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['error' => 'El catálogo de documentos contiene JSON inválido.'];
        }

        if (!is_array($catalog) || !str_starts_with(ltrim($contents), '{')) {
            return ['error' => 'El catálogo de documentos tiene un formato inválido.'];
        }

        return ['catalog' => $catalog];
    }

    private function writeCatalog(string $path, array $catalog): bool
    {
        try {
            $json = json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
    }

    private function validateLocation(string $kind, array $catalog, array $data): array
    {
        $company = is_string($data['Empresa'] ?? null) ? trim($data['Empresa']) : '';
        $category = is_string($data['Categoria'] ?? null) ? trim($data['Categoria']) : '';
        $errors = [];

        if ($company === '' || !array_key_exists($company, $catalog) || !is_array($catalog[$company])) {
            $errors['Empresa'] = ['Selecciona una empresa válida.'];
        }

        if ($kind === 'formatos' && !in_array($category, self::CATEGORIES, true)) {
            $errors['Categoria'] = ['Selecciona una categoría válida.'];
        } elseif ($kind === 'documentacion' && $category !== '') {
            $errors['Categoria'] = ['La documentación no usa categorías.'];
        }

        return [$company, $category, $errors];
    }

    private function sectionKey(string $kind, string $category): string
    {
        return $kind === 'formatos' ? $category : 'documentos';
    }

    private function uploadName(?array $file): ?string
    {
        $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
        $cleanName = preg_replace('/[^\p{L}\p{N} ._()\-]/u', '', pathinfo($original, PATHINFO_FILENAME));
        if (!is_string($cleanName)) {
            return null;
        }
        $name = mb_substr(trim($cleanName), 0, 150);

        return $name === '' ? null : $name . '.pdf';
    }

    public function create(string $kind, array $data, ?array $file): array
    {
        $config = $this->libraryConfig($kind);
        if ($config === null) {
            return ['success' => false, 'status' => 404, 'message' => 'La biblioteca solicitada no existe.'];
        }

        $loaded = $this->readCatalog($config['catalog']);
        if (isset($loaded['error'])) {
            return ['success' => false, 'status' => 500, 'message' => $loaded['error']];
        }

        $catalog = $loaded['catalog'];
        $originalCatalog = $catalog;
        [$company, $category, $errors] = $this->validateLocation($kind, $catalog, $data);
        $title = is_string($data['Titulo'] ?? null) ? trim($data['Titulo']) : '';
        if ($title === '' || mb_strlen($title) > 150) {
            $errors['Titulo'] = ['El título es obligatorio y debe tener máximo 150 caracteres.'];
        }

        $filename = $this->uploadName($file);
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['Archivo'] = ['Selecciona un archivo PDF.'];
        } elseif ($filename === null) {
            $errors['Archivo'] = ['El nombre del archivo no es válido.'];
        }

        if ($errors) {
            return ['success' => false, 'status' => 422, 'errors' => $errors, 'message' => 'Revisa los datos del documento.'];
        }

        $section = $this->sectionKey($kind, $category);
        if (!isset($catalog[$company][$section]) || !is_array($catalog[$company][$section])) {
            $catalog[$company][$section] = [];
        }

        $directory = $config['base'] . DIRECTORY_SEPARATOR . $company;
        if ($kind === 'formatos') {
            $directory .= DIRECTORY_SEPARATOR . $category;
        }
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            return ['success' => false, 'status' => 422, 'message' => 'Ya existe un documento con ese nombre de archivo.'];
        }

        $upload = UploadHelper::store($file, $directory, UploadHelper::PDF_TYPES, 30 * 1024 * 1024, $filename);
        if (isset($upload['error'])) {
            return ['success' => false, 'status' => 422, 'message' => $upload['error']];
        }
        if ($upload === null) {
            return ['success' => false, 'status' => 422, 'message' => 'Selecciona un archivo PDF.'];
        }

        $entry = ['titulo' => $title, 'archivo' => $filename];
        $catalog[$company][$section][] = $entry;
        if (!$this->writeCatalog($config['catalog'], $catalog)) {
            if (is_file($upload['path']) && !unlink($upload['path'])) {
                error_log('No fue posible limpiar un PDF cuyo catálogo no se guardó: ' . $upload['path']);
            }
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible guardar el catálogo de documentos.'];
        }
        if (!$this->syncPdfToDist($config, $company, $kind === 'formatos' ? $category : '', $filename)
            || !$this->syncCatalogToDist($config)) {
            if (!$this->writeCatalog($config['catalog'], $originalCatalog)) {
                error_log('No fue posible restaurar un catálogo después de fallar su publicación en dist.');
            }
            if (is_file($upload['path']) && !unlink($upload['path'])) {
                error_log('No fue posible borrar un PDF después de fallar su publicación en dist: ' . $upload['path']);
            }
            if (!$this->deletePdfFromDist($config, $company, $kind === 'formatos' ? $category : '', $filename)) {
                error_log('No fue posible borrar del sitio un PDF cuya publicación falló: ' . $filename);
            }
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible publicar el documento en el sitio.'];
        }

        return ['success' => true, 'message' => 'Documento agregado correctamente.', 'data' => $entry];
    }

    public function delete(string $kind, array $data): array
    {
        $config = $this->libraryConfig($kind);
        if ($config === null) {
            return ['success' => false, 'status' => 404, 'message' => 'La biblioteca solicitada no existe.'];
        }

        $loaded = $this->readCatalog($config['catalog']);
        if (isset($loaded['error'])) {
            return ['success' => false, 'status' => 500, 'message' => $loaded['error']];
        }

        $catalog = $loaded['catalog'];
        $originalCatalog = $catalog;
        [$company, $category, $errors] = $this->validateLocation($kind, $catalog, $data);
        $filename = is_string($data['Archivo'] ?? null) ? trim($data['Archivo']) : '';
        if ($filename === '' || basename($filename) !== $filename || !preg_match('/^[\p{L}\p{N} ._()\-]+\.pdf$/iu', $filename)) {
            $errors['Archivo'] = ['Selecciona un archivo válido.'];
        }
        if ($errors) {
            return ['success' => false, 'status' => 422, 'errors' => $errors, 'message' => 'Revisa los datos del documento.'];
        }

        $section = $this->sectionKey($kind, $category);
        $documents = $catalog[$company][$section] ?? [];
        if (!is_array($documents)) {
            return ['success' => false, 'status' => 500, 'message' => 'La sección del catálogo tiene un formato inválido.'];
        }
        $remaining = array_values(array_filter(
            $documents,
            static fn ($document) => !is_array($document) || ($document['archivo'] ?? null) !== $filename
        ));
        if (count($remaining) === count($documents)) {
            return ['success' => false, 'status' => 404, 'message' => 'El documento no existe en esta categoría.'];
        }

        $catalog[$company][$section] = $remaining;
        if (!$this->writeCatalog($config['catalog'], $catalog)) {
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible actualizar el catálogo de documentos.'];
        }
        if (!$this->syncCatalogToDist($config)) {
            if (!$this->writeCatalog($config['catalog'], $originalCatalog)) {
                error_log('No fue posible restaurar un catálogo después de fallar su publicación en dist.');
            }
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible publicar el catálogo actualizado.'];
        }

        $directory = $config['base'] . DIRECTORY_SEPARATOR . $company;
        if ($kind === 'formatos') {
            $directory .= DIRECTORY_SEPARATOR . $category;
        }
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $sourceDeleted = !is_file($path) || unlink($path);
        $distDeleted = $this->deletePdfFromDist($config, $company, $kind === 'formatos' ? $category : '', $filename);
        if (!$sourceDeleted || !$distDeleted) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'El documento se retiró del catálogo, pero no fue posible borrar el archivo.',
            ];
        }

        return ['success' => true, 'message' => 'Documento eliminado correctamente.'];
    }
}
