<?php

namespace App\Repositories\Gestion;

use App\Repositories\BaseRepository;
use PDO;

class MantenimientoRepository extends BaseRepository
{
    public function getPending(): array
    {
        $stmt = $this->db->query("
            WITH RECURSIVE periodos (ActivoId, FechaProgramada, Anio, Semestre) AS (
                SELECT a.Id,
                       DATE_ADD(DATE(a.FechaFin), INTERVAL 6 MONTH),
                       YEAR(DATE_ADD(DATE(a.FechaFin), INTERVAL 6 MONTH)),
                       IF(MONTH(DATE_ADD(DATE(a.FechaFin), INTERVAL 6 MONTH)) <= 6, 1, 2)
                FROM gestion_activos a
                WHERE a.Estado = 1
                  AND a.EstadoActivo <> 'retirado'
                  AND a.TipoPropiedad = 'propio'
                  AND a.FechaFin IS NOT NULL
                  AND DATE_ADD(DATE(a.FechaFin), INTERVAL 6 MONTH) <= CURRENT_DATE
                UNION ALL
                SELECT ActivoId,
                       DATE_ADD(FechaProgramada, INTERVAL 6 MONTH),
                       YEAR(DATE_ADD(FechaProgramada, INTERVAL 6 MONTH)),
                       IF(MONTH(DATE_ADD(FechaProgramada, INTERVAL 6 MONTH)) <= 6, 1, 2)
                FROM periodos
                WHERE DATE_ADD(FechaProgramada, INTERVAL 6 MONTH) <= CURRENT_DATE
            )
            SELECT a.Id AS ActivoId, a.CodigoInventario, a.Placa, a.EmpresaId,
                   e.Nombre AS EmpresaNombre, c.Nombre AS TipoActivo,
                   agencia.Nombre AS AgenciaNombre,
                   periodo.Anio, periodo.Semestre,
                   periodo.FechaProgramada AS FechaLimite
            FROM gestion_activos a
            INNER JOIN gestion_activos_categorias c ON c.Id = a.CategoriaId
            LEFT JOIN gcp_empresas e ON e.Id = a.EmpresaId
            LEFT JOIN gcp_usuarios asignado ON asignado.Id = a.AsignadoA
            LEFT JOIN gcp_agencias agencia ON agencia.Id = asignado.AgenciaId
            INNER JOIN periodos periodo ON periodo.ActivoId = a.Id
            LEFT JOIN gestion_mantenimiento m
                ON m.ActivoId = a.Id
                AND m.FechaProgramada = periodo.FechaProgramada
            WHERE a.Estado = 1
              AND a.EstadoActivo <> 'retirado'
              AND a.TipoPropiedad = 'propio'
              AND (m.Id IS NULL OR m.Estado <> 'completado')
            ORDER BY periodo.Anio ASC, periodo.Semestre ASC, a.CodigoInventario ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isEligiblePeriod(int $assetId, int $year, int $semester, string $scheduledDate): bool
    {
        $stmt = $this->db->prepare("
            WITH RECURSIVE periodos (FechaProgramada, Anio, Semestre) AS (
                SELECT DATE_ADD(DATE(a.FechaFin), INTERVAL 6 MONTH),
                       YEAR(DATE_ADD(DATE(a.FechaFin), INTERVAL 6 MONTH)),
                       IF(MONTH(DATE_ADD(DATE(a.FechaFin), INTERVAL 6 MONTH)) <= 6, 1, 2)
                FROM gestion_activos a
                WHERE a.Id = :Id
                  AND a.Estado = 1
                  AND a.EstadoActivo <> 'retirado'
                  AND a.TipoPropiedad = 'propio'
                  AND a.FechaFin IS NOT NULL
                  AND DATE_ADD(DATE(a.FechaFin), INTERVAL 6 MONTH) <= CURRENT_DATE
                UNION ALL
                SELECT DATE_ADD(FechaProgramada, INTERVAL 6 MONTH),
                       YEAR(DATE_ADD(FechaProgramada, INTERVAL 6 MONTH)),
                       IF(MONTH(DATE_ADD(FechaProgramada, INTERVAL 6 MONTH)) <= 6, 1, 2)
                FROM periodos
                WHERE DATE_ADD(FechaProgramada, INTERVAL 6 MONTH) <= CURRENT_DATE
            )
            SELECT 1
            FROM periodos
            WHERE Anio = :Anio
              AND Semestre = :Semestre
              AND FechaProgramada = :FechaProgramada
        ");
        $stmt->execute([
            ':Id' => $assetId,
            ':Anio' => $year,
            ':Semestre' => $semester,
            ':FechaProgramada' => $scheduledDate,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function isActiveSupplier(int $supplierId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM gestion_proveedores WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $supplierId]);
        return (bool) $stmt->fetchColumn();
    }

    public function complete(int $assetId, int $year, int $semester, string $scheduledDate, string $executor, ?int $supplierId, int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            $existingStmt = $this->db->prepare("
                SELECT Id, Estado
                FROM gestion_mantenimiento
                WHERE ActivoId = :ActivoId AND FechaProgramada = :FechaProgramada
                FOR UPDATE
            ");
            $existingStmt->execute([
                ':ActivoId' => $assetId,
                ':FechaProgramada' => $scheduledDate,
            ]);
            $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
            if ($existing && $existing['Estado'] === 'completado') {
                $this->db->rollBack();
                return false;
            }

            $description = 'Mantenimiento preventivo semestral ' . $year . ' - semestre ' . $semester;
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
