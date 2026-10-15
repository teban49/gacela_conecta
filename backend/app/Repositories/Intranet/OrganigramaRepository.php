<?php

namespace App\Repositories\Intranet;

use App\Repositories\BaseRepository;
use PDO;

class OrganigramaRepository extends BaseRepository
{
    public function getOrganigrama(): array
    {
        $sql = "
            SELECT o.*,
                   CONCAT(u.Nombres, ' ', u.Apellidos) AS UsuarioNombre,
                   u.EmailCorporativo, u.Telefono,
                   CONCAT(j.Nombres, ' ', j.Apellidos) AS JefeNombre
            FROM GCP_Intranet_Organigrama o
            INNER JOIN gcp_usuarios u ON u.Id = o.UsuarioId
            LEFT JOIN gcp_usuarios j ON j.Id = o.JefeInmediatoUsuarioId
            WHERE o.Estado = 1
            ORDER BY o.NivelJerarquico ASC, u.Nombres ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upsertNodo(array $data): bool
    {
        $sql = "
            INSERT INTO GCP_Intranet_Organigrama (UsuarioId, CargoNombre, JefeInmediatoUsuarioId, Dependencia, NivelJerarquico, Estado)
            VALUES (:UsuarioId, :CargoNombre, :JefeInmediatoUsuarioId, :Dependencia, :NivelJerarquico, :Estado)
            ON DUPLICATE KEY UPDATE
                CargoNombre = VALUES(CargoNombre),
                JefeInmediatoUsuarioId = VALUES(JefeInmediatoUsuarioId),
                Dependencia = VALUES(Dependencia),
                NivelJerarquico = VALUES(NivelJerarquico),
                Estado = VALUES(Estado)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':UsuarioId' => $data['UsuarioId'],
            ':CargoNombre' => $data['CargoNombre'],
            ':JefeInmediatoUsuarioId' => $data['JefeInmediatoUsuarioId'] ?? null,
            ':Dependencia' => $data['Dependencia'] ?? null,
            ':NivelJerarquico' => $data['NivelJerarquico'] ?? 1,
            ':Estado' => isset($data['Estado']) ? ($data['Estado'] ? 1 : 0) : 1,
        ]);
    }
}
