<?php

namespace App\Repositories\Intranet;

use App\Repositories\BaseRepository;
use PDO;

class EventoRepository extends BaseRepository
{
    protected string $table = 'intranet_eventos';

    public function getAll(): array
    {
        $sql = "
            {$this->eventSelect()}
            ORDER BY e.FechaInicio ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDateRange(string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            {$this->eventSelect()}
            WHERE e.FechaInicio >= :fechaInicio AND e.FechaInicio <= :fechaFin
            AND e.Estado != 'cancelado'
            ORDER BY e.FechaInicio ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fechaInicio' => $fechaInicio,
            ':fechaFin' => $fechaFin
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpcoming(int $limit = 10): array
    {
        $sql = "
            {$this->eventSelect()}
            WHERE e.FechaInicio >= NOW() AND e.Estado = 'confirmado'
            ORDER BY e.FechaInicio ASC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $evento): int
    {
        $sql = "
            INSERT INTO {$this->table} (Titulo, Descripcion, FechaInicio, FechaFin, Ubicacion, Tipo, Prioridad, TodoElDia, Recordatorio, Estado, OrganizadorId, Repetir, FechaFinRepeticion)
            VALUES (:Titulo, :Descripcion, :FechaInicio, :FechaFin, :Ubicacion, :Tipo, :Prioridad, :TodoElDia, :Recordatorio, :Estado, :OrganizadorId, :Repetir, :FechaFinRepeticion)
        ";

        $participantIds = $this->normalizeParticipantIds($evento['ParticipantesIds'] ?? []);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':Titulo' => $evento['Titulo'],
                ':Descripcion' => $evento['Descripcion'] ?? null,
                ':FechaInicio' => $evento['FechaInicio'],
                ':FechaFin' => $evento['FechaFin'],
                ':Ubicacion' => $evento['Ubicacion'] ?? null,
                ':Tipo' => $evento['Tipo'] ?? 'reunion',
                ':Prioridad' => $evento['Prioridad'] ?? 'media',
                ':TodoElDia' => $evento['TodoElDia'] ?? 0,
                ':Recordatorio' => $evento['Recordatorio'] ?? 30,
                ':Estado' => $evento['Estado'] ?? 'planeado',
                ':OrganizadorId' => $evento['OrganizadorId'],
                ':Repetir' => $evento['Repetir'] ?? 'no',
                ':FechaFinRepeticion' => $evento['FechaFinRepeticion'] ?? null,
            ]);

            $eventId = (int)$this->db->lastInsertId();
            $this->replaceParticipants($eventId, $participantIds);
            $this->db->commit();

            return $eventId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $evento): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                Titulo = :Titulo,
                Descripcion = :Descripcion,
                FechaInicio = :FechaInicio,
                FechaFin = :FechaFin,
                Ubicacion = :Ubicacion,
                Tipo = :Tipo,
                Prioridad = :Prioridad,
                TodoElDia = :TodoElDia,
                Recordatorio = :Recordatorio,
                Estado = :Estado,
                Repetir = :Repetir,
                FechaFinRepeticion = :FechaFinRepeticion
            WHERE Id = :Id
        ";

        $participantIds = $this->normalizeParticipantIds($evento['ParticipantesIds'] ?? []);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare($sql);
            $updated = $stmt->execute([
                ':Id' => $id,
                ':Titulo' => $evento['Titulo'],
                ':Descripcion' => $evento['Descripcion'] ?? null,
                ':FechaInicio' => $evento['FechaInicio'],
                ':FechaFin' => $evento['FechaFin'],
                ':Ubicacion' => $evento['Ubicacion'] ?? null,
                ':Tipo' => $evento['Tipo'] ?? 'reunion',
                ':Prioridad' => $evento['Prioridad'] ?? 'media',
                ':TodoElDia' => $evento['TodoElDia'] ?? 0,
                ':Recordatorio' => $evento['Recordatorio'] ?? 30,
                ':Estado' => $evento['Estado'] ?? 'planeado',
                ':Repetir' => $evento['Repetir'] ?? 'no',
                ':FechaFinRepeticion' => $evento['FechaFinRepeticion'] ?? null,
            ]);

            if ($updated) {
                $this->replaceParticipants($id, $participantIds);
            }

            $this->db->commit();
            return $updated;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
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

    private function eventSelect(): string
    {
        return "
            SELECT e.*,
                   u.Nombres AS OrganizadorNombre,
                   u.Apellidos AS OrganizadorApellidos,
                   COALESCE(
                       (
                           SELECT CONCAT('[', GROUP_CONCAT(ep.UsuarioId ORDER BY ep.UsuarioId SEPARATOR ','), ']')
                           FROM intranet_evento_participantes ep
                           WHERE ep.EventoId = e.Id
                       ),
                       '[]'
                   ) AS ParticipantesIds
            FROM {$this->table} e
            LEFT JOIN gcp_usuarios u ON u.Id = e.OrganizadorId
        ";
    }

    private function normalizeParticipantIds($participantIds): array
    {
        if (!is_array($participantIds)) {
            throw new \InvalidArgumentException('ParticipantesIds debe ser una lista de identificadores.');
        }

        $normalizedIds = [];
        foreach ($participantIds as $participantId) {
            $id = filter_var($participantId, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                throw new \InvalidArgumentException('Cada participante debe tener un identificador válido.');
            }

            $normalizedIds[] = $id;
        }

        return array_values(array_unique($normalizedIds));
    }

    private function replaceParticipants(int $eventId, array $participantIds): void
    {
        $delete = $this->db->prepare(
            'DELETE FROM intranet_evento_participantes WHERE EventoId = :EventoId'
        );
        $delete->execute([':EventoId' => $eventId]);

        if ($participantIds === []) {
            return;
        }

        $insert = $this->db->prepare(
            'INSERT INTO intranet_evento_participantes (EventoId, UsuarioId) VALUES (:EventoId, :UsuarioId)'
        );
        foreach ($participantIds as $participantId) {
            $insert->execute([
                ':EventoId' => $eventId,
                ':UsuarioId' => $participantId,
            ]);
        }
    }
}