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

    public function getAreas(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM gcp_areas WHERE Estado = 1 ORDER BY Nombre ASC");
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

    public function createNoticia(array $data): int
    {
        $sql = "INSERT INTO GCP_Intranet_Noticias (Titulo, Resumen, Contenido, ImagenUrl, Destacado, CreadoPorUsuarioId)
                VALUES (:Titulo, :Resumen, :Contenido, :ImagenUrl, :Destacado, :CreadoPorUsuarioId)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':Titulo' => $data['Titulo'],
            ':Resumen' => $data['Resumen'] ?? null,
            ':Contenido' => $data['Contenido'],
            ':ImagenUrl' => $data['ImagenUrl'] ?? null,
            ':Destacado' => isset($data['Destacado']) ? ($data['Destacado'] ? 1 : 0) : 0,
            ':CreadoPorUsuarioId' => $data['CreadoPorUsuarioId'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getManuales(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM GCP_Intranet_Manuales WHERE Estado = 1 ORDER BY Titulo ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPildoras(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM GCP_Intranet_Pildoras WHERE Estado = 1 ORDER BY Id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLinks(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM GCP_Intranet_Links WHERE Estado = 1 ORDER BY Orden ASC, Titulo ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
