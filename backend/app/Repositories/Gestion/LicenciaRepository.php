<?php

namespace App\Repositories\Gestion;

use App\Repositories\BaseRepository;
use PDO;

class LicenciaRepository extends BaseRepository
{
    public function getActive(): array
    {
        $licenses = $this->db->query("
            SELECT l.*, e.Nombre AS EmpresaNombre, ar.Nombre AS AreaNombre,
                   CASE
                       WHEN l.FechaVencimiento < CURRENT_DATE THEN 'vencida'
                       WHEN l.FechaVencimiento <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) THEN 'proxima'
                       ELSE 'vigente'
                   END AS EstadoVencimiento
            FROM gestion_licencias l
            LEFT JOIN gcp_empresas e ON e.Id = l.EmpresaId
            LEFT JOIN gcp_areas ar ON ar.Id = l.AreaId
            WHERE l.Estado = 1
            ORDER BY l.FechaVencimiento IS NULL ASC, l.FechaVencimiento ASC, l.Nombre ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (!$licenses) return [];
        $licenseIds = array_map(static fn (array $license): int => (int) $license['Id'], $licenses);
        $placeholders = implode(',', array_fill(0, count($licenseIds), '?'));
        $assetsStmt = $this->db->prepare("
            SELECT asign.LicenciaId, a.Id AS ActivoId, a.CodigoInventario, a.Placa,
                   c.Nombre AS TipoActivo
            FROM gestion_licencias_asignaciones asign
            INNER JOIN gestion_activos a ON a.Id = asign.ActivoId
            LEFT JOIN gestion_activos_categorias c ON c.Id = a.CategoriaId
            WHERE asign.Estado = 'activa' AND asign.LicenciaId IN ({$placeholders})
            ORDER BY a.CodigoInventario ASC
        ");
        $assetsStmt->execute($licenseIds);
        $assetsByLicense = [];
        foreach ($assetsStmt->fetchAll(PDO::FETCH_ASSOC) as $asset) {
            $assetsByLicense[(int) $asset['LicenciaId']][] = $asset;
        }
        foreach ($licenses as &$license) {
            $license['Activos'] = $assetsByLicense[(int) $license['Id']] ?? [];
        }
        unset($license);

        return $licenses;
    }

    public function getOptions(): array
    {
        return [
            'Empresas' => $this->db->query('SELECT Id, Nombre FROM gcp_empresas WHERE Estado = 1 ORDER BY Nombre ASC')->fetchAll(PDO::FETCH_ASSOC),
            'Areas' => $this->db->query('SELECT Id, Nombre FROM gcp_areas WHERE Estado = 1 ORDER BY Nombre ASC')->fetchAll(PDO::FETCH_ASSOC),
            'Activos' => $this->db->query("
                SELECT a.Id, a.EmpresaId, a.CodigoInventario, a.Placa, c.Nombre AS TipoActivo
                FROM gestion_activos a
                LEFT JOIN gestion_activos_categorias c ON c.Id = a.CategoriaId
                WHERE a.Estado = 1 AND a.EstadoActivo <> 'retirado'
                ORDER BY a.CodigoInventario ASC
            ")->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function isActiveCompany(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM gcp_empresas WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function isActiveArea(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM gcp_areas WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function getActiveAssetIds(array $ids, int $companyId): array
    {
        if (!$ids) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            SELECT Id
            FROM gestion_activos
            WHERE Estado = 1 AND EstadoActivo <> 'retirado'
              AND EmpresaId = ? AND Id IN ({$placeholders})
        ");
        $stmt->execute(array_merge([$companyId], $ids));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function create(array $license): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO gestion_licencias
                    (Nombre, Tipo, Proveedor, NumeroLicencia, Cantidad, CantidadUsada,
                     FechaVencimiento, Notas, EmpresaId, AreaId, Estado)
                VALUES
                    (:Nombre, :Tipo, :Proveedor, :NumeroLicencia, :Cantidad, :CantidadUsada,
                     :FechaVencimiento, :Notas, :EmpresaId, :AreaId, 1)
            ");
            $stmt->execute([
                ':Nombre' => $license['Nombre'],
                ':Tipo' => $license['Tipo'],
                ':Proveedor' => $license['Proveedor'],
                ':NumeroLicencia' => $license['NumeroLicencia'],
                ':Cantidad' => $license['Cantidad'],
                ':CantidadUsada' => count($license['ActivosIds']),
                ':FechaVencimiento' => $license['FechaVencimiento'],
                ':Notas' => $license['Notas'],
                ':EmpresaId' => $license['EmpresaId'],
                ':AreaId' => $license['AreaId'],
            ]);
            $licenseId = (int) $this->db->lastInsertId();
            $assignment = $this->db->prepare("
                INSERT INTO gestion_licencias_asignaciones
                    (LicenciaId, UsuarioId, ActivoId, FechaAsignacion, Estado)
                VALUES (:LicenciaId, NULL, :ActivoId, CURRENT_DATE, 'activa')
            ");
            foreach ($license['ActivosIds'] as $assetId) {
                $assignment->execute([':LicenciaId' => $licenseId, ':ActivoId' => $assetId]);
            }
            $this->db->commit();
            return $licenseId;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function update(int $id, array $license): bool
    {
        $this->db->beginTransaction();
        try {
            $licenseStmt = $this->db->prepare('SELECT Id FROM gestion_licencias WHERE Id = :Id AND Estado = 1 FOR UPDATE');
            $licenseStmt->execute([':Id' => $id]);
            if (!$licenseStmt->fetchColumn()) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                    UPDATE gestion_licencias
                    SET Nombre = :Nombre, Tipo = :Tipo, Proveedor = :Proveedor,
                        NumeroLicencia = :NumeroLicencia, Cantidad = :Cantidad,
                        CantidadUsada = :CantidadUsada, FechaVencimiento = :FechaVencimiento,
                        Notas = :Notas, EmpresaId = :EmpresaId, AreaId = :AreaId
                    WHERE Id = :Id AND Estado = 1
                ");
            $stmt->execute([
                    ':Nombre' => $license['Nombre'],
                    ':Tipo' => $license['Tipo'],
                    ':Proveedor' => $license['Proveedor'],
                    ':NumeroLicencia' => $license['NumeroLicencia'],
                    ':Cantidad' => $license['Cantidad'],
                    ':CantidadUsada' => count($license['ActivosIds']),
                    ':FechaVencimiento' => $license['FechaVencimiento'],
                    ':Notas' => $license['Notas'],
                    ':EmpresaId' => $license['EmpresaId'],
                    ':AreaId' => $license['AreaId'],
                    ':Id' => $id,
            ]);

            $currentStmt = $this->db->prepare("
                    SELECT ActivoId FROM gestion_licencias_asignaciones
                    WHERE LicenciaId = :LicenciaId AND Estado = 'activa' AND ActivoId IS NOT NULL
                ");
            $currentStmt->execute([':LicenciaId' => $id]);
            $currentAssetIds = array_map('intval', $currentStmt->fetchAll(PDO::FETCH_COLUMN));
            $requestedAssetIds = array_map('intval', $license['ActivosIds']);
            $removedAssetIds = array_diff($currentAssetIds, $requestedAssetIds);
            $addedAssetIds = array_diff($requestedAssetIds, $currentAssetIds);

            $revoke = $this->db->prepare("
                    UPDATE gestion_licencias_asignaciones
                    SET Estado = 'revocada', FechaDesasignacion = CURRENT_DATE
                    WHERE LicenciaId = :LicenciaId AND ActivoId = :ActivoId AND Estado = 'activa'
                ");
            foreach ($removedAssetIds as $assetId) {
                $revoke->execute([':LicenciaId' => $id, ':ActivoId' => $assetId]);
            }

            $add = $this->db->prepare("
                    INSERT INTO gestion_licencias_asignaciones
                        (LicenciaId, UsuarioId, ActivoId, FechaAsignacion, Estado)
                    VALUES (:LicenciaId, NULL, :ActivoId, CURRENT_DATE, 'activa')
                ");
            foreach ($addedAssetIds as $assetId) {
                $add->execute([':LicenciaId' => $id, ':ActivoId' => $assetId]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
