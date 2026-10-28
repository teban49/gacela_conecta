<?php

namespace App\Repositories\Intranet;

use App\Repositories\BaseRepository;
use PDO;

class OrganigramaPuestoRepository extends BaseRepository
{
    protected string $table = 'GCP_Intranet_Organigrama_Puestos';

    public function getActive(): array
    {
        $stmt = $this->db->query("
            SELECT Id, EmpresaId, Nivel, Area, Fila, Nombre, Cargo, Orden
            FROM {$this->table}
            WHERE Estado = 1
            ORDER BY Fila ASC, Orden ASC, Id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function companyExists(int $companyId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM GCP_Intranet_Empresas WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $companyId]);
        return (bool) $stmt->fetchColumn();
    }

    public function getCompanies(): array
    {
        return $this->db->query('SELECT Id, Nombre, Codigo FROM GCP_Intranet_Empresas WHERE Estado = 1 ORDER BY Id ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompaniesForUser(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT c.Id, c.Nombre, c.Codigo
            FROM GCP_Intranet_Empresas c
            INNER JOIN gcp_usuariosempresas ue ON ue.EmpresaId = c.Id
            WHERE c.Estado = 1 AND ue.UsuarioId = :userId
            ORDER BY c.Id ASC
        ');
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $fila = $data['Fila'];
        if ($fila === null) {
            $stmt = $this->db->prepare("
                SELECT COALESCE(MAX(Fila), 0) + 1 FROM {$this->table}
                WHERE Estado = 1 AND Nivel = :Nivel AND EmpresaId <=> :EmpresaId AND Area <=> :Area
            ");
            $stmt->execute([':Nivel' => $data['Nivel'], ':EmpresaId' => $data['EmpresaId'], ':Area' => $data['Area']]);
            $fila = (int) $stmt->fetchColumn();
        }

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (EmpresaId, Nivel, Area, Fila, Nombre, Cargo, Orden)
            VALUES (:EmpresaId, :Nivel, :Area, :Fila, :Nombre, :Cargo,
                (SELECT COALESCE(MAX(o.Orden), 0) + 1 FROM {$this->table} o
                 WHERE o.Nivel = :Nivel2 AND o.EmpresaId <=> :EmpresaId2 AND o.Area <=> :Area2))
        ");
        $stmt->execute([
            ':EmpresaId' => $data['EmpresaId'],
            ':Nivel' => $data['Nivel'],
            ':Area' => $data['Area'],
            ':Fila' => $fila,
            ':Nombre' => $data['Nombre'],
            ':Cargo' => $data['Cargo'],
            ':Nivel2' => $data['Nivel'],
            ':EmpresaId2' => $data['EmpresaId'],
            ':Area2' => $data['Area'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nombre, ?string $cargo): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET Nombre = :Nombre, Cargo = :Cargo WHERE Id = :Id AND Estado = 1");
        $stmt->execute([':Nombre' => $nombre, ':Cargo' => $cargo, ':Id' => $id]);
        return $stmt->rowCount() > 0 || $this->findById($id) !== null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET Estado = 0 WHERE Id = :Id");
        $stmt->execute([':Id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
