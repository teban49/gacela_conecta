<?php

namespace App\Services\Intranet;

use App\Helpers\UploadHelper;

class ManualService
{
    public const CATEGORIES = ['GIS', 'FICS', 'PXP', 'Ofima'];

    private function baseDirectory(): string
    {
        $custom = getenv('MANUALES_PATH');
        if (is_string($custom) && $custom !== '') {
            return rtrim($custom, '\\/');
        }

        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'intranet' . DIRECTORY_SEPARATOR . 'manuales';
    }

    private function pdfDirectory(): string
    {
        return $this->baseDirectory() . DIRECTORY_SEPARATOR . 'pdf';
    }

    private function catalogPath(): string
    {
        return $this->baseDirectory() . DIRECTORY_SEPARATOR . 'manuales.json';
    }

    private function distDirectory(): ?string
    {
        if (getenv('MANUALES_PATH')) {
            return null;
        }

        $frontend = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'frontend';
        if (!is_dir($frontend . DIRECTORY_SEPARATOR . 'dist')) {
            return null;
        }

        return $frontend . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'media'
            . DIRECTORY_SEPARATOR . 'intranet' . DIRECTORY_SEPARATOR . 'manuales';
    }

    private function syncCatalogToDist(): bool
    {
        $directory = $this->distDirectory();
        if ($directory === null) {
            return true;
        }
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            return false;
        }

