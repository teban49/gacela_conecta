<?php

namespace App\Repositories\Security;

use App\Repositories\BaseRepository;
use PDO;

class RoleRepository extends BaseRepository
{
    protected string $table = 'gcp_roles';

    public function getAll(): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            ORDER BY Nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        return $this->findById($id);
    }

    public function create(array $role): int
    {
        $sql = "
            INSERT INTO {$this->table} (Nombre, Codigo, Descripcion, NivelAcceso, Estado)
            VALUES (:Nombre, :Codigo, :Descripcion, :NivelAcceso, :Estado)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':Nombre' => $role['Nombre'],
            ':Codigo' => $role['Codigo'],
            ':Descripcion' => $role['Descripcion'] ?? null,
            ':NivelAcceso' => $role['NivelAcceso'] ?? 1,
            ':Estado' => $role['Estado'] ?? 1,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $role): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET Nombre = :Nombre, Codigo = :Codigo, Descripcion = :Descripcion, NivelAcceso = :NivelAcceso, Estado = :Estado
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':Nombre' => $role['Nombre'],
            ':Codigo' => $role['Codigo'],
            ':Descripcion' => $role['Descripcion'] ?? null,
            ':NivelAcceso' => $role['NivelAcceso'] ?? 1,
            ':Estado' => $role['Estado'] ?? 1,
        ]);
    }

    public function getRolePermissions(int $roleId): array
    {
        $sql = "
            SELECT p.Id, p.CodigoUnico, p.Modulo, p.Accion, p.AplicacionId, a.Nombre AS AplicacionNombre
            FROM gcp_rolespermisos rp
            INNER JOIN gcp_permisos p ON p.Id = rp.PermisoId
            LEFT JOIN gcp_aplicaciones a ON a.Id = p.AplicacionId
            WHERE rp.RolId = :roleId
            ORDER BY a.OrdenMenu ASC, a.Nombre ASC, p.CodigoUnico ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':roleId' => $roleId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $this->db->beginTransaction();

        try {
            $deleteSql = 'DELETE FROM gcp_rolespermisos WHERE RolId = :roleId';
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([':roleId' => $roleId]);

            if (!empty($permissionIds)) {
                $insertSql = 'INSERT INTO gcp_rolespermisos (RolId, PermisoId) VALUES (:roleId, :permissionId)';
                $insertStmt = $this->db->prepare($insertSql);

                foreach ($permissionIds as $permissionId) {
                    $insertStmt->execute([
                        ':roleId' => $roleId,
                        ':permissionId' => (int) $permissionId,
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
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
