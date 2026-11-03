<?php

namespace App\Repositories\Solicitudes;

use App\Repositories\BaseRepository;
use PDO;

class SolicitudLaboralRepository extends BaseRepository
{
    public const AUX_CARGO = 'Aux Gestión Humana';
    public const ANALYST_CARGO = 'Analista Nómina';

    /** @return 'aux'|'analista'|null según el cargo del usuario */
    public function getPayrollRole(int $userId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT c.Nombre FROM gcp_usuarios u INNER JOIN gcp_cargos c ON c.Id = u.CargoId WHERE u.Id = :Id AND u.Estado = 1'
        );
        $stmt->execute([':Id' => $userId]);
        $name = $stmt->fetchColumn();
        if ($name === false) {
            return null;
        }

        $normalize = static fn (string $value): string => mb_strtolower(
            strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U'])
        );
        return match ($normalize(trim((string) $name))) {
            $normalize(self::AUX_CARGO) => 'aux',
            $normalize(self::ANALYST_CARGO) => 'analista',
            default => null,
        };
    }

    public function getPayrollQueue(string $payrollRole): array
    {
        $states = $payrollRole === 'aux'
            ? ['Pendiente_Liquidacion', 'Con_Errores']
            : ['Pendiente_Revision_Nomina', 'Pendiente_Pago'];
        $placeholders = implode(',', array_fill(0, count($states), '?'));
        $stmt = $this->db->prepare(
            $this->selectSql() . " WHERE s.EstadoNomina IN ($placeholders) ORDER BY s.FechaActualizacion ASC"
        );
        $stmt->execute($states);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPayrollState(int $requestId): ?string
    {
        $stmt = $this->db->prepare('SELECT EstadoNomina FROM gcp_solicitudes WHERE Id = :Id');
        $stmt->execute([':Id' => $requestId]);
        $state = $stmt->fetchColumn();

        return $state === false || $state === null ? null : (string) $state;
    }

    public function getRequesterId(int $requestId): ?int
    {
        $stmt = $this->db->prepare('SELECT UsuarioId FROM gcp_solicitudes WHERE Id = :Id');
        $stmt->execute([':Id' => $requestId]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function getPayrollDocuments(int $requestId): array
    {
        $stmt = $this->db->prepare("
            SELECT d.Id, d.NombreArchivo, d.FechaSubida, u.Nombres, u.Apellidos
            FROM gcp_solicitudnomina d
            INNER JOIN gcp_usuarios u ON u.Id = d.UsuarioId
            WHERE d.SolicitudId = :SolicitudId
            ORDER BY d.Id DESC
        ");
        $stmt->execute([':SolicitudId' => $requestId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPayrollDocument(int $requestId, int $documentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT NombreArchivo, RutaArchivo FROM gcp_solicitudnomina WHERE Id = :Id AND SolicitudId = :SolicitudId'
        );
        $stmt->execute([':Id' => $documentId, ':SolicitudId' => $requestId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        return $document ?: null;
    }

    /**
     * Cambia el estado de nómina solo si el actual está entre los permitidos.
     * Devuelve false si el estado no era el esperado (p. ej. otra persona ya lo procesó).
     */
    public function transitionPayroll(
        int $requestId,
        int $userId,
        array $fromStates,
        string $toState,
        string $historyAction,
        ?string $comment,
        ?array $document = null
    ): bool {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT EstadoNomina FROM gcp_solicitudes WHERE Id = :Id FOR UPDATE');
            $lock->execute([':Id' => $requestId]);
            $current = $lock->fetchColumn();
            if ($current === false || !in_array($current, $fromStates, true)) {
                $this->db->commit();
                return false;
            }

            $this->db->prepare('UPDATE gcp_solicitudes SET EstadoNomina = :Estado WHERE Id = :Id')
                ->execute([':Estado' => $toState, ':Id' => $requestId]);

            if ($document) {
                $this->db->prepare("
                    INSERT INTO gcp_solicitudnomina (SolicitudId, UsuarioId, NombreArchivo, RutaArchivo)
                    VALUES (:SolicitudId, :UsuarioId, :NombreArchivo, :RutaArchivo)
                ")->execute([
                    ':SolicitudId' => $requestId,
                    ':UsuarioId' => $userId,
                    ':NombreArchivo' => $document['NombreArchivo'],
                    ':RutaArchivo' => $document['RutaArchivo'],
                ]);
            }

            $this->db->prepare("
                INSERT INTO gcp_solicitudhistorial (SolicitudId, UsuarioId, Accion, Comentario, Nivel)
                VALUES (:SolicitudId, :UsuarioId, :Accion, :Comentario, 4)
            ")->execute([
                ':SolicitudId' => $requestId,
                ':UsuarioId' => $userId,
                ':Accion' => $historyAction,
                ':Comentario' => $comment !== null && $comment !== '' ? $comment : null,
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

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

    public function getUserCompanies(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT e.Id, e.Nombre, e.Codigo
            FROM gcp_usuariosempresas ue
            INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId AND e.Estado = 1
            WHERE ue.UsuarioId = :UsuarioId
            ORDER BY e.Id
        ');
        $stmt->execute([':UsuarioId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function canView(int $requestId, int $userId, bool $isHr, bool $isManagement, bool $isPayroll = false): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM gcp_solicitudes s
            WHERE s.Id = :Id
              AND (
                    s.UsuarioId = :requesterId
                 OR (s.EstadoNomina IS NOT NULL AND :isPayroll = 1)
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
            ':isPayroll' => $isPayroll ? 1 : 0,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO gcp_solicitudes
                        (UsuarioId, AprobadorCargoId, TipoId, Asunto, Descripcion, FechaInicio, FechaFin, EmpresaSolicitudId,
                     FechaReintegro, DiasDinero, DiasDisfrute, PeriodoPendiente, EstadoSolicitud, NivelActual)
                VALUES
                        (:UsuarioId, :AprobadorCargoId, :TipoId, :Asunto, :Descripcion, :FechaInicio, :FechaFin, :EmpresaSolicitudId,
                     :FechaReintegro, :DiasDinero, :DiasDisfrute, :PeriodoPendiente, 'Pendiente_Jefe', 1)
            ");
            $stmt->execute([
                ':UsuarioId' => $data['UsuarioId'],
                ':AprobadorCargoId' => $data['AprobadorCargoId'],
                ':TipoId' => $data['TipoId'],
                ':Asunto' => $data['Asunto'],
                ':Descripcion' => $data['Descripcion'],
                ':FechaInicio' => $data['FechaInicio'],
                ':FechaFin' => $data['FechaFin'],
                ':EmpresaSolicitudId' => $data['EmpresaSolicitudId'] ?? null,
                ':FechaReintegro' => $data['FechaReintegro'] ?? null,
                ':DiasDinero' => $data['DiasDinero'] ?? null,
                ':DiasDisfrute' => $data['DiasDisfrute'] ?? null,
                ':PeriodoPendiente' => $data['PeriodoPendiente'] ?? null,
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
                    EstadoNomina = :EstadoNomina,
                    FechaActualizacion = CURRENT_TIMESTAMP
                WHERE Id = :Id
            ");
            $update->execute([
                ':EstadoSolicitud' => $nextStatus,
                ':NivelActual' => $nextLevel,
                ':MotivoRechazo' => $action === 'Rechazada' ? trim($comment) : null,
                ':EstadoNomina' => $nextStatus === 'Aprobada' ? 'Pendiente_Liquidacion' : null,
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
            return ['EstadoSolicitud' => $nextStatus, 'NivelActual' => $nextLevel, 'SolicitanteId' => (int) $request['UsuarioId']];
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
                   s.FechaReintegro, s.DiasDinero, s.DiasDisfrute, s.PeriodoPendiente, s.EstadoNomina,
                   t.Nombre AS TipoNombre, t.Codigo AS TipoCodigo,
                   u.Nombres AS SolicitanteNombres, u.Apellidos AS SolicitanteApellidos,
                   u.Cedula AS SolicitanteCedula,
                   u.EmailCorporativo AS SolicitanteEmail, u.Telefono AS SolicitanteTelefono,
                   solicitanteCargo.Nombre AS SolicitanteCargo,
                   jefeCargo.Nombre AS JefeAprobadorCargoNombre,
                   COALESCE(s.EmpresaSolicitudId, (SELECT ue.EmpresaId FROM gcp_usuariosempresas ue
                    WHERE ue.UsuarioId = s.UsuarioId ORDER BY ue.EmpresaId LIMIT 1)) AS EmpresaId,
                   (SELECT e.Nombre FROM gcp_usuariosempresas ue
                    INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId
                    WHERE ue.UsuarioId = s.UsuarioId
                      AND ue.EmpresaId = COALESCE(s.EmpresaSolicitudId, (
                        SELECT ueEmpresa.EmpresaId FROM gcp_usuariosempresas ueEmpresa
                        WHERE ueEmpresa.UsuarioId = s.UsuarioId ORDER BY ueEmpresa.EmpresaId LIMIT 1
                      ))
                    ORDER BY e.Id LIMIT 1) AS EmpresaNombre,
                   (SELECT CONCAT(gerente.Nombres, ' ', gerente.Apellidos)
                    FROM gcp_usuariosempresas ueGerencia
                    INNER JOIN gcp_usuarios gerente ON gerente.Id = ueGerencia.UsuarioId AND gerente.Estado = 1
                    INNER JOIN gcp_cargos cargoGerencia ON cargoGerencia.Id = gerente.CargoId AND cargoGerencia.Nombre = 'Gerencia'
                    WHERE ueGerencia.EmpresaId = COALESCE(s.EmpresaSolicitudId, (
                        SELECT ueEmpresa.EmpresaId FROM gcp_usuariosempresas ueEmpresa
                        WHERE ueEmpresa.UsuarioId = s.UsuarioId ORDER BY ueEmpresa.EmpresaId LIMIT 1
                    ))
                    ORDER BY gerente.Id LIMIT 1) AS GerenteNombre
            FROM gcp_solicitudes s
            INNER JOIN gcp_solicitudtipos t ON t.Id = s.TipoId
            INNER JOIN gcp_usuarios u ON u.Id = s.UsuarioId
            LEFT JOIN gcp_cargos jefeCargo ON jefeCargo.Id = s.AprobadorCargoId
            LEFT JOIN gcp_cargos solicitanteCargo ON solicitanteCargo.Id = u.CargoId
        ";
    }
}
