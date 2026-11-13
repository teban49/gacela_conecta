<?php

namespace App\Services\Solicitudes;

use App\Repositories\Solicitudes\CategoriaRepository;
use App\Repositories\Solicitudes\SolicitudRepository;

class SolicitudService
{
    private SolicitudRepository $repository;
    private CategoriaRepository $categoriaRepository;

    public function __construct()
    {
        $this->repository = new SolicitudRepository();
        $this->categoriaRepository = new CategoriaRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getByStatus(string $estado): array
    {
        return $this->repository->getByStatus($estado);
    }

    public function getByAssignee(int $usuarioId): array
    {
        return $this->repository->getByAssignee($usuarioId);
    }

    public function getByRequester(int $usuarioId): array
    {
        return $this->repository->getByRequester($usuarioId);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): array
    {
        $errors = [];
        $titulo = is_string($data['Titulo'] ?? null) ? trim($data['Titulo']) : '';
        $descripcion = is_string($data['Descripcion'] ?? null) ? trim($data['Descripcion']) : '';
        $categoriaId = filter_var($data['CategoriaId'] ?? null, FILTER_VALIDATE_INT);
        $categoria = null;

        if ($titulo === '') {
            $errors['Titulo'] = ['El título es obligatorio.'];
        } elseif (mb_strlen($titulo) > 255) {
            $errors['Titulo'] = ['El título no puede superar 255 caracteres.'];
        }
        if ($descripcion === '') {
            $errors['Descripcion'] = ['La descripción es obligatoria.'];
        }
        if (is_string($data['Ubicacion'] ?? null) && mb_strlen(trim($data['Ubicacion'])) > 255) {
            $errors['Ubicacion'] = ['La ubicación no puede superar 255 caracteres.'];
        }
        if ($categoriaId === false || $categoriaId === null || $categoriaId < 1) {
            $errors['CategoriaId'] = ['Selecciona una categoría válida.'];
        } else {
            $categoria = $this->categoriaRepository->findById($categoriaId);
            if (!$categoria || (int) $categoria['Estado'] !== 1) {
                $errors['CategoriaId'] = ['La categoría seleccionada no está disponible.'];
            }
        }

        $subcategoria = $data['Subcategoria'] ?? '';
        if (!is_string($subcategoria)) {
            $errors['Subcategoria'] = ['Selecciona una subcategoría válida.'];
        } else {
            $subcategoria = trim($subcategoria);
            if (mb_strlen($subcategoria) > 150) {
                $errors['Subcategoria'] = ['La subcategoría no puede superar 150 caracteres.'];
            } elseif (($data['Tipo'] ?? '') === 'incidencia' && is_int($categoriaId) && $categoriaId > 0) {
                $subcategories = array_column(
                    $this->categoriaRepository->getActiveSubcategories($categoriaId),
                    'Nombre'
                );
                if ($subcategories && !in_array($subcategoria, $subcategories, true)) {
                    $errors['Subcategoria'] = ['Selecciona una subcategoría válida para la categoría.'];
                } elseif ($subcategories && $subcategoria === '') {
                    $errors['Subcategoria'] = ['Selecciona una subcategoría.'];
                } elseif (!$subcategories && $subcategoria !== '') {
                    $errors['Subcategoria'] = ['La categoría seleccionada no tiene subcategorías.'];
                }
            }
        }

        $allowedPriorities = ['baja', 'media', 'alta', 'critica'];
        $isIncident = ($data['Tipo'] ?? 'soporte') === 'incidencia';
        if (!$isIncident && isset($data['Prioridad']) && (!is_string($data['Prioridad']) || !in_array($data['Prioridad'], $allowedPriorities, true))) {
            $errors['Prioridad'] = ['Selecciona una prioridad válida.'];
        }

        $allowedTypes = ['soporte', 'solicitud', 'incidencia', 'mejora', 'otro'];
        if (isset($data['Tipo']) && (!is_string($data['Tipo']) || !in_array($data['Tipo'], $allowedTypes, true))) {
            $errors['Tipo'] = ['Selecciona un tipo válido.'];
        }

        if ($errors) {
            return [
                'success' => false,
                'message' => 'Revisa los datos de la solicitud.',
                'errors' => $errors,
            ];
        }

        $data['Titulo'] = $titulo;
        $data['Descripcion'] = $descripcion;
        $data['CategoriaId'] = $categoriaId;
        $data['Subcategoria'] = $subcategoria !== '' ? $subcategoria : null;
        $data['Prioridad'] = $isIncident ? 'baja' : ($data['Prioridad'] ?? 'media');
        $data['Estado'] = 'nueva';
        $data['Canal'] = 'portal';
        $data['AsignadoA'] = null;
        $data['ArchivoAdjunto'] = $data['ArchivoAdjunto'] ?? null;
        $data['FechaVencimiento'] = $isIncident
            ? TicketSla::deadlineForPriority($data['Prioridad'])
            : null;

        $id = $this->repository->create($data);

        return [
            'success' => true,
            'message' => 'Solicitud creada correctamente.',
            'data' => ['Id' => $id],
        ];
    }

    public function update(int $id, array $data): array
    {
        $solicitud = $this->repository->findById($id);

        if (!$solicitud) {
            return [
                'success' => false,
                'message' => 'La solicitud no existe.'
            ];
        }

        $this->repository->update($id, $data);

        return [
            'success' => true,
            'message' => 'Solicitud actualizada correctamente.'
        ];
    }

    public function changeStatus(int $id, string $estado): array
    {
        $solicitud = $this->repository->findById($id);

        if (!$solicitud) {
            return [
                'success' => false,
                'message' => 'La solicitud no existe.'
            ];
        }

        $this->repository->changeStatus($id, $estado);

        return [
            'success' => true,
            'message' => 'Estado de la solicitud actualizado correctamente.'
        ];
    }

    public function assignTo(int $id, int $usuarioId): array
    {
        $solicitud = $this->repository->findById($id);

        if (!$solicitud) {
            return [
                'success' => false,
                'message' => 'La solicitud no existe.'
            ];
        }

        $this->repository->assignTo($id, $usuarioId);

        return [
            'success' => true,
            'message' => 'Solicitud asignada correctamente.'
        ];
    }
}