<?php

namespace App\Services\Gestion;

use App\Repositories\Gestion\LicenciaRepository;

class LicenciaService
{
    private LicenciaRepository $repository;

    public function __construct()
    {
        $this->repository = new LicenciaRepository();
    }

    public function getActive(): array
    {
        return $this->repository->getActive();
    }

    public function getOptions(): array
    {
        return $this->repository->getOptions();
    }

    public function create(array $data): array
    {
        return $this->save($data, null);
    }

    public function update(int $id, array $data): array
    {
        return $this->save($data, $id);
    }

    private function save(array $data, ?int $id): array
    {
        $errors = [];
        $name = is_string($data['Nombre'] ?? null) ? trim($data['Nombre']) : '';
        if ($name === '') {
            $errors['Nombre'] = ['El nombre del software es obligatorio.'];
        } elseif (mb_strlen($name) > 255) {
            $errors['Nombre'] = ['El nombre no puede superar 255 caracteres.'];
        }

        $companyId = $this->parseInteger($data['EmpresaId'] ?? null);
        if ($companyId === false || $companyId === null || $companyId < 1 || !$this->repository->isActiveCompany($companyId)) {
            $errors['EmpresaId'] = ['Selecciona una empresa activa.'];
        }
        $areaId = $this->parseInteger($data['AreaId'] ?? null);
        if ($areaId === false || $areaId === null || $areaId < 1 || !$this->repository->isActiveArea($areaId)) {
            $errors['AreaId'] = ['Selecciona un área activa.'];
        }

        $allowedTypes = ['comercial', 'opensource', 'personalizado'];
        $type = $data['Tipo'] ?? 'comercial';
        if (!is_string($type) || !in_array($type, $allowedTypes, true)) {
            $errors['Tipo'] = ['Selecciona un tipo de licencia válido.'];
        }

        $assetIds = $data['ActivosIds'] ?? null;
        if (!is_array($assetIds) || !$assetIds) {
            $errors['ActivosIds'] = ['Selecciona al menos un activo asociado.'];
            $assetIds = [];
        } else {
            $parsedIds = [];
            foreach ($assetIds as $assetId) {
                if (!is_int($assetId) && !is_string($assetId)) {
                    $parsedIds[] = false;
                    continue;
                }
                $parsedIds[] = $this->parseInteger($assetId);
            }
            $assetIds = array_values(array_unique(array_filter(
                $parsedIds,
                static fn ($id) => $id !== false && $id !== null && $id > 0
            )));
            if (!$assetIds || count($assetIds) !== count($data['ActivosIds'])) {
                $errors['ActivosIds'] = ['La selección de activos no es válida.'];
            } elseif ($companyId !== false && count($this->repository->getActiveAssetIds($assetIds, $companyId)) !== count($assetIds)) {
                $errors['ActivosIds'] = ['Selecciona activos activos y asociados a la empresa elegida.'];
            }
        }

        $quantity = $this->parseInteger($data['Cantidad'] ?? count($assetIds));
        if ($quantity === false || $quantity < 1) {
            $errors['Cantidad'] = ['La cantidad de licencias debe ser mayor que cero.'];
        } elseif ($quantity < count($assetIds)) {
            $errors['Cantidad'] = ['La cantidad debe cubrir todos los activos asociados.'];
        }

        $expiry = $data['FechaVencimiento'] ?? null;
        if ($expiry === '') $expiry = null;
        if ($expiry !== null) {
            $date = is_string($expiry) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $expiry) : false;
            if (!$date || $date->format('Y-m-d') !== $expiry) {
                $errors['FechaVencimiento'] = ['Ingresa una fecha de vencimiento válida.'];
            }
        }

        $text = [];
        foreach (['Proveedor' => 255, 'NumeroLicencia' => 100, 'Notas' => 10000] as $field => $limit) {
            $value = $data[$field] ?? null;
            if ($value !== null && !is_string($value)) {
                $errors[$field] = ['El valor debe ser texto.'];
                continue;
            }
            $value = is_string($value) ? trim($value) : '';
            if (mb_strlen($value) > $limit) {
                $errors[$field] = ["El campo no puede superar {$limit} caracteres."];
            }
            $text[$field] = $value === '' ? null : $value;
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Revisa los datos de la licencia.'];
        }

        $license = [
            'Nombre' => $name,
            'Tipo' => $type,
            'EmpresaId' => $companyId,
            'AreaId' => $areaId,
            'ActivosIds' => $assetIds,
            'Cantidad' => $quantity,
            'FechaVencimiento' => $expiry,
            'Proveedor' => $text['Proveedor'],
            'NumeroLicencia' => $text['NumeroLicencia'],
            'Notas' => $text['Notas'],
        ];
        if ($id === null) {
            $id = $this->repository->create($license);
            return ['success' => true, 'message' => 'Licencia registrada correctamente.', 'data' => ['Id' => $id]];
        }
        if (!$this->repository->update($id, $license)) {
            return ['success' => false, 'status' => 404, 'message' => 'La licencia no existe o ya no está activa.'];
        }
        return ['success' => true, 'message' => 'Licencia renovada/actualizada correctamente.', 'data' => ['Id' => $id]];
    }

    private function parseInteger(mixed $value): int|false
    {
        if (!is_int($value) && !is_string($value)) return false;
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed === false ? false : $parsed;
    }
}