        return copy($this->catalogPath(), $directory . DIRECTORY_SEPARATOR . 'manuales.json');
    }

    private function syncPdfToDist(string $filename): bool
    {
        $directory = $this->distDirectory();
        if ($directory === null) {
            return true;
        }
        $pdfDirectory = $directory . DIRECTORY_SEPARATOR . 'pdf';
        if (!is_dir($pdfDirectory) && !mkdir($pdfDirectory, 0770, true) && !is_dir($pdfDirectory)) {
            return false;
        }

        return copy($this->pdfDirectory() . DIRECTORY_SEPARATOR . $filename, $pdfDirectory . DIRECTORY_SEPARATOR . $filename);
    }

    private function deletePdfFromDist(string $filename): bool
    {
        $directory = $this->distDirectory();
        if ($directory === null) {
            return true;
        }
        $path = $directory . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . basename($filename);

        return !is_file($path) || unlink($path);
    }

    private function readCatalog(): array
    {
        $path = $this->catalogPath();
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    private function writeCatalog(array $catalog): bool
    {
        $json = json_encode(array_values($catalog), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return $json !== false && file_put_contents($this->catalogPath(), $json . PHP_EOL, LOCK_EX) !== false;
    }

    private function validate(array $data): array
    {
        $title = is_string($data['Titulo'] ?? null) ? trim($data['Titulo']) : '';
        $category = is_string($data['Categoria'] ?? null) ? trim($data['Categoria']) : '';
        $version = is_string($data['Version'] ?? null) ? trim($data['Version']) : '';
        $errors = [];

        if ($title === '' || mb_strlen($title) > 150) {
            $errors['Titulo'] = ['El título es obligatorio y debe tener máximo 150 caracteres.'];
        }
        if (!in_array($category, self::CATEGORIES, true)) {
            $errors['Categoria'] = ['Selecciona una categoría válida.'];
        }
        if (mb_strlen($version) > 30) {
            $errors['Version'] = ['La versión debe tener máximo 30 caracteres.'];
        }

        return [$title, $category, $version, $errors];
    }

    private function storePdf(?array $file): array
    {
        $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
        $name = trim(preg_replace('/[^\p{L}\p{N} ._()\-]/u', '', pathinfo($original, PATHINFO_FILENAME)));
        $name = mb_substr($name, 0, 150);
        if ($name === '') {
            return ['error' => 'El nombre del archivo no es válido.'];
        }
        $name .= '.pdf';
        if (is_file($this->pdfDirectory() . DIRECTORY_SEPARATOR . $name)) {
            return ['error' => 'Ya existe un manual con ese nombre de archivo. Cambia el nombre del PDF.'];
        }

        $upload = UploadHelper::store($file, $this->pdfDirectory(), UploadHelper::PDF_TYPES, 30 * 1024 * 1024, $name);
        return $upload ?? ['error' => 'Selecciona el archivo PDF.'];
    }

    private function deleteFileIfUnused(array $catalog, string $filename): bool
    {
        foreach ($catalog as $item) {
            if (($item['archivo'] ?? null) === $filename) {
                return true;
            }
        }
        $path = $this->pdfDirectory() . DIRECTORY_SEPARATOR . basename($filename);
        if (is_file($path) && !unlink($path)) {
            return false;
        }

        return $this->deletePdfFromDist($filename);
    }

    public function create(array $data, ?array $file): array
    {
        [$title, $category, $version, $errors] = $this->validate($data);
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['Archivo'] = ['Selecciona el archivo PDF del manual.'];
        }
        if ($errors) {
            return ['success' => false, 'status' => 422, 'errors' => $errors, 'message' => 'Revisa los datos del manual.'];
        }

        $upload = $this->storePdf($file);
        if (isset($upload['error'])) {
            return ['success' => false, 'status' => 422, 'errors' => ['Archivo' => [$upload['error']]], 'message' => $upload['error']];
        }

        $catalog = $this->readCatalog();
        $originalCatalog = $catalog;
        $entry = [
            'id' => 'manual-' . bin2hex(random_bytes(6)),
            'categoria' => $category,
            'titulo' => $title,
            'archivo' => $upload['name'],
        ];
        if ($version !== '') {
            $entry['version'] = $version;
        }
        $catalog[] = $entry;

        if (!$this->writeCatalog($catalog)) {
            if (is_file($upload['path']) && !unlink($upload['path'])) {
                error_log('No fue posible limpiar un manual cuyo catálogo no se guardó: ' . $upload['path']);
            }
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible guardar el manual.'];
        }
        if (!$this->syncPdfToDist($upload['name']) || !$this->syncCatalogToDist()) {
            if (!$this->writeCatalog($originalCatalog)) {
                error_log('No fue posible restaurar el catálogo de manuales tras fallar su publicación.');
            }
            if (is_file($upload['path']) && !unlink($upload['path'])) {
                error_log('No fue posible borrar un manual tras fallar su publicación: ' . $upload['path']);
            }
            if (!$this->deletePdfFromDist($upload['name'])) {
                error_log('No fue posible borrar del sitio un manual cuya publicación falló: ' . $upload['name']);
            }
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible publicar el manual en el sitio.'];
        }

        return ['success' => true, 'message' => 'Manual agregado correctamente.', 'data' => $entry];
    }

    public function update(string $id, array $data, ?array $file): array
    {
        $catalog = $this->readCatalog();
        $originalCatalog = $catalog;
        $index = null;
        foreach ($catalog as $i => $item) {
            if (($item['id'] ?? null) === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return ['success' => false, 'status' => 404, 'message' => 'El manual no existe.'];
        }

        [$title, $category, $version, $errors] = $this->validate($data);
        if ($errors) {
            return ['success' => false, 'status' => 422, 'errors' => $errors, 'message' => 'Revisa los datos del manual.'];
        }

        $oldFile = (string) ($catalog[$index]['archivo'] ?? '');
        $newPath = null;
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = $this->storePdf($file);
            if (isset($upload['error'])) {
                return ['success' => false, 'status' => 422, 'errors' => ['Archivo' => [$upload['error']]], 'message' => $upload['error']];
            }
            $catalog[$index]['archivo'] = $upload['name'];
            $newPath = $upload['path'];
        }

        $catalog[$index]['titulo'] = $title;
        $catalog[$index]['categoria'] = $category;
        if ($version !== '') {
            $catalog[$index]['version'] = $version;
        } else {
            unset($catalog[$index]['version']);
        }

        if (!$this->writeCatalog($catalog)) {
            if ($newPath) {
                @unlink($newPath);
            }
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible guardar el manual.'];
        }
        if (($newPath && !$this->syncPdfToDist((string) $catalog[$index]['archivo']))
            || !$this->syncCatalogToDist()) {
            if (!$this->writeCatalog($originalCatalog)) {
                error_log('No fue posible restaurar el catálogo de manuales tras fallar su publicación.');
            }
            if ($newPath) {
                if (is_file($newPath) && !unlink($newPath)) {
                    error_log('No fue posible limpiar el nuevo PDF tras fallar la publicación: ' . $newPath);
                }
                if (!$this->deletePdfFromDist((string) $catalog[$index]['archivo'])) {
                    error_log('No fue posible limpiar del sitio el nuevo PDF: ' . $catalog[$index]['archivo']);
                }
            }
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible publicar el manual en el sitio.'];
        }
        if ($newPath && $oldFile !== '') {
            if (!$this->deleteFileIfUnused($catalog, $oldFile)) {
                return ['success' => false, 'status' => 500, 'message' => 'El manual se actualizó, pero no fue posible retirar el PDF anterior.'];
            }
        }

        return ['success' => true, 'message' => 'Manual actualizado correctamente.', 'data' => $catalog[$index]];
    }

    public function delete(string $id): array
    {
        $catalog = $this->readCatalog();
        $originalCatalog = $catalog;
        $removed = null;
        $remaining = [];
        foreach ($catalog as $item) {
            if ($removed === null && ($item['id'] ?? null) === $id) {
                $removed = $item;
                continue;
            }
            $remaining[] = $item;
        }
        if ($removed === null) {
            return ['success' => false, 'status' => 404, 'message' => 'El manual no existe.'];
        }
        if (!$this->writeCatalog($remaining)) {
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible eliminar el manual.'];
        }
        if (!$this->syncCatalogToDist()) {
            if (!$this->writeCatalog($originalCatalog)) {
                error_log('No fue posible restaurar el catálogo de manuales tras fallar su publicación.');
            }
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible actualizar el catálogo publicado.'];
        }
        if (!$this->deleteFileIfUnused($remaining, (string) ($removed['archivo'] ?? ''))) {
            return ['success' => false, 'status' => 500, 'message' => 'El manual se retiró del catálogo, pero no fue posible borrar el PDF.'];
        }

        return ['success' => true, 'message' => 'Manual eliminado correctamente.'];
    }
}
