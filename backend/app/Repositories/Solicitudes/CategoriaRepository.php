<?php

namespace App\Repositories\Solicitudes;

use App\Repositories\BaseRepository;
use PDO;

class CategoriaRepository extends BaseRepository
{
    protected string $table = 'gestion_tickets_categorias';

    public function getAll(): array
    {
        $sql = "
            SELECT c.*, u.Nombres AS AsignadoNombre, u.Apellidos AS AsignadoApellidos
            FROM {$this->table} c
            LEFT JOIN gcp_usuarios u ON u.Id = c.AsignadoA
            ORDER BY c.Prioridad DESC, c.Nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActive(): array
    {
        $sql = "
            SELECT c.*, u.Nombres AS AsignadoNombre, u.Apellidos AS AsignadoApellidos
            FROM {$this->table} c
            LEFT JOIN gcp_usuarios u ON u.Id = c.AsignadoA
            WHERE c.Estado = 1
            ORDER BY c.Prioridad DESC, c.Nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $categoria): int
    {
        $sql = "
            INSERT INTO {$this->table} (Nombre, Descripcion, Codigo, Icono, Color, Prioridad, Estado, AsignadoA, TiempoRespuestaHoras)
            VALUES (:Nombre, :Descripcion, :Codigo, :Icono, :Color, :Prioridad, :Estado, :AsignadoA, :TiempoRespuestaHoras)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':Nombre' => $categoria['Nombre'],
            ':Descripcion' => $categoria['Descripcion'] ?? null,
            ':Codigo' => $categoria['Codigo'] ?? null,
            ':Icono' => $categoria['Icono'] ?? null,
            ':Color' => $categoria['Color'] ?? '#6366f1',
            ':Prioridad' => $categoria['Prioridad'] ?? 0,
            ':Estado' => $categoria['Estado'] ?? 1,
            ':AsignadoA' => $categoria['AsignadoA'] ?? null,
            ':TiempoRespuestaHoras' => $categoria['TiempoRespuestaHoras'] ?? 24,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $categoria): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                Nombre = :Nombre,
                Descripcion = :Descripcion,
                Codigo = :Codigo,
                Icono = :Icono,
                Color = :Color,
                Prioridad = :Prioridad,
                Estado = :Estado,
                AsignadoA = :AsignadoA,
                TiempoRespuestaHoras = :TiempoRespuestaHoras
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':Nombre' => $categoria['Nombre'],
            ':Descripcion' => $categoria['Descripcion'] ?? null,
            ':Codigo' => $categoria['Codigo'] ?? null,
            ':Icono' => $categoria['Icono'] ?? null,
            ':Color' => $categoria['Color'] ?? '#6366f1',
            ':Prioridad' => $categoria['Prioridad'] ?? 0,
            ':Estado' => $categoria['Estado'] ?? 1,
            ':AsignadoA' => $categoria['AsignadoA'] ?? null,
            ':TiempoRespuestaHoras' => $categoria['TiempoRespuestaHoras'] ?? 24,
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