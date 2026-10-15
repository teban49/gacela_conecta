<?php

namespace App\Repositories\Gestion;

use App\Repositories\BaseRepository;
use PDO;

class ActivoRepository extends BaseRepository
{
    protected string $table = 'gestion_activos';

    public function getAll(): array
    {
        $sql = "
            SELECT a.*,
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   e.Nombre AS EmpresaNombre, p.Nombre AS ProveedorNombre,
                   u.Nombres AS AsignadoNombre, u.Apellidos AS AsignadoApellidos,
                   pending.UsuarioId AS UsuarioAsignacionPendienteId,
                   pending.Nombres AS UsuarioAsignacionPendienteNombre,
                   pending.Apellidos AS UsuarioAsignacionPendienteApellidos
            FROM {$this->table} a
            LEFT JOIN gestion_activos_categorias c ON c.Id = a.CategoriaId
            LEFT JOIN gcp_empresas e ON e.Id = a.EmpresaId
            LEFT JOIN gestion_proveedores p ON p.Id = a.ProveedorId
            LEFT JOIN gcp_usuarios u ON u.Id = a.AsignadoA
            LEFT JOIN (
                SELECT aa.ActivoId, aa.UsuarioId, usr.Nombres, usr.Apellidos
                FROM gestion_activos_asignaciones aa
                INNER JOIN gcp_usuarios usr ON usr.Id = aa.UsuarioId
                WHERE aa.Estado = 'pendiente'
            ) pending ON pending.ActivoId = a.Id
            ORDER BY a.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssignableUsers(): array
    {
        $sql = "
            SELECT Id, Nombres, Apellidos, EmailCorporativo
            FROM gcp_usuarios
            WHERE Estado = 1
            ORDER BY Nombres ASC, Apellidos ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveCategories(): array
    {
        $stmt = $this->db->prepare("
            SELECT Id, Nombre, Descripcion, Color
            FROM gestion_activos_categorias
            WHERE Estado = 1
            ORDER BY Nombre ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveCompanies(): array
    {
        $stmt = $this->db->query('SELECT Id, Nombre FROM gcp_empresas WHERE Estado = 1 ORDER BY Nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveSuppliers(): array
    {
        $stmt = $this->db->query('SELECT Id, Nombre FROM gestion_proveedores WHERE Estado = 1 ORDER BY Nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isActiveCompany(int $companyId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM gcp_empresas WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $companyId]);
        return (bool) $stmt->fetchColumn();
    }

    public function isActiveSupplier(int $supplierId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM gestion_proveedores WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $supplierId]);
        return (bool) $stmt->fetchColumn();
    }

    public function createSupplier(string $name): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO gestion_proveedores (Nombre, Categoria, Estado)
            VALUES (:Nombre, 'hardware', 1)
        ");
        $stmt->execute([':Nombre' => $name]);
        return (int) $this->db->lastInsertId();
    }

    public function findActiveCategory(int $categoryId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT Id
            FROM gestion_activos_categorias
            WHERE Id = :Id AND Estado = 1
            LIMIT 1
        ");
        $stmt->execute([':Id' => $categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        return $category ?: null;
    }

    public function isAssignableUserActive(int $usuarioId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM gcp_usuarios WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    public function getPendingAssignmentsForUser(int $usuarioId): array
    {
        $stmt = $this->db->prepare("
            SELECT aa.Id AS AsignacionId, aa.ActivoId, aa.FechaSolicitud,
                   a.CodigoInventario, a.Placa, c.Nombre AS TipoActivo,
                   asignador.Nombres AS SolicitadoPorNombre,
                   asignador.Apellidos AS SolicitadoPorApellidos
            FROM gestion_activos_asignaciones aa
            INNER JOIN gestion_activos a ON a.Id = aa.ActivoId
            INNER JOIN gestion_activos_categorias c ON c.Id = a.CategoriaId
            INNER JOIN gcp_usuarios asignador ON asignador.Id = aa.AsignadoPorUsuarioId
            WHERE aa.UsuarioId = :UsuarioId
              AND aa.Estado = 'pendiente'
              AND a.Estado = 1
              AND a.EstadoActivo <> 'retirado'
            ORDER BY aa.FechaSolicitud DESC
        ");
        $stmt->execute([':UsuarioId' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCategory(int $categoriaId): array
    {
        $sql = "
            SELECT a.*, 
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   u.Nombres AS AsignadoNombre, u.Apellidos AS AsignadoApellidos
            FROM {$this->table} a
            LEFT JOIN gestion_activos_categorias c ON c.Id = a.CategoriaId
            LEFT JOIN gcp_usuarios u ON u.Id = a.AsignadoA
            WHERE a.CategoriaId = :categoriaId
            ORDER BY a.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':categoriaId' => $categoriaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByAssignee(int $usuarioId): array
    {
        $sql = "
            SELECT a.*, 
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   u.Nombres AS AsignadoNombre, u.Apellidos AS AsignadoApellidos
            FROM {$this->table} a
            LEFT JOIN gestion_activos_categorias c ON c.Id = a.CategoriaId
            LEFT JOIN gcp_usuarios u ON u.Id = a.AsignadoA
            WHERE a.AsignadoA = :usuarioId AND a.EstadoActivo = 'en_uso'
            ORDER BY a.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuarioId' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateInventoryCode(): string
    {
        $year = date('Y');
        $prefix = "ACT-{$year}-";
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE CodigoInventario LIKE :prefix";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $result = $stmt->fetch();
        
        $sequence = str_pad(($result['count'] + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . $sequence;
    }

    public function create(array $activo): int
    {
        $codigoInventario = $activo['CodigoInventario'] ?? $this->generateInventoryCode();
        
        $sql = "
            INSERT INTO {$this->table}
                (CategoriaId, EmpresaId, TipoPropiedad, ProveedorId, NumeroFacturaContrato, FechaInicio, FechaFin, Placa, Observaciones,
                 CodigoInventario, Descripcion, Marca, Modelo, NumeroSerie, EstadoActivo, AsignadoA, Estado)
            VALUES
                (:CategoriaId, :EmpresaId, :TipoPropiedad, :ProveedorId, :NumeroFacturaContrato, :FechaInicio, :FechaFin, :Placa, :Observaciones,
                 :CodigoInventario, :Descripcion, :Marca, :Modelo, :NumeroSerie, :EstadoActivo, :AsignadoA, :Estado)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':CategoriaId' => $activo['CategoriaId'],
            ':EmpresaId' => $activo['EmpresaId'],
            ':TipoPropiedad' => $activo['TipoPropiedad'],
            ':ProveedorId' => $activo['ProveedorId'] ?? null,
            ':NumeroFacturaContrato' => $activo['NumeroFacturaContrato'] ?? null,
            ':FechaInicio' => $activo['FechaInicio'] ?? null,
            ':FechaFin' => $activo['FechaFin'] ?? null,
            ':Placa' => $activo['Placa'] ?? null,
            ':Observaciones' => $activo['Observaciones'] ?? null,
            ':CodigoInventario' => $codigoInventario,
            ':Descripcion' => $activo['Descripcion'] ?? null,
            ':Marca' => $activo['Marca'] ?? null,
            ':Modelo' => $activo['Modelo'] ?? null,
            ':NumeroSerie' => $activo['NumeroSerie'] ?? null,
            ':EstadoActivo' => $activo['EstadoActivo'] ?? 'nuevo',
            ':AsignadoA' => $activo['AsignadoA'] ?? null,
            ':Estado' => $activo['Estado'] ?? 1,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $activo): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                CategoriaId = :CategoriaId,
                EmpresaId = :EmpresaId,
                TipoPropiedad = :TipoPropiedad,
                ProveedorId = :ProveedorId,
                NumeroFacturaContrato = :NumeroFacturaContrato,
                FechaInicio = :FechaInicio,
                FechaFin = :FechaFin,
                Placa = :Placa,
                Observaciones = :Observaciones,
                CodigoInventario = :CodigoInventario,
                Descripcion = :Descripcion,
                Marca = :Marca,
                Modelo = :Modelo,
                NumeroSerie = :NumeroSerie,
                EstadoActivo = :EstadoActivo,
                AsignadoA = :AsignadoA,
                Estado = :Estado
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':CategoriaId' => $activo['CategoriaId'],
            ':EmpresaId' => $activo['EmpresaId'],
            ':TipoPropiedad' => $activo['TipoPropiedad'],
            ':ProveedorId' => $activo['ProveedorId'] ?? null,
            ':NumeroFacturaContrato' => $activo['NumeroFacturaContrato'] ?? null,
            ':FechaInicio' => $activo['FechaInicio'] ?? null,
            ':FechaFin' => $activo['FechaFin'] ?? null,
            ':Placa' => $activo['Placa'] ?? null,
            ':Observaciones' => $activo['Observaciones'] ?? null,
            ':CodigoInventario' => $activo['CodigoInventario'],
            ':Descripcion' => $activo['Descripcion'] ?? null,
            ':Marca' => $activo['Marca'] ?? null,
            ':Modelo' => $activo['Modelo'] ?? null,
            ':NumeroSerie' => $activo['NumeroSerie'] ?? null,
            ':EstadoActivo' => $activo['EstadoActivo'] ?? 'nuevo',
            ':AsignadoA' => $activo['AsignadoA'] ?? null,
            ':Estado' => $activo['Estado'] ?? 1,
        ]);
    }

    public function changeStatus(int $id, bool|string $status): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET Estado = :Estado
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':Estado' => $status ? 1 : 0,
        ]);
    }

    public function changeAssetStatus(int $id, string $estadoActivo): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET EstadoActivo = :EstadoActivo
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':EstadoActivo' => $estadoActivo,
        ]);
    }

    public function requestAssignment(int $id, int $usuarioId, int $requestedBy): bool
    {
        $this->db->beginTransaction();
        try {
            $assetStmt = $this->db->prepare("SELECT AsignadoA, Estado, EstadoActivo FROM {$this->table} WHERE Id = :Id FOR UPDATE");
            $assetStmt->execute([':Id' => $id]);
            $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);
            if (!$asset || (int) $asset['Estado'] !== 1 || $asset['EstadoActivo'] === 'retirado' || $asset['AsignadoA'] !== null) {
                $this->db->rollBack();
                return false;
            }

            $pendingStmt = $this->db->prepare("
                SELECT Id FROM gestion_activos_asignaciones
                WHERE ActivoId = :ActivoId AND Estado = 'pendiente'
                LIMIT 1 FOR UPDATE
            ");
            $pendingStmt->execute([':ActivoId' => $id]);
            if ($pendingStmt->fetchColumn()) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                INSERT INTO gestion_activos_asignaciones (ActivoId, UsuarioId, AsignadoPorUsuarioId)
                VALUES (:ActivoId, :UsuarioId, :AsignadoPorUsuarioId)
            ");
            $stmt->execute([
                ':ActivoId' => $id,
                ':UsuarioId' => $usuarioId,
                ':AsignadoPorUsuarioId' => $requestedBy,
            ]);
            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function respondToAssignment(int $assignmentId, int $userId, bool $accept): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT aa.ActivoId, aa.Estado, a.AsignadoA, a.Estado AS ActivoEstado, a.EstadoActivo
                FROM gestion_activos_asignaciones aa
                INNER JOIN {$this->table} a ON a.Id = aa.ActivoId
                WHERE aa.Id = :Id AND aa.UsuarioId = :UsuarioId
                FOR UPDATE
            ");
            $stmt->execute([':Id' => $assignmentId, ':UsuarioId' => $userId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request || $request['Estado'] !== 'pendiente') {
                $this->db->rollBack();
                return false;
            }

            if ($accept) {
                if ($request['AsignadoA'] !== null || (int) $request['ActivoEstado'] !== 1 || $request['EstadoActivo'] === 'retirado') {
                    $this->db->rollBack();
                    return false;
                }
                $updateAsset = $this->db->prepare("
                    UPDATE {$this->table}
                    SET AsignadoA = :UsuarioId, EstadoActivo = 'en_uso'
                    WHERE Id = :ActivoId AND AsignadoA IS NULL AND Estado = 1
                ");
                $updateAsset->execute([':UsuarioId' => $userId, ':ActivoId' => $request['ActivoId']]);
                if ($updateAsset->rowCount() !== 1) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $updateRequest = $this->db->prepare("
                UPDATE gestion_activos_asignaciones
                SET Estado = :Estado, FechaRespuesta = CURRENT_TIMESTAMP
                WHERE Id = :Id AND UsuarioId = :UsuarioId AND Estado = 'pendiente'
            ");
            $updateRequest->execute([
                ':Estado' => $accept ? 'aceptada' : 'rechazada',
                ':Id' => $assignmentId,
                ':UsuarioId' => $userId,
            ]);
            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function unassign(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET AsignadoA = NULL,
                EstadoActivo = CASE WHEN EstadoActivo = 'en_uso' THEN 'nuevo' ELSE EstadoActivo END
            WHERE Id = :Id
        ");

        return $stmt->execute([':Id' => $id]);
    }

    public function retire(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->table}
                SET AsignadoA = NULL, EstadoActivo = 'retirado', Estado = 0
                WHERE Id = :Id
            ");
            $stmt->execute([':Id' => $id]);
            $cancel = $this->db->prepare("
                UPDATE gestion_activos_asignaciones
                SET Estado = 'cancelada', FechaRespuesta = CURRENT_TIMESTAMP
                WHERE ActivoId = :ActivoId AND Estado = 'pendiente'
            ");
            $cancel->execute([':ActivoId' => $id]);
            $this->db->commit();
            return $stmt->rowCount() > 0;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}