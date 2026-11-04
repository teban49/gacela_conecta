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

    private function deleteFileIfUnused(array $catalog, string $filename): void
    {
        foreach ($catalog as $item) {
            if (($item['archivo'] ?? null) === $filename) {
                return;
            }
        }
        $path = $this->pdfDirectory() . DIRECTORY_SEPARATOR . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
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
            @unlink($upload['path']);
            return ['success' => false, 'status' => 500, 'message' => 'No fue posible guardar el manual.'];
        }

        return ['success' => true, 'message' => 'Manual agregado correctamente.', 'data' => $entry];
    }

    public function update(string $id, array $data, ?array $file): array
    {
        $catalog = $this->readCatalog();
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
        if ($newPath && $oldFile !== '') {
            $this->deleteFileIfUnused($catalog, $oldFile);
        }

        return ['success' => true, 'message' => 'Manual actualizado correctamente.', 'data' => $catalog[$index]];
    }

    public function delete(string $id): array
    {
        $catalog = $this->readCatalog();
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
        $this->deleteFileIfUnused($remaining, (string) ($removed['archivo'] ?? ''));

        return ['success' => true, 'message' => 'Manual eliminado correctamente.'];
    }
}
