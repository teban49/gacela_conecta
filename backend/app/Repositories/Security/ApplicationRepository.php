<?php

namespace App\Repositories\Security;

use App\Repositories\BaseRepository;
use PDO;

class ApplicationRepository extends BaseRepository
{
    protected string $table = 'gcp_aplicaciones';

    public function getAll(): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            ORDER BY OrdenMenu ASC, Nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        return $this->findById($id);
    }

    public function create(array $application): int
    {
        $sql = "
            INSERT INTO {$this->table} (Nombre, Codigo, Ruta, Color, Icono, OrdenMenu, Visible, Estado)
            VALUES (:Nombre, :Codigo, :Ruta, :Color, :Icono, :OrdenMenu, :Visible, :Estado)
        ";

        $visible = isset($application['Visible']) ? (int)$application['Visible'] : 1;
        $estado = isset($application['Estado']) ? ($application['Estado'] ? 1 : 0) : 1;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':Nombre' => $application['Nombre'],
            ':Codigo' => $application['Codigo'],
            ':Ruta' => $application['Ruta'] ?? null,
            ':Color' => $application['Color'] ?? '#6366f1',
            ':Icono' => $application['Icono'] ?? null,
            ':OrdenMenu' => $application['OrdenMenu'] ?? 0,
            ':Visible' => $visible,
            ':Estado' => $estado,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $application): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                Nombre = :Nombre,
                Codigo = :Codigo,
                Ruta = :Ruta,
                Color = :Color,
                Icono = :Icono,
                OrdenMenu = :OrdenMenu,
                Visible = :Visible,
                Estado = :Estado
            WHERE Id = :Id
        ";

        $visible = isset($application['Visible']) ? (int)$application['Visible'] : 1;
        $estado = isset($application['Estado']) ? ($application['Estado'] ? 1 : 0) : 1;

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':Nombre' => $application['Nombre'],
            ':Codigo' => $application['Codigo'],
            ':Ruta' => $application['Ruta'] ?? null,
            ':Color' => $application['Color'] ?? '#6366f1',
            ':Icono' => $application['Icono'] ?? null,
            ':OrdenMenu' => $application['OrdenMenu'] ?? 0,
            ':Visible' => $visible,
            ':Estado' => $estado,
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

    public function changeVisibility(int $id, bool $visible): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET Visible = :Visible
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':Visible' => $visible ? 1 : 0,
        ]);
    }
}
