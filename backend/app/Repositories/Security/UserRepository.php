<?php

namespace App\Repositories\Security;

use App\Repositories\BaseRepository;
use PDO;

class UserRepository extends BaseRepository
{
    protected string $table = 'gcp_usuarios';

    public function getAll(): array
    {
        $sql = "
            SELECT u.Id, u.AgenciaId, u.CargoId, u.CargoJefeDirectoId,
                   jefeCargo.Nombre AS JefeDirectoCargoNombre,
                   a.CiudadId, c.AreaId,
                   MIN(ue.EmpresaId) AS EmpresaId,
                   GROUP_CONCAT(DISTINCT ue.EmpresaId ORDER BY ue.EmpresaId) AS EmpresasIds,
                   u.Nombres, u.Apellidos, u.EmailCorporativo, u.Telefono, u.Estado,
                   r.Id AS RolId,
                   r.Nombre AS RolNombre,
                   r.Codigo AS RolCodigo,
                   r.Id AS RolesIds,
                   r.Nombre AS RolesNombres,
                   r.Codigo AS RolesCodigos
            FROM gcp_usuarios u
            INNER JOIN gcp_agencias a ON a.Id = u.AgenciaId
            INNER JOIN gcp_cargos c ON c.Id = u.CargoId
            LEFT JOIN gcp_cargos jefeCargo ON jefeCargo.Id = u.CargoJefeDirectoId
            LEFT JOIN gcp_usuariosempresas ue ON ue.UsuarioId = u.Id
            LEFT JOIN gcp_roles r ON r.Id = u.RolId
            GROUP BY u.Id
            ORDER BY u.Nombres ASC, u.Apellidos ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveJobTitles(): array
    {
        $stmt = $this->db->query("
            SELECT Id, Nombre, Codigo
            FROM gcp_cargos
            WHERE Estado = 1
            ORDER BY Nombre ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        return $this->findById($id);
    }

    public function getUserRoles(int $userId): array
    {
        $sql = "
            SELECT r.*
            FROM gcp_usuarios u
            INNER JOIN gcp_roles r ON r.Id = u.RolId
            WHERE u.Id = :userId
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setRole(int $userId, int $roleId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET RolId = :roleId
            WHERE Id = :userId
              AND EXISTS (SELECT 1 FROM gcp_roles WHERE Id = :validatedRoleId AND Estado = 1)
        ");

        return $stmt->execute([
            ':userId' => $userId,
            ':roleId' => $roleId,
            ':validatedRoleId' => $roleId,
        ]);
    }

    public function create(array $user): int
    {
        $sql = "
            INSERT INTO {$this->table} (AgenciaId, CargoId, RolId, CargoJefeDirectoId, Nombres, Apellidos, EmailCorporativo, Telefono, Estado)
            VALUES (
                :AgenciaId, :CargoId,
                (SELECT Id FROM gcp_roles WHERE Codigo = 'usuario' AND Estado = 1 LIMIT 1),
                :CargoJefeDirectoId, :Nombres, :Apellidos, :EmailCorporativo, :Telefono, :Estado
            )
        ";

        $companyIds = $this->getCompanyIds($user);
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':AgenciaId' => $user['AgenciaId'],
                ':CargoId' => $user['CargoId'],
                ':CargoJefeDirectoId' => $this->normalizeManagerCargoId($user['CargoJefeDirectoId'] ?? null),
                ':Nombres' => $user['Nombres'],
                ':Apellidos' => $user['Apellidos'],
                ':EmailCorporativo' => $user['EmailCorporativo'],
                ':Telefono' => $user['Telefono'] ?? null,
                ':Estado' => $user['Estado'] ?? 1,
            ]);

            $userId = (int)$this->db->lastInsertId();
            $this->replaceCompanies($userId, $companyIds);
            $this->db->commit();

            return $userId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $id, array $user): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                AgenciaId = :AgenciaId,
                CargoId = :CargoId,
                CargoJefeDirectoId = :CargoJefeDirectoId,
                Nombres = :Nombres,
                Apellidos = :Apellidos,
                EmailCorporativo = :EmailCorporativo,
                Telefono = :Telefono,
                Estado = :Estado
            WHERE Id = :Id
        ";

        $syncCompanies = array_key_exists('EmpresaId', $user) || array_key_exists('EmpresaIds', $user);
        $companyIds = $syncCompanies ? $this->getCompanyIds($user) : [];

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare($sql);
            $updated = $stmt->execute([
                ':Id' => $id,
                ':AgenciaId' => $user['AgenciaId'],
                ':CargoId' => $user['CargoId'],
                ':CargoJefeDirectoId' => $this->normalizeManagerCargoId($user['CargoJefeDirectoId'] ?? null),
                ':Nombres' => $user['Nombres'],
                ':Apellidos' => $user['Apellidos'],
                ':EmailCorporativo' => $user['EmailCorporativo'],
                ':Telefono' => $user['Telefono'] ?? null,
                ':Estado' => $user['Estado'] ?? 1,
            ]);

            if ($updated && $syncCompanies) {
                $this->replaceCompanies($id, $companyIds);
            }

            $this->db->commit();
            return $updated;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function getCompanyIds(array $user): array
    {
        $companyIds = $user['EmpresaIds'] ?? (isset($user['EmpresaId']) ? [$user['EmpresaId']] : []);
        if (!is_array($companyIds)) {
            throw new \InvalidArgumentException('EmpresaIds debe ser una lista de identificadores.');
        }

        $normalizedIds = [];
        foreach ($companyIds as $companyId) {
            $id = filter_var($companyId, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                throw new \InvalidArgumentException('Cada empresa debe tener un identificador válido.');
            }

            $normalizedIds[] = $id;
        }

        return array_values(array_unique($normalizedIds));
    }

    private function normalizeManagerCargoId(mixed $cargoId): ?int
    {
        if ($cargoId === null || $cargoId === '') {
            return null;
        }

        $cargoId = filter_var($cargoId, FILTER_VALIDATE_INT);
        if ($cargoId === false || $cargoId < 1) {
            throw new \InvalidArgumentException('Selecciona un cargo de jefe directo válido.');
        }

        $stmt = $this->db->prepare('SELECT Estado FROM gcp_cargos WHERE Id = :Id');
        $stmt->execute([':Id' => $cargoId]);
        if ((int) $stmt->fetchColumn() !== 1) {
            throw new \InvalidArgumentException('El cargo de jefe directo seleccionado no existe o está inactivo.');
        }

        return $cargoId;
    }

    private function replaceCompanies(int $userId, array $companyIds): void
    {
        $delete = $this->db->prepare('DELETE FROM gcp_usuariosempresas WHERE UsuarioId = :UsuarioId');
        $delete->execute([':UsuarioId' => $userId]);

        if ($companyIds === []) {
            return;
        }

        $insert = $this->db->prepare(
            'INSERT INTO gcp_usuariosempresas (UsuarioId, EmpresaId) VALUES (:UsuarioId, :EmpresaId)'
        );
        foreach ($companyIds as $companyId) {
            $insert->execute([
                ':UsuarioId' => $userId,
                ':EmpresaId' => $companyId,
            ]);
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

    public function getUserPermissions(int $userId): array
    {
        $sql = "
            SELECT DISTINCT p.Id, p.CodigoUnico, p.Modulo, p.Accion, p.AplicacionId, a.Nombre AS AplicacionNombre
            FROM gcp_usuarios u
            INNER JOIN gcp_rolespermisos rp ON rp.RolId = u.RolId
            INNER JOIN gcp_permisos p ON p.Id = rp.PermisoId
            LEFT JOIN gcp_aplicaciones a ON a.Id = p.AplicacionId
            WHERE u.Id = :userId AND p.Estado = 1
            ORDER BY a.OrdenMenu ASC, a.Nombre ASC, p.CodigoUnico ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
