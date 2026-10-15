<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected PDO $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getDb(): PDO
    {
        return $this->db;
    }

    public function findById(int $id): ?array
    {
        if ($this->table === '') {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE Id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        return $record ?: null;
    }

    public function changeStatus(int $id, bool|string $status): bool
    {
        if ($this->table === '') {
            return false;
        }

        $sql = "UPDATE {$this->table} SET Estado = :estado WHERE Id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':estado' => (int) $status,
            ':id' => $id,
        ]);
    }
}
