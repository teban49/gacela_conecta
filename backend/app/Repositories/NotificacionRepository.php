<?php

namespace App\Repositories;

use PDO;

class NotificacionRepository extends BaseRepository
{
    protected string $table = 'gcp_notificaciones';

    public function create(int $userId, string $type, string $title, ?string $message, ?string $link): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO gcp_notificaciones (UsuarioId, Tipo, Titulo, Mensaje, Enlace)
            VALUES (:UsuarioId, :Tipo, :Titulo, :Mensaje, :Enlace)
        ");
        $stmt->execute([
            ':UsuarioId' => $userId,
            ':Tipo' => $type,
            ':Titulo' => mb_substr($title, 0, 200),
            ':Mensaje' => $message !== null ? mb_substr($message, 0, 500) : null,
            ':Enlace' => $link,
        ]);
    }

    /** Notifica a todos los usuarios activos cuyo cargo coincide con alguno de los nombres indicados. */
    public function notifyCargos(array $cargoNames, string $type, string $title, ?string $message, ?string $link): void
    {
        if ($cargoNames === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($cargoNames), '?'));
        $stmt = $this->db->prepare("
            SELECT u.Id FROM gcp_usuarios u
            INNER JOIN gcp_cargos c ON c.Id = u.CargoId
            WHERE u.Estado = 1 AND c.Nombre IN ($placeholders)
        ");
        $stmt->execute(array_values($cargoNames));
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
            $this->create((int) $userId, $type, $title, $message, $link);
        }
    }

    public function getRecent(int $userId, int $limit = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT Id, Tipo, Titulo, Mensaje, Enlace, Leida, FechaCreacion
            FROM gcp_notificaciones
            WHERE UsuarioId = :UsuarioId
              AND (Leida = 0 OR FechaCreacion >= (NOW() - INTERVAL 14 DAY))
            ORDER BY Leida ASC, FechaCreacion DESC, Id DESC
            LIMIT $limit
        ");
        $stmt->execute([':UsuarioId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markRead(int $userId, int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE gcp_notificaciones SET Leida = 1 WHERE Id = :Id AND UsuarioId = :UsuarioId');
        return $stmt->execute([':Id' => $id, ':UsuarioId' => $userId]);
    }

    public function markAllRead(int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE gcp_notificaciones SET Leida = 1 WHERE UsuarioId = :UsuarioId AND Leida = 0');
        return $stmt->execute([':UsuarioId' => $userId]);
    }

    /** Incidencias que requieren atención del usuario: confirmar cierre o atender una asignada. */
    public function getTicketsNeedingAttention(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT Id, Ticket, Titulo, Estado, SolicitanteId, AsignadoA, FechaActualizacion
            FROM gestion_tickets
            WHERE (SolicitanteId = :requesterId AND Estado = 'pendiente')
               OR (AsignadoA = :assigneeId AND Estado = 'nueva')
            ORDER BY FechaActualizacion DESC
            LIMIT 50
        ");
        $stmt->execute([':requesterId' => $userId, ':assigneeId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
