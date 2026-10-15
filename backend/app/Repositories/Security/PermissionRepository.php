<?php

namespace App\Repositories\Security;

use App\Repositories\BaseRepository;
use PDO;

class PermissionRepository extends BaseRepository
{
    protected string $table = 'gcp_permisos';

    public function getAll(): array
    {
        $sql = "
            SELECT
                p.Id,
                p.Modulo,
                p.Accion,
                p.Descripcion,
                p.CodigoUnico,
                p.Estado,
                p.AplicacionId,
                a.Nombre AS AplicacionNombre,
                a.Codigo AS AplicacionCodigo
            FROM {$this->table} p
            LEFT JOIN gcp_aplicaciones a ON a.Id = p.AplicacionId
            ORDER BY a.OrdenMenu ASC, a.Nombre ASC, p.CodigoUnico ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        return $this->findById($id);
    }

    public function getByApplication(int $applicationId): array
    {
        $sql = "
            SELECT
                p.Id,
                p.Modulo,
                p.Accion,
                p.Descripcion,
                p.CodigoUnico,
                p.Estado,
                p.AplicacionId,
                a.Nombre AS AplicacionNombre,
                a.Codigo AS AplicacionCodigo
            FROM {$this->table} p
            LEFT JOIN gcp_aplicaciones a ON a.Id = p.AplicacionId
            WHERE p.AplicacionId = :applicationId
            ORDER BY p.CodigoUnico ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':applicationId' => $applicationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCode(string $code): ?array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE CodigoUnico = :code
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':code' => $code]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $permission): int
    {
        $sql = "
            INSERT INTO {$this->table} (AplicacionId, Modulo, Accion, Descripcion, CodigoUnico, Estado)
            VALUES (:AplicacionId, :Modulo, :Accion, :Descripcion, :CodigoUnico, :Estado)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':AplicacionId' => $permission['AplicacionId'],
            ':Modulo' => $permission['Modulo'],
            ':Accion' => $permission['Accion'],
            ':Descripcion' => $permission['Descripcion'] ?? null,
            ':CodigoUnico' => $permission['CodigoUnico'],
            ':Estado' => $permission['Estado'] ?? 1,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $permission): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                AplicacionId = :AplicacionId,
                Modulo = :Modulo,
                Accion = :Accion,
                Descripcion = :Descripcion,
                CodigoUnico = :CodigoUnico,
                Estado = :Estado
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':AplicacionId' => $permission['AplicacionId'],
            ':Modulo' => $permission['Modulo'],
            ':Accion' => $permission['Accion'],
            ':Descripcion' => $permission['Descripcion'] ?? null,
            ':CodigoUnico' => $permission['CodigoUnico'],
            ':Estado' => $permission['Estado'] ?? 1,
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
}
