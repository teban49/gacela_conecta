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

            $files = scandir($directory);
            if ($files === false) {
                throw new RuntimeException('No se pudo leer la carpeta privada de documentos de Calidad.');
            }

            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }

                $path = $directory . DIRECTORY_SEPARATOR . $file;
                if (!is_file($path)) {
                    continue;
                }

                $documents[] = [
                    'area' => $slug,
                    'areaNombre' => $label,
                    'titulo' => pathinfo($file, PATHINFO_FILENAME),
                    'archivo' => $file,
                ];
            }
        }

        usort($documents, static function (array $left, array $right): int {
            return [$left['areaNombre'], $left['titulo']] <=> [$right['areaNombre'], $right['titulo']];
        });

        return $documents;
    }

    public function findDocument(int $companyId, string $area, string $filename): ?string
    {
        if (!isset(self::AREAS[$area])
            || $filename !== basename($filename)
            || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf'
            || str_contains($filename, "\0")) {
            return null;
        }

        $directory = realpath($this->getAreaDirectory($companyId, $area));
        if ($directory === false) {
            return null;
        }

        $path = realpath($directory . DIRECTORY_SEPARATOR . $filename);
        if ($path === false || dirname($path) !== $directory || !is_file($path)) {
            return null;
        }

        return $path;
    }

    private function getAreaDirectory(int $companyId, string $area): string
    {
        return $this->storageRoot . DIRECTORY_SEPARATOR . $companyId . DIRECTORY_SEPARATOR . $area;
    }
}
