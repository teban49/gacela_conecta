<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AuthToken
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): int
    {
        $defaultType = defined('TOKEN_TYPE_MAGIC_LINK') ? TOKEN_TYPE_MAGIC_LINK : 'MAGIC_LINK';

        $stmt = $this->db->prepare("
            INSERT INTO GCP_AuthTokens 
            (UsuarioId, TokenHash, Tipo, FechaExpiracion, IpAddress, UserAgent)
            VALUES 
            (:UsuarioId, :TokenHash, :Tipo, :FechaExpiracion, :IpAddress, :UserAgent)
        ");
        
        $stmt->execute([
            'UsuarioId'       => $data['UsuarioId'],
            'TokenHash'       => $data['TokenHash'],
            'Tipo'            => $data['Tipo'] ?? $defaultType,
            'FechaExpiracion' => $data['FechaExpiracion'],
            'IpAddress'       => $data['IpAddress'] ?? null,
            'UserAgent'       => $data['UserAgent'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByTokenHash(string $tokenHash): ?array
    {
        $unusedState = defined('TOKEN_STATE_UNUSED') ? TOKEN_STATE_UNUSED : 0;

        $stmt = $this->db->prepare("
            SELECT * 
            FROM GCP_AuthTokens
            WHERE TokenHash = :TokenHash
            AND Usado = :Usado
            AND FechaExpiracion > NOW()
        ");
        
        $stmt->execute([
            'TokenHash' => $tokenHash,
            'Usado'     => $unusedState
        ]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public function markAsUsed(int $tokenId): bool
    {
        $usedState = defined('TOKEN_STATE_USED') ? TOKEN_STATE_USED : 1;

        $stmt = $this->db->prepare("
            UPDATE GCP_AuthTokens 
            SET Usado = :Usado, FechaUso = NOW() 
            WHERE Id = :Id
        ");
        
        return $stmt->execute([
            'Usado' => $usedState,
            'Id'    => $tokenId
        ]);
    }

    public function cleanupExpired(): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM GCP_AuthTokens 
            WHERE FechaExpiracion < NOW()
        ");
        
        $stmt->execute();
        
        return $stmt->rowCount();
    }
}