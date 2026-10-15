<?php

namespace App\Repositories\Solicitudes;

use App\Repositories\BaseRepository;
use PDO;

class SolicitudLaboralRepository extends BaseRepository
{
    public function getTypes(): array
    {
        $stmt = $this->db->query(
            'SELECT Id, Nombre, Codigo, RequiereAdjunto FROM gcp_solicitudtipos WHERE Estado = 1 ORDER BY Id'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getType(int $typeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT Id, Nombre, Codigo, RequiereAdjunto FROM gcp_solicitudtipos WHERE Id = :Id AND Estado = 1'
        );
        $stmt->execute([':Id' => $typeId]);
        $type = $stmt->fetch(PDO::FETCH_ASSOC);

        return $type ?: null;
    }

    public function getDirectManagerCargoId(int $userId): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT CargoJefeDirectoId FROM gcp_usuarios WHERE Id = :Id AND Estado = 1'
        );
        $stmt->execute([':Id' => $userId]);
        $cargoId = $stmt->fetchColumn();

        return $cargoId === false || $cargoId === null ? null : (int) $cargoId;
    }

    public function getMine(int $userId): array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE s.UsuarioId = :userId ORDER BY s.FechaCreacion DESC');
        $stmt->execute([':userId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPending(int $userId, bool $isHr, bool $isManagement): array
    {
        $stmt = $this->db->prepare(
            $this->selectSql() . "
            WHERE (s.NivelActual = 1 AND s.UsuarioId <> :requesterId AND s.AprobadorCargoId = (
                    SELECT u.CargoId FROM gcp_usuarios u WHERE u.Id = :userId AND u.Estado = 1
                ) AND s.EstadoSolicitud = 'Pendiente_Jefe')
               OR (s.UsuarioId <> :hrRequesterId AND s.NivelActual = 2 AND :isHr = 1 AND s.EstadoSolicitud = 'Pendiente_RRHH')
               OR (s.UsuarioId <> :managementRequesterId AND s.NivelActual = 3 AND :isManagement = 1 AND s.EstadoSolicitud = 'Pendiente_Gerencia')
            ORDER BY s.FechaCreacion ASC"
        );
        $stmt->execute([
            ':userId' => $userId,
            ':requesterId' => $userId,
            ':hrRequesterId' => $userId,
            ':managementRequesterId' => $userId,
            ':isHr' => $isHr ? 1 : 0,
            ':isManagement' => $isManagement ? 1 : 0,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE s.Id = :Id');
        $stmt->execute([':Id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            return null;
        }

        $history = $this->db->prepare("
            SELECT h.Id, h.UsuarioId, h.Accion, h.Comentario, h.Nivel, h.Fecha,
                   u.Nombres, u.Apellidos
            FROM gcp_solicitudhistorial h
            INNER JOIN gcp_usuarios u ON u.Id = h.UsuarioId
            WHERE h.SolicitudId = :SolicitudId
            ORDER BY h.Fecha ASC, h.Id ASC
        ");
        $history->execute([':SolicitudId' => $id]);
        $request['Historial'] = $history->fetchAll(PDO::FETCH_ASSOC);

        $attachments = $this->db->prepare("
            SELECT Id, NombreArchivo, FechaSubida
            FROM gcp_solicitudadjuntos
            WHERE SolicitudId = :SolicitudId
            ORDER BY Id ASC
        ");
        $attachments->execute([':SolicitudId' => $id]);
        $request['Adjuntos'] = $attachments->fetchAll(PDO::FETCH_ASSOC);

        return $request;
    }

    public function canView(int $requestId, int $userId, bool $isHr, bool $isManagement): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM gcp_solicitudes s
            WHERE s.Id = :Id
              AND (
                    s.UsuarioId = :requesterId
                 OR (
                    s.UsuarioId <> :cargoRequesterId
                    AND s.NivelActual = 1
                    AND s.EstadoSolicitud = 'Pendiente_Jefe'
                    AND s.AprobadorCargoId = (
                        SELECT u.CargoId FROM gcp_usuarios u
                        WHERE u.Id = :approverId AND u.Estado = 1
                    )
                 )
                 OR EXISTS (
                    SELECT 1 FROM gcp_solicitudhistorial h
                    WHERE h.SolicitudId = s.Id AND h.UsuarioId = :historyUserId
                 )
                 OR (s.UsuarioId <> :hrRequesterId AND s.NivelActual = 2 AND s.EstadoSolicitud = 'Pendiente_RRHH' AND :isHr = 1)
                 OR (s.UsuarioId <> :managementRequesterId AND s.NivelActual = 3 AND s.EstadoSolicitud = 'Pendiente_Gerencia' AND :isManagement = 1)
              )
        ");
        $stmt->execute([
            ':Id' => $requestId,
            ':requesterId' => $userId,
            ':cargoRequesterId' => $userId,
            ':approverId' => $userId,
            ':historyUserId' => $userId,
            ':hrRequesterId' => $userId,
            ':managementRequesterId' => $userId,
            ':isHr' => $isHr ? 1 : 0,
            ':isManagement' => $isManagement ? 1 : 0,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO gcp_solicitudes
                    (UsuarioId, AprobadorCargoId, TipoId, Asunto, Descripcion, FechaInicio, FechaFin, EstadoSolicitud, NivelActual)
                VALUES
                    (:UsuarioId, :AprobadorCargoId, :TipoId, :Asunto, :Descripcion, :FechaInicio, :FechaFin, 'Pendiente_Jefe', 1)
            ");
            $stmt->execute([
                ':UsuarioId' => $data['UsuarioId'],
                ':AprobadorCargoId' => $data['AprobadorCargoId'],
                ':TipoId' => $data['TipoId'],
                ':Asunto' => $data['Asunto'],
                ':Descripcion' => $data['Descripcion'],
                ':FechaInicio' => $data['FechaInicio'],
                ':FechaFin' => $data['FechaFin'],
            ]);
            $id = (int) $this->db->lastInsertId();

            $history = $this->db->prepare("
                INSERT INTO gcp_solicitudhistorial (SolicitudId, UsuarioId, Accion, Comentario, Nivel)
                VALUES (:SolicitudId, :UsuarioId, 'Creada', NULL, 1)
            ");
            $history->execute([':SolicitudId' => $id, ':UsuarioId' => $data['UsuarioId']]);

            if (!empty($data['Adjunto'])) {
                $attachment = $this->db->prepare("
                    INSERT INTO gcp_solicitudadjuntos (SolicitudId, NombreArchivo, RutaArchivo)
                    VALUES (:SolicitudId, :NombreArchivo, :RutaArchivo)
                ");
                $attachment->execute([
                    ':SolicitudId' => $id,
                    ':NombreArchivo' => $data['Adjunto']['NombreArchivo'],
                    ':RutaArchivo' => $data['Adjunto']['RutaArchivo'],
                ]);
            }

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function decide(int $requestId, int $userId, string $action, string $comment, array $roles): ?array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT s.*, u.CargoId AS UsuarioCargoId
                FROM gcp_solicitudes s
                INNER JOIN gcp_usuarios u ON u.Id = :UsuarioId AND u.Estado = 1
                WHERE s.Id = :Id
                FOR UPDATE
            ");
            $stmt->execute([':UsuarioId' => $userId, ':Id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->db->commit();
                return ['error' => 'not_found'];
            }

            $level = (int) $request['NivelActual'];
            $currentStatus = $request['EstadoSolicitud'];
            $expectedStatus = [1 => 'Pendiente_Jefe', 2 => 'Pendiente_RRHH', 3 => 'Pendiente_Gerencia'][$level] ?? '';
            $authorized = (int) $request['UsuarioId'] !== $userId && match ($level) {
                1 => (int) $request['AprobadorCargoId'] > 0
                    && (int) $request['AprobadorCargoId'] === (int) $request['UsuarioCargoId'],
                2 => in_array('rrhh', $roles, true),
                3 => in_array('gerencia', $roles, true),
                default => false,
            };

            if (!$authorized) {
                $this->db->commit();
                return ['error' => 'forbidden'];
            }
            if ($currentStatus !== $expectedStatus) {
                $this->db->commit();
                return ['error' => 'already_decided'];
            }
            if ($action === 'Rechazada' && trim($comment) === '') {
                $this->db->commit();
                return ['error' => 'rejection_comment_required'];
            }

            if ($action === 'Rechazada') {
                $nextStatus = 'Rechazada';
            } else {
                $nextStatus = [
                    1 => 'Pendiente_RRHH',
                    2 => 'Pendiente_Gerencia',
                    3 => 'Aprobada',
                ][$level];
            }
            $nextLevel = $action === 'Rechazada' ? $level : min($level + 1, 3);

            $update = $this->db->prepare("
                UPDATE gcp_solicitudes
                SET EstadoSolicitud = :EstadoSolicitud,
                    NivelActual = :NivelActual,
                    MotivoRechazo = :MotivoRechazo,
                    FechaActualizacion = CURRENT_TIMESTAMP
                WHERE Id = :Id
            ");
            $update->execute([
                ':EstadoSolicitud' => $nextStatus,
                ':NivelActual' => $nextLevel,
                ':MotivoRechazo' => $action === 'Rechazada' ? trim($comment) : null,
                ':Id' => $requestId,
            ]);

            $history = $this->db->prepare("
                INSERT INTO gcp_solicitudhistorial (SolicitudId, UsuarioId, Accion, Comentario, Nivel)
                VALUES (:SolicitudId, :UsuarioId, :Accion, :Comentario, :Nivel)
            ");
            $history->execute([
                ':SolicitudId' => $requestId,
                ':UsuarioId' => $userId,
                ':Accion' => $action,
                ':Comentario' => trim($comment) !== '' ? trim($comment) : null,
                ':Nivel' => $level,
            ]);

            $this->db->commit();
            return ['EstadoSolicitud' => $nextStatus, 'NivelActual' => $nextLevel];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getAttachment(int $requestId, int $attachmentId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.NombreArchivo, a.RutaArchivo
            FROM gcp_solicitudadjuntos a
            WHERE a.Id = :AttachmentId AND a.SolicitudId = :SolicitudId
        ");
        $stmt->execute([':AttachmentId' => $attachmentId, ':SolicitudId' => $requestId]);
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $attachment ?: null;
    }

    private function selectSql(): string
    {
        return "
            SELECT s.Id, s.UsuarioId, s.AprobadorCargoId, s.TipoId, s.Asunto, s.Descripcion,
                   s.FechaInicio, s.FechaFin, s.EstadoSolicitud, s.NivelActual,
                   s.MotivoRechazo, s.FechaCreacion, s.FechaActualizacion,
                   t.Nombre AS TipoNombre,
                   u.Nombres AS SolicitanteNombres, u.Apellidos AS SolicitanteApellidos,
                   jefeCargo.Nombre AS JefeAprobadorCargoNombre
            FROM gcp_solicitudes s
            INNER JOIN gcp_solicitudtipos t ON t.Id = s.TipoId
            INNER JOIN gcp_usuarios u ON u.Id = s.UsuarioId
            LEFT JOIN gcp_cargos jefeCargo ON jefeCargo.Id = s.AprobadorCargoId
        ";
    }
}
