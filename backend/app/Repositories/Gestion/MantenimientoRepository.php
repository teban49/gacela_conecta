<?php

namespace App\Repositories\Gestion;

use App\Repositories\BaseRepository;
use PDO;

class MantenimientoRepository extends BaseRepository
{
    public function getPending(): array
    {
        $stmt = $this->db->query("
            WITH RECURSIVE periodos (ActivoId, Anio, Semestre) AS (
                SELECT a.Id,
                       YEAR(COALESCE(a.FechaInicio, DATE(a.FechaCreacion))),
                       IF(MONTH(COALESCE(a.FechaInicio, DATE(a.FechaCreacion))) <= 6, 1, 2)
                FROM gestion_activos a
                WHERE a.Estado = 1
                  AND a.EstadoActivo <> 'retirado'
                  AND COALESCE(a.FechaInicio, DATE(a.FechaCreacion)) <= CURRENT_DATE
                UNION ALL
                SELECT ActivoId,
                       Anio + IF(Semestre = 2, 1, 0),
                       IF(Semestre = 1, 2, 1)
                FROM periodos
                WHERE Anio < YEAR(CURRENT_DATE)
                   OR (Anio = YEAR(CURRENT_DATE)
                       AND Semestre < IF(MONTH(CURRENT_DATE) <= 6, 1, 2))
            )
            SELECT a.Id AS ActivoId, a.CodigoInventario, a.Placa, a.EmpresaId,
                   e.Nombre AS EmpresaNombre, c.Nombre AS TipoActivo,
                   periodo.Anio, periodo.Semestre,
                   STR_TO_DATE(
                       CONCAT(periodo.Anio, IF(periodo.Semestre = 1, '-06-30', '-12-31')),
                       '%Y-%m-%d'
                   ) AS FechaLimite
            FROM gestion_activos a
            INNER JOIN gestion_activos_categorias c ON c.Id = a.CategoriaId
            LEFT JOIN gcp_empresas e ON e.Id = a.EmpresaId
            INNER JOIN periodos periodo ON periodo.ActivoId = a.Id
            LEFT JOIN gestion_mantenimiento m
                ON m.ActivoId = a.Id
                AND m.Anio = periodo.Anio
                AND m.Semestre = periodo.Semestre
            WHERE a.Estado = 1
              AND a.EstadoActivo <> 'retirado'
              AND (m.Id IS NULL OR m.Estado <> 'completado')
            ORDER BY periodo.Anio ASC, periodo.Semestre ASC, a.CodigoInventario ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isEligiblePeriod(int $assetId, int $year, int $semester): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM gestion_activos a
            WHERE a.Id = :Id
              AND a.Estado = 1
              AND a.EstadoActivo <> 'retirado'
              AND COALESCE(a.FechaInicio, DATE(a.FechaCreacion)) <= CURRENT_DATE
              AND (
                    YEAR(COALESCE(a.FechaInicio, DATE(a.FechaCreacion))) < :AnioInicial
                    OR (
                        YEAR(COALESCE(a.FechaInicio, DATE(a.FechaCreacion))) = :AnioComparacion
                        AND IF(MONTH(COALESCE(a.FechaInicio, DATE(a.FechaCreacion))) <= 6, 1, 2) <= :SemestreInicial
                    )
              )
              AND (
                    :AnioActual1 < YEAR(CURRENT_DATE)
                    OR (:AnioActual2 = YEAR(CURRENT_DATE)
                        AND :SemestreActual <= IF(MONTH(CURRENT_DATE) <= 6, 1, 2))
              )
        ");
        $stmt->execute([
            ':Id' => $assetId,
            ':AnioInicial' => $year,
            ':AnioComparacion' => $year,
            ':SemestreInicial' => $semester,
            ':AnioActual1' => $year,
            ':AnioActual2' => $year,
            ':SemestreActual' => $semester,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function isActiveSupplier(int $supplierId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM gestion_proveedores WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $supplierId]);
        return (bool) $stmt->fetchColumn();
    }

    public function complete(int $assetId, int $year, int $semester, string $executor, ?int $supplierId, int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            $existingStmt = $this->db->prepare("
                SELECT Id, Estado
                FROM gestion_mantenimiento
                WHERE ActivoId = :ActivoId AND Anio = :Anio AND Semestre = :Semestre
                FOR UPDATE
            ");
            $existingStmt->execute([
                ':ActivoId' => $assetId,
                ':Anio' => $year,
                ':Semestre' => $semester,
            ]);
            $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
            if ($existing && $existing['Estado'] === 'completado') {
                $this->db->rollBack();
                return false;
            }

            $description = 'Mantenimiento preventivo semestral ' . $year . ' - semestre ' . $semester;
            $scheduledDate = $year . ($semester === 1 ? '-06-30' : '-12-31');
            if ($existing) {
                $stmt = $this->db->prepare("
                    UPDATE gestion_mantenimiento
                    SET Tipo = 'preventivo', Descripcion = :Descripcion, TecnicoId = :TecnicoId,
                        FechaProgramada = :FechaProgramada, FechaRealizacion = CURRENT_TIMESTAMP,
                        Estado = 'completado', Ejecutor = :Ejecutor, ProveedorId = :ProveedorId
                    WHERE Id = :Id
                ");
                $stmt->execute([
                    ':Descripcion' => $description,
                    ':TecnicoId' => $userId,
                    ':FechaProgramada' => $scheduledDate,
                    ':Ejecutor' => $executor,
                    ':ProveedorId' => $supplierId,
                    ':Id' => $existing['Id'],
                ]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO gestion_mantenimiento
                        (ActivoId, Anio, Semestre, Tipo, Descripcion, TecnicoId, FechaProgramada,
                         FechaRealizacion, Estado, Ejecutor, ProveedorId)
                    VALUES
                        (:ActivoId, :Anio, :Semestre, 'preventivo', :Descripcion, :TecnicoId,
                         :FechaProgramada, CURRENT_TIMESTAMP, 'completado', :Ejecutor, :ProveedorId)
                ");
                $stmt->execute([
                    ':ActivoId' => $assetId,
                    ':Anio' => $year,
                    ':Semestre' => $semester,
                    ':Descripcion' => $description,
                    ':TecnicoId' => $userId,
                    ':FechaProgramada' => $scheduledDate,
                    ':Ejecutor' => $executor,
                    ':ProveedorId' => $supplierId,
                ]);
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
