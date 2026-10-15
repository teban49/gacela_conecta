<?php

namespace App\Repositories\Intranet;

use App\Repositories\BaseRepository;
use PDO;

class DocumentRepository extends BaseRepository
{
    protected string $table = 'GCP_Intranet_Documentos';

    public function getDocumentos(?int $empresaId = null, ?int $areaId = null, ?int $categoriaId = null, ?string $search = null): array
    {
        $sql = "
            SELECT d.*, e.Nombre AS EmpresaNombre, a.Nombre AS AreaNombre, c.Nombre AS CategoriaNombre,
                   CONCAT(u.Nombres, ' ', u.Apellidos) AS AutorNombre
            FROM GCP_Intranet_Documentos d
            INNER JOIN GCP_Intranet_Empresas e ON e.Id = d.EmpresaId
            INNER JOIN GCP_Intranet_Areas a ON a.Id = d.AreaId
            INNER JOIN GCP_Intranet_Categorias c ON c.Id = d.CategoriaId
            LEFT JOIN gcp_usuarios u ON u.Id = d.CreadoPorUsuarioId
            WHERE d.Estado = 1
        ";

        $params = [];

        if ($empresaId) {
            $sql .= " AND d.EmpresaId = :empresaId";
            $params[':empresaId'] = $empresaId;
        }

        if ($areaId) {
            $sql .= " AND d.AreaId = :areaId";
            $params[':areaId'] = $areaId;
        }

        if ($categoriaId) {
            $sql .= " AND d.CategoriaId = :categoriaId";
            $params[':categoriaId'] = $categoriaId;
        }

        if ($search) {
            $sql .= " AND (d.Titulo LIKE :search OR d.CodigoDocumento LIKE :search OR d.Descripcion LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY d.FechaCreacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO GCP_Intranet_Documentos (EmpresaId, AreaId, CategoriaId, Titulo, CodigoDocumento, Version, RutaArchivo, TipoArchivo, TamanoBytes, Descripcion, CreadoPorUsuarioId, Estado)
            VALUES (:EmpresaId, :AreaId, :CategoriaId, :Titulo, :CodigoDocumento, :Version, :RutaArchivo, :TipoArchivo, :TamanoBytes, :Descripcion, :CreadoPorUsuarioId, :Estado)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':EmpresaId' => $data['EmpresaId'],
            ':AreaId' => $data['AreaId'],
            ':CategoriaId' => $data['CategoriaId'],
            ':Titulo' => $data['Titulo'],
            ':CodigoDocumento' => $data['CodigoDocumento'] ?? null,
            ':Version' => $data['Version'] ?? '1.0',
            ':RutaArchivo' => $data['RutaArchivo'],
            ':TipoArchivo' => $data['TipoArchivo'] ?? 'pdf',
            ':TamanoBytes' => $data['TamanoBytes'] ?? 0,
            ':Descripcion' => $data['Descripcion'] ?? null,
            ':CreadoPorUsuarioId' => $data['CreadoPorUsuarioId'] ?? 1,
            ':Estado' => isset($data['Estado']) ? ($data['Estado'] ? 1 : 0) : 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateVersion(int $id, string $nuevaVersion, string $nuevaRuta): bool
    {
        $sql = "
            UPDATE GCP_Intranet_Documentos
            SET Version = :Version, RutaArchivo = :RutaArchivo, FechaActualizacion = CURRENT_TIMESTAMP
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':Id' => $id,
            ':Version' => $nuevaVersion,
            ':RutaArchivo' => $nuevaRuta,
        ]);
    }
}
