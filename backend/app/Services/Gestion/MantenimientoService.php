<?php

namespace App\Services\Gestion;

use App\Repositories\Gestion\MantenimientoRepository;

class MantenimientoService
{
    private MantenimientoRepository $repository;

    public function __construct()
    {
        $this->repository = new MantenimientoRepository();
    }

    public function getPending(): array
    {
        return $this->repository->getPending();
    }

    public function complete(int $assetId, int $year, int $semester, array $data, int $userId): array
    {
        if ($semester !== 1 && $semester !== 2) {
            return ['success' => false, 'message' => 'Selecciona un semestre válido.'];
        }
        if (!$this->repository->isEligiblePeriod($assetId, $year, $semester)) {
            return ['success' => false, 'message' => 'El período no corresponde a un mantenimiento pendiente del activo.'];
        }

        $executor = $data['Ejecutor'] ?? null;
        if (!is_string($executor) || !in_array($executor, ['ti', 'proveedor'], true)) {
            return ['success' => false, 'message' => 'Selecciona si el mantenimiento fue realizado por TI o por un proveedor.'];
        }

        $supplierId = null;
        if ($executor === 'proveedor') {
            $supplierId = $this->parseInteger($data['ProveedorId'] ?? null);
            if ($supplierId === false || $supplierId < 1 || !$this->repository->isActiveSupplier($supplierId)) {
                return ['success' => false, 'message' => 'Selecciona un proveedor activo para el mantenimiento.'];
            }
        }

        if (!$this->repository->complete($assetId, $year, $semester, $executor, $supplierId, $userId)) {
            return ['success' => false, 'message' => 'Este mantenimiento semestral ya fue registrado como completado.'];
        }
        return ['success' => true, 'message' => 'Mantenimiento registrado correctamente.'];
    }

    private function parseInteger(mixed $value): int|false
    {
        if (!is_int($value) && !is_string($value)) return false;
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed === false ? false : $parsed;
    }
}
