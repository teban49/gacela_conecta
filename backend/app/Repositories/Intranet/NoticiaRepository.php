<?php

namespace App\Repositories\Intranet;

use App\Repositories\BaseRepository;
use PDO;

class NoticiaRepository extends BaseRepository
{
    protected string $table = 'intranet_noticias';

    public function getAll(): array
    {
        $sql = "
            SELECT n.*, u.Nombres AS AutorNombre, u.Apellidos AS AutorApellidos,
                   (SELECT COUNT(*) FROM intranet_noticias_vistas WHERE NoticiaId = n.Id) AS Vistas
            FROM {$this->table} n
            LEFT JOIN gcp_usuarios u ON u.Id = n.AutorId
            ORDER BY n.FechaPublicacion DESC, n.Prioridad DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPublished(): array
    {
        $sql = "
            SELECT n.*, u.Nombres AS AutorNombre, u.Apellidos AS AutorApellidos,
                   (SELECT COUNT(*) FROM intranet_noticias_vistas WHERE NoticiaId = n.Id) AS Vistas
            FROM {$this->table} n
            LEFT JOIN gcp_usuarios u ON u.Id = n.AutorId
            WHERE n.Estado = 'publicada'
            AND (n.FechaExpiracion IS NULL OR n.FechaExpiracion > NOW())
            ORDER BY n.FechaPublicacion DESC, n.Prioridad DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCategory(string $categoria): array
    {
        $sql = "
            SELECT n.*, u.Nombres AS AutorNombre, u.Apellidos AS AutorApellidos,
                   (SELECT COUNT(*) FROM intranet_noticias_vistas WHERE NoticiaId = n.Id) AS Vistas
            FROM {$this->table} n
            LEFT JOIN gcp_usuarios u ON u.Id = n.AutorId
            WHERE n.Categoria = :categoria AND n.Estado = 'publicada'
            ORDER BY n.FechaPublicacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':categoria' => $categoria]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsViewed(int $noticiaId, int $usuarioId): bool
    {
        $sql = "
            INSERT INTO intranet_noticias_vistas (NoticiaId, UsuarioId, FechaVisualizacion)
            VALUES (:noticiaId, :usuarioId, NOW())
            ON DUPLICATE KEY UPDATE FechaVisualizacion = NOW()
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':noticiaId' => $noticiaId,
            ':usuarioId' => $usuarioId
        ]);
    }

    public function create(array $noticia): int
    {
        $sql = "
            INSERT INTO {$this->table} (Titulo, Contenido, Resumen, Categoria, Prioridad, Imagen, Estado, AutorId, FechaPublicacion, FechaExpiracion, VistaPorDefecto)
            VALUES (:Titulo, :Contenido, :Resumen, :Categoria, :Prioridad, :Imagen, :Estado, :AutorId, :FechaPublicacion, :FechaExpiracion, :VistaPorDefecto)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':Titulo' => $noticia['Titulo'],
            ':Contenido' => $noticia['Contenido'],
            ':Resumen' => $noticia['Resumen'] ?? null,
            ':Categoria' => $noticia['Categoria'] ?? 'general',
            ':Prioridad' => $noticia['Prioridad'] ?? 'media',
            ':Imagen' => $noticia['Imagen'] ?? null,
            ':Estado' => $noticia['Estado'] ?? 'borrador',
            ':AutorId' => $noticia['AutorId'],
            ':FechaPublicacion' => $noticia['FechaPublicacion'] ?? null,
            ':FechaExpiracion' => $noticia['FechaExpiracion'] ?? null,
            ':VistaPorDefecto' => $noticia['VistaPorDefecto'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $noticia): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                Titulo = :Titulo,
                Contenido = :Contenido,
                Resumen = :Resumen,
                Categoria = :Categoria,
                Prioridad = :Prioridad,
                Imagen = :Imagen,
                Estado = :Estado,
                FechaPublicacion = :FechaPublicacion,
                FechaExpiracion = :FechaExpiracion,
                VistaPorDefecto = :VistaPorDefecto
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':Titulo' => $noticia['Titulo'],
            ':Contenido' => $noticia['Contenido'],
            ':Resumen' => $noticia['Resumen'] ?? null,
            ':Categoria' => $noticia['Categoria'] ?? 'general',
            ':Prioridad' => $noticia['Prioridad'] ?? 'media',
            ':Imagen' => $noticia['Imagen'] ?? null,
            ':Estado' => $noticia['Estado'] ?? 'borrador',
            ':FechaPublicacion' => $noticia['FechaPublicacion'] ?? null,
            ':FechaExpiracion' => $noticia['FechaExpiracion'] ?? null,
            ':VistaPorDefecto' => $noticia['VistaPorDefecto'] ?? 0,
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