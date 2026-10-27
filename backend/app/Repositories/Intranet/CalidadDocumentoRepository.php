<?php

namespace App\Repositories\Intranet;

use App\Repositories\BaseRepository;
use RuntimeException;

class CalidadDocumentoRepository extends BaseRepository
{
    public const AREAS = [
        'vinculaciones' => 'Vinculaciones',
        'mantenimiento' => 'Mantenimiento',
        'torre-control' => 'Torre de control',
        'calidad' => 'Calidad',
        'tecnologia' => 'Tecnología',
        'gerencia' => 'Gerencia',
        'servicio-cliente' => 'Servicio al cliente',
        'gestion-humana' => 'Gestión humana',
        'compras-proveedores' => 'Compras y proveedores',
    ];

    public const CATEGORIAS = [
        'caracterizacion' => 'Caracterización',
        'desempeño' => 'Desempeño',
        'directrices' => 'Directrices',
        'formatos' => 'Formatos',
        'matrices' => 'Matrices',
        'procedimiento' => 'Procedimiento',
    ];

    private string $storageRoot;

    public function __construct()
    {
        parent::__construct();
        $this->storageRoot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'intranet' . DIRECTORY_SEPARATOR . 'calidad';
    }

    public function getDocuments(int $companyId): array
    {
        $documents = [];

        foreach (self::AREAS as $slug => $label) {
            $directory = $this->getAreaDirectory($companyId, $slug);
            if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
                throw new RuntimeException('No se pudo preparar la carpeta privada de documentos de Calidad.');
            }

            $entries = scandir($directory);
            if ($entries === false) {
                throw new RuntimeException('No se pudo leer la carpeta privada de documentos de Calidad.');
            }

            // Los PDF pueden estar directamente en el área o dentro de una carpeta de categoría.
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $path = $directory . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($path)) {
                    if (!$this->isValidCategory($entry)) {
                        continue;
                    }
                    $files = scandir($path) ?: [];
                    foreach ($files as $file) {
                        if ($this->isPdfFile($path . DIRECTORY_SEPARATOR . $file, $file)) {
                            $documents[] = $this->buildDocument($slug, $label, $entry, $file);
                        }
                    }
                } elseif ($this->isPdfFile($path, $entry)) {
                    $documents[] = $this->buildDocument($slug, $label, '', $entry);
                }
            }
        }

        usort($documents, static function (array $left, array $right): int {
            return [$left['areaNombre'], $left['categoriaNombre'], $left['titulo']]
                <=> [$right['areaNombre'], $right['categoriaNombre'], $right['titulo']];
        });

        return $documents;
    }

    public function findDocument(int $companyId, string $area, string $categoria, string $filename): ?string
    {
        if (!isset(self::AREAS[$area])
            || ($categoria !== '' && !$this->isValidCategory($categoria))
            || $filename !== basename($filename)
            || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf'
            || str_contains($filename, "\0")) {
            return null;
        }

        $directory = $this->getAreaDirectory($companyId, $area);
        if ($categoria !== '') {
            $directory .= DIRECTORY_SEPARATOR . $categoria;
        }
        $directory = realpath($directory);
        if ($directory === false) {
            return null;
        }

        $path = realpath($directory . DIRECTORY_SEPARATOR . $filename);
        if ($path === false || dirname($path) !== $directory || !is_file($path)) {
            return null;
        }

        return $path;
    }

    public function getUploadDirectory(int $companyId, string $area, string $categoria): ?string
    {
        if (!isset(self::AREAS[$area]) || ($categoria !== '' && !isset(self::CATEGORIAS[$categoria]))) {
            return null;
        }

        $directory = $this->getAreaDirectory($companyId, $area);
        return $categoria === '' ? $directory : $directory . DIRECTORY_SEPARATOR . $categoria;
    }

    public function isValidCategory(string $categoria): bool
    {
        return isset(self::CATEGORIAS[$categoria]);
    }

    private function isPdfFile(string $path, string $file): bool
    {
        return $file !== '.' && $file !== '..'
            && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf'
            && is_file($path);
    }

    private function buildDocument(string $area, string $areaLabel, string $categoria, string $file): array
    {
        return [
            'area' => $area,
            'areaNombre' => $areaLabel,
            'categoria' => $categoria,
            'categoriaNombre' => self::CATEGORIAS[$categoria] ?? 'General',
            'titulo' => pathinfo($file, PATHINFO_FILENAME),
            'archivo' => $file,
        ];
    }

    private function getAreaDirectory(int $companyId, string $area): string
    {
        return $this->storageRoot . DIRECTORY_SEPARATOR . $companyId . DIRECTORY_SEPARATOR . $area;
    }
}
