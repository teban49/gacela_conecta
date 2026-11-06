<?php

namespace App\Repositories;

use PDO;

/** Taquilleros por los que un Agencista puede registrar solicitudes y dotaciones. */
class BeneficiaryRepository extends BaseRepository
{
    private function isAgencista(int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM gcp_usuarios u
            INNER JOIN gcp_cargos c ON c.Id = u.CargoId
            WHERE u.Id = :Id AND u.Estado = 1 AND c.Nombre LIKE 'Agencista%'
        ");
        $stmt->execute([':Id' => $userId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function getTaquilleros(int $agencistaId): array
    {
        if (!$this->isAgencista($agencistaId)) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT t.Id, t.Nombres, t.Apellidos, t.Cedula, a.Nombre AS Agencia,
                   (SELECT GROUP_CONCAT(ue.EmpresaId) FROM gcp_usuariosempresas ue WHERE ue.UsuarioId = t.Id) AS EmpresasIds
            FROM gcp_usuarios t
            INNER JOIN gcp_cargos c ON c.Id = t.CargoId AND c.Nombre LIKE 'Taquill%'
            INNER JOIN gcp_agencias a ON a.Id = t.AgenciaId
            INNER JOIN gcp_usuarios ag ON ag.Id = :Agencista
            WHERE t.Estado = 1
              AND EXISTS (
                  SELECT 1 FROM gcp_usuariosempresas te
                  INNER JOIN gcp_usuariosempresas ae ON ae.EmpresaId = te.EmpresaId AND ae.UsuarioId = ag.Id
                  WHERE te.UsuarioId = t.Id
              )
            ORDER BY (t.AgenciaId = ag.AgenciaId) DESC, t.Nombres, t.Apellidos
        ");
        $stmt->execute([':Agencista' => $agencistaId]);

        return array_map(static function (array $row): array {
            $row['EmpresasIds'] = array_values(array_filter(array_map('intval', explode(',', (string) $row['EmpresasIds']))));
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function canRequestFor(int $agencistaId, int $beneficiaryId): bool
    {
        foreach ($this->getTaquilleros($agencistaId) as $taquillero) {
            if ((int) $taquillero['Id'] === $beneficiaryId) {
                return true;
            }
        }

        return false;
    }
}
