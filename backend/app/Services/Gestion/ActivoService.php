<?php

namespace App\Services\Gestion;

use App\Repositories\Gestion\ActivoRepository;

class ActivoService
{
    private ActivoRepository $repository;

    public function __construct()
    {
        $this->repository = new ActivoRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getAssignableUsers(): array
    {
        return $this->repository->getAssignableUsers();
    }

    public function getPendingAssignmentsForUser(int $usuarioId): array
    {
        return $this->repository->getPendingAssignmentsForUser($usuarioId);
    }

    public function respondToAssignment(int $assignmentId, int $usuarioId, bool $accept): array
    {
        if (!$this->repository->respondToAssignment($assignmentId, $usuarioId, $accept)) {
            return ['success' => false, 'message' => 'La solicitud ya no está pendiente o no corresponde a tu usuario.'];
        }
        return [
            'success' => true,
            'message' => $accept ? 'Asignación aceptada. El activo ya está a tu nombre.' : 'Asignación rechazada. El activo quedó disponible.',
        ];
    }

    public function getActiveCategories(): array
    {
        return $this->repository->getActiveCategories();
    }

    public function getActiveCompanies(): array
    {
        return $this->repository->getActiveCompanies();
    }

    public function getActiveSuppliers(): array
    {
        return $this->repository->getActiveSuppliers();
    }

    public function createSupplier(array $data): array
    {
        $name = is_string($data['Nombre'] ?? null) ? trim($data['Nombre']) : '';
        if ($name === '' || mb_strlen($name) > 255) {
            return ['success' => false, 'message' => 'Ingresa un nombre de proveedor de hasta 255 caracteres.'];
        }
        $id = $this->repository->createSupplier($name);
        return ['success' => true, 'message' => 'Proveedor agregado correctamente.', 'data' => ['Id' => $id]];
    }

    public function getByCategory(int $categoriaId): array
    {
        return $this->repository->getByCategory($categoriaId);
    }

    public function getByAssignee(int $usuarioId): array
    {
        return $this->repository->getByAssignee($usuarioId);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): array
    {
        $validated = $this->validateAsset($data);
        if ($validated['errors']) {
            return ['success' => false, 'errors' => $validated['errors'], 'message' => 'Revisa los datos del activo.'];
        }

        $asset = $validated['data'];
        $asset['AsignadoA'] = null;
        $asset['EstadoActivo'] = 'nuevo';
        $asset['Estado'] = 1;
        $id = $this->repository->create($asset);

        return [
            'success' => true,
            'message' => 'Activo creado correctamente.',
            'data' => ['Id' => $id]
        ];
    }

    public function update(int $id, array $data): array
    {
        $activo = $this->repository->findById($id);

        if (!$activo) {
            return [
                'success' => false,
                'message' => 'El activo no existe.'
            ];
        }

        $validated = $this->validateAsset(array_merge($activo, $data));
        if ($validated['errors']) {
            return ['success' => false, 'errors' => $validated['errors'], 'message' => 'Revisa los datos del activo.'];
        }
        $this->repository->update($id, array_merge($validated['data'], [
            'CodigoInventario' => $activo['CodigoInventario'],
            'EstadoActivo' => $activo['EstadoActivo'],
            'AsignadoA' => $activo['AsignadoA'],
            'Estado' => $activo['Estado'],
        ]));

        return [
            'success' => true,
            'message' => 'Activo actualizado correctamente.'
        ];
    }

    public function changeStatus(int $id, bool $status): array
    {
        $activo = $this->repository->findById($id);

        if (!$activo) {
            return [
                'success' => false,
                'message' => 'El activo no existe.'
            ];
        }

        $this->repository->changeStatus($id, $status);

        return [
            'success' => true,
            'message' => 'Estado del activo actualizado correctamente.'
        ];
    }

    public function changeAssetStatus(int $id, string $estadoActivo): array
    {
        $activo = $this->repository->findById($id);

        if (!$activo) {
            return [
                'success' => false,
                'message' => 'El activo no existe.'
            ];
        }

        $allowedStatuses = ['nuevo', 'en_uso', 'mantenimiento', 'dañado', 'obsoleto', 'retirado'];
        if (!in_array($estadoActivo, $allowedStatuses, true)) {
            return [
                'success' => false,
                'message' => 'El estado del activo no es válido.'
            ];
        }

        if ($estadoActivo === 'retirado') {
            $this->repository->retire($id);
            return [
                'success' => true,
                'message' => 'Activo dado de baja y desasignado correctamente.'
            ];
        }

        $this->repository->changeAssetStatus($id, $estadoActivo);

        return [
            'success' => true,
            'message' => 'Estado del activo actualizado correctamente.'
        ];
    }

    public function assignTo(int $id, int $usuarioId, int $requestedBy): array
    {
        $activo = $this->repository->findById($id);

        if (!$activo) {
            return [
                'success' => false,
                'message' => 'El activo no existe.'
            ];
        }

        if ($activo['EstadoActivo'] === 'retirado' || (int) $activo['Estado'] !== 1) {
            return [
                'success' => false,
                'message' => 'No se puede asignar un activo dado de baja o inactivo.'
            ];
        }

        if (!$this->repository->isAssignableUserActive($usuarioId)) {
            return [
                'success' => false,
                'message' => 'El usuario seleccionado no está activo.'
            ];
        }

        if (!$this->repository->requestAssignment($id, $usuarioId, $requestedBy)) {
            return [
                'success' => false,
                'message' => 'El activo ya tiene una asignación o solicitud pendiente.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Solicitud de asignación enviada. El usuario debe aceptarla para completar la entrega.'
        ];
    }

    public function unassign(int $id): array
    {
        $activo = $this->repository->findById($id);

        if (!$activo) {
            return ['success' => false, 'message' => 'El activo no existe.'];
        }

        if ($activo['EstadoActivo'] === 'retirado') {
            return ['success' => false, 'message' => 'El activo está dado de baja y no tiene asignación vigente.'];
        }

        if ($activo['AsignadoA'] === null) {
            return ['success' => false, 'message' => 'El activo ya está sin asignar.'];
        }

        if (!$this->repository->unassign($id)) {
            return ['success' => false, 'message' => 'No fue posible retirar la asignación.'];
        }

        return ['success' => true, 'message' => 'Activo retirado al usuario y disponible nuevamente.'];
    }

    public function retire(int $id): array
    {
        $activo = $this->repository->findById($id);

        if (!$activo) {
            return ['success' => false, 'message' => 'El activo no existe.'];
        }

        if ($activo['EstadoActivo'] === 'retirado') {
            return ['success' => false, 'message' => 'El activo ya está dado de baja.'];
        }

        if (!$this->repository->retire($id)) {
            return ['success' => false, 'message' => 'No fue posible dar de baja el activo.'];
        }

        return ['success' => true, 'message' => 'Activo dado de baja y desasignado correctamente.'];
    }

    private function validateAsset(array $data): array
    {
        $errors = [];
        $categoryId = $this->parseInteger($data['CategoriaId'] ?? null);
        $companyId = $this->parseInteger($data['EmpresaId'] ?? null);
        $supplierId = ($data['ProveedorId'] ?? '') === '' || ($data['ProveedorId'] ?? null) === null
            ? null
            : $this->parseInteger($data['ProveedorId']);
        $property = $data['TipoPropiedad'] ?? null;

        if ($categoryId === false || $categoryId === null || $categoryId < 1 || !$this->repository->findActiveCategory($categoryId)) {
            $errors['CategoriaId'] = ['Selecciona un tipo de activo válido.'];
        }
        if ($companyId === false || $companyId === null || $companyId < 1 || !$this->repository->isActiveCompany($companyId)) {
            $errors['EmpresaId'] = ['Selecciona una empresa activa.'];
        }
        if (!is_string($property) || !in_array($property, ['propio', 'arriendo'], true)) {
            $errors['TipoPropiedad'] = ['Selecciona si el activo es propio o en arriendo.'];
        }
        if ($supplierId === false || ($supplierId !== null && ($supplierId < 1 || !$this->repository->isActiveSupplier($supplierId)))) {
            $errors['ProveedorId'] = ['Selecciona un proveedor activo válido.'];
        }

        $limits = [
            'NumeroFacturaContrato' => 150,
            'Placa' => 100,
            'Marca' => 100,
            'Modelo' => 100,
            'NumeroSerie' => 100,
        ];
        $values = [
            'CategoriaId' => $categoryId,
            'EmpresaId' => $companyId,
            'TipoPropiedad' => $property,
            'ProveedorId' => $supplierId,
        ];
        foreach ($limits as $field => $limit) {
            $value = $data[$field] ?? null;
            if ($value !== null && !is_string($value)) {
                $errors[$field] = ['El valor debe ser texto.'];
                continue;
            }
            $value = is_string($value) ? trim($value) : '';
            if (mb_strlen($value) > $limit) {
                $errors[$field] = ["El campo no puede superar {$limit} caracteres."];
            }
            $values[$field] = $value === '' ? null : $value;
        }

        foreach (['FechaInicio', 'FechaFin'] as $field) {
            $value = $data[$field] ?? null;
            if ($value === '') $value = null;
            if ($value !== null) {
                $date = is_string($value) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
                if (!$date || $date->format('Y-m-d') !== $value) {
                    $errors[$field] = ['Ingresa una fecha válida.'];
                    continue;
                }
            }
            $values[$field] = $value;
        }

        foreach (['Descripcion', 'Observaciones'] as $field) {
            $value = $data[$field] ?? null;
            if ($value !== null && !is_string($value)) {
                $errors[$field] = ['El valor debe ser texto.'];
                continue;
            }
            $values[$field] = is_string($value) && trim($value) !== '' ? trim($value) : null;
            if ($field === 'Observaciones' && mb_strlen($values[$field] ?? '') > 10000) {
                $errors[$field] = ['Las observaciones no pueden superar 10000 caracteres.'];
            }
        }

        return ['data' => array_merge($data, $values), 'errors' => $errors];
    }

    private function parseInteger(mixed $value): int|false
    {
        if (!is_int($value) && !is_string($value)) return false;
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed === false ? false : $parsed;
    }
}