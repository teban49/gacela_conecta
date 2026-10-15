<?php

namespace App\Repositories\Intranet;

use App\Repositories\BaseRepository;
use PDO;

class DocumentoRepository extends BaseRepository
{
    protected string $table = 'intranet_documentos';

    public function getAll(): array
    {
        $sql = "
            SELECT d.*, u.Nombres AS AutorNombre, u.Apellidos AS AutorApellidos
            FROM {$this->table} d
            LEFT JOIN gcp_usuarios u ON u.Id = d.AutorId
            ORDER BY d.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCategory(string $categoria): array
    {
        $sql = "
            SELECT d.*, u.Nombres AS AutorNombre, u.Apellidos AS AutorApellidos
            FROM {$this->table} d
            LEFT JOIN gcp_usuarios u ON u.Id = d.AutorId
            WHERE d.Categoria = :categoria AND d.Estado = 'activo'
            ORDER BY d.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':categoria' => $categoria]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function incrementDownloads(int $id): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET Descargas = Descargas + 1
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':Id' => $id]);
    }

    public function create(array $documento): int
    {
        $sql = "
            INSERT INTO {$this->table} (Nombre, Descripcion, RutaArchivo, Tamaño, TipoMime, Categoria, Extension, Version, AutorId, Descargable, RequierePermiso, PermisoRequerido, Estado)
            VALUES (:Nombre, :Descripcion, :RutaArchivo, :Tamaño, :TipoMime, :Categoria, :Extension, :Version, :AutorId, :Descargable, :RequierePermiso, :PermisoRequerido, :Estado)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':Nombre' => $documento['Nombre'],
            ':Descripcion' => $documento['Descripcion'] ?? null,
            ':RutaArchivo' => $documento['RutaArchivo'],
            ':Tamaño' => $documento['Tamaño'] ?? 0,
            ':TipoMime' => $documento['TipoMime'] ?? null,
            ':Categoria' => $documento['Categoria'] ?? 'general',
            ':Extension' => $documento['Extension'] ?? null,
            ':Version' => $documento['Version'] ?? '1.0',
            ':AutorId' => $documento['AutorId'],
            ':Descargable' => $documento['Descargable'] ?? 1,
            ':RequierePermiso' => $documento['RequierePermiso'] ?? 0,
            ':PermisoRequerido' => $documento['PermisoRequerido'] ?? null,
            ':Estado' => $documento['Estado'] ?? 'activo',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $documento): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                Nombre = :Nombre,
                Descripcion = :Descripcion,
                RutaArchivo = :RutaArchivo,
                Tamaño = :Tamaño,
                TipoMime = :TipoMime,
                Categoria = :Categoria,
                Extension = :Extension,
                Version = :Version,
                Descargable = :Descargable,
                RequierePermiso = :RequierePermiso,
                PermisoRequerido = :PermisoRequerido,
                Estado = :Estado
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':Nombre' => $documento['Nombre'],
            ':Descripcion' => $documento['Descripcion'] ?? null,
            ':RutaArchivo' => $documento['RutaArchivo'],
            ':Tamaño' => $documento['Tamaño'] ?? 0,
            ':TipoMime' => $documento['TipoMime'] ?? null,
            ':Categoria' => $documento['Categoria'] ?? 'general',
            ':Extension' => $documento['Extension'] ?? null,
            ':Version' => $documento['Version'] ?? '1.0',
            ':Descargable' => $documento['Descargable'] ?? 1,
            ':RequierePermiso' => $documento['RequierePermiso'] ?? 0,
            ':PermisoRequerido' => $documento['PermisoRequerido'] ?? null,
            ':Estado' => $documento['Estado'] ?? 'activo',
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
            ':Estado' => $status,
        ]);
    }
}