<?php

namespace App\Repositories\Intranet;

use App\Repositories\BaseRepository;
use PDO;

class IntranetRepository extends BaseRepository
{
    public function getDirectorio(): array
    {
        $stmt = $this->db->query("
            SELECT id, first_name, last_name, address, phone, email, extension, area_agencia, cargo, empresa
            FROM directorio_gacela_conecta
            ORDER BY first_name ASC, last_name ASC, id ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateDirectorio(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE directorio_gacela_conecta
            SET first_name = :first_name,
                last_name = :last_name,
                address = :address,
                phone = :phone,
                email = :email,
                extension = :extension,
                area_agencia = :area_agencia,
                cargo = :cargo,
                empresa = :empresa
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':address' => $data['address'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':extension' => $data['extension'],
            ':area_agencia' => $data['area_agencia'],
            ':cargo' => $data['cargo'],
            ':empresa' => $data['empresa'],
        ]);
    }

    public function createDirectorio(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO directorio_gacela_conecta
                (first_name, last_name, address, phone, email, extension, area_agencia, cargo, empresa)
            VALUES
                (:first_name, :last_name, :address, :phone, :email, :extension, :area_agencia, :cargo, :empresa)
        ");
        $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':address' => $data['address'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':extension' => $data['extension'],
            ':area_agencia' => $data['area_agencia'],
            ':cargo' => $data['cargo'],
            ':empresa' => $data['empresa'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function deleteDirectorio(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM directorio_gacela_conecta WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function directorioExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM directorio_gacela_conecta WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function getEmpresas(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM gcp_empresas WHERE Estado = 1 ORDER BY Nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function userBelongsToCompany(int $userId, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM gcp_usuariosempresas ue
            INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId
            WHERE ue.UsuarioId = :userId AND ue.EmpresaId = :companyId AND e.Estado = 1
            LIMIT 1
        ");
        $stmt->execute([':userId' => $userId, ':companyId' => $companyId]);

        return (bool) $stmt->fetchColumn();
    }

    public function getAreas(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM GCP_Intranet_Areas WHERE Estado = 1 ORDER BY Nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategorias(): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT Categoria AS Id, Categoria AS Nombre
            FROM intranet_documentos
            WHERE Estado = 'activo'
            ORDER BY Categoria ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
