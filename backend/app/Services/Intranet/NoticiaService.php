<?php

namespace App\Services\Intranet;

use App\Repositories\Intranet\NoticiaRepository;

class NoticiaService
{
    private NoticiaRepository $repository;

    public function __construct()
    {
        $this->repository = new NoticiaRepository();
    }

    public const IMAGE_ROUTE = '/api/v1/intranet/noticias/imagenes/';

    public static function imageDirectory(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'intranet' . DIRECTORY_SEPARATOR . 'noticias';
    }

    public static function withImageUrl(array $noticia): array
    {
        $image = (string) ($noticia['Imagen'] ?? '');
        if ($image === '') {
            $noticia['ImagenUrl'] = null;
        } elseif (preg_match('#^(https?:)?//#i', $image) || str_starts_with($image, '/')) {
            $noticia['ImagenUrl'] = $image;
        } else {
            $noticia['ImagenUrl'] = self::IMAGE_ROUTE . rawurlencode($image);
        }

        return $noticia;
    }

    public function getImagePath(string $filename): ?string
    {
        if ($filename !== basename($filename) || !preg_match('/^[A-Za-z0-9_-]+\.(png|jpg|webp)$/', $filename)) {
            return null;
        }

        $path = self::imageDirectory() . DIRECTORY_SEPARATOR . $filename;
        return is_file($path) ? $path : null;
    }

    public function getAll(): array
    {
        return array_map([self::class, 'withImageUrl'], $this->repository->getAll());
    }

    public function getPublished(): array
    {
        return array_map([self::class, 'withImageUrl'], $this->repository->getPublished());
    }

    public function getByCategory(string $categoria): array
    {
        return array_map([self::class, 'withImageUrl'], $this->repository->getByCategory($categoria));
    }

    public function getById(int $id): ?array
    {
        $noticia = $this->repository->getById($id);
        return $noticia ? self::withImageUrl($noticia) : null;
    }

    public function markAsViewed(int $noticiaId, int $usuarioId): array
    {
        $this->repository->markAsViewed($noticiaId, $usuarioId);

        return [
            'success' => true,
            'message' => 'Noticia marcada como vista.'
        ];
    }

    public function create(array $data): array
    {
        $categorias = ['general', 'rrhh', 'ti', 'operaciones', 'ventas', 'finanzas'];
        $prioridades = ['baja', 'media', 'alta', 'urgente'];
        $data['Titulo'] = trim($data['Titulo']);
        $data['Categoria'] = in_array($data['Categoria'] ?? '', $categorias, true) ? $data['Categoria'] : 'general';
        $data['Prioridad'] = in_array($data['Prioridad'] ?? '', $prioridades, true) ? $data['Prioridad'] : 'media';
        $data['Estado'] = in_array($data['Estado'] ?? '', ['borrador', 'publicada', 'archivada'], true) ? $data['Estado'] : 'publicada';
        $data['FechaPublicacion'] = !empty($data['FechaPublicacion']) ? $data['FechaPublicacion'] : date('Y-m-d H:i:s');
        $data['FechaExpiracion'] = !empty($data['FechaExpiracion']) ? $data['FechaExpiracion'] : null;
        $data['Resumen'] = isset($data['Resumen']) && trim($data['Resumen']) !== '' ? trim($data['Resumen']) : null;

        $id = $this->repository->create($data);

        return [
            'success' => true,
            'message' => 'Noticia creada correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $noticia = $this->repository->getById($id);

        if (!$noticia) {
            return [
                'success' => false,
                'message' => 'La noticia no existe.'
            ];
        }

        $this->repository->update($id, array_merge($noticia, $data, ['Imagen' => $noticia['Imagen']]));

        return [
            'success' => true,
            'message' => 'Noticia actualizada correctamente.'
        ];
    }

    public function changeStatus(int $id, string $status): array
    {
        $noticia = $this->repository->getById($id);

        if (!$noticia) {
            return [
                'success' => false,
                'message' => 'La noticia no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado de la noticia actualizado correctamente.'
        ];
    }
}