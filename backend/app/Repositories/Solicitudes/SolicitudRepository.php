<?php

namespace App\Repositories\Solicitudes;

use App\Repositories\BaseRepository;
use PDO;

class SolicitudRepository extends BaseRepository
{
    protected string $table = 'gestion_tickets';

    public function getAll(): array
    {
        $sql = "
            SELECT s.*, 
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   sol.Nombres AS SolicitanteNombre, sol.Apellidos AS SolicitanteApellidos,
                   asig.Nombres AS AsignadoNombre, asig.Apellidos AS AsignadoApellidos,
                   (SELECT COUNT(*) FROM gestion_tickets_seguimiento WHERE SolicitudId = s.Id) AS Seguimientos
            FROM {$this->table} s
            LEFT JOIN gestion_tickets_categorias c ON c.Id = s.CategoriaId
            LEFT JOIN gcp_usuarios sol ON sol.Id = s.SolicitanteId
            LEFT JOIN gcp_usuarios asig ON asig.Id = s.AsignadoA
            ORDER BY s.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIncidentContext(int $userId): array
    {
        $locationStmt = $this->db->prepare("
            SELECT CONCAT_WS(', ', NULLIF(a.Nombre, ''), NULLIF(ci.Nombre, '')) AS Ubicacion
            FROM gcp_usuarios u
            INNER JOIN gcp_agencias a ON a.Id = u.AgenciaId
            INNER JOIN gcp_ciudades ci ON ci.Id = a.CiudadId
            WHERE u.Id = :UsuarioId AND u.Estado = 1
            LIMIT 1
        ");
        $locationStmt->execute([':UsuarioId' => $userId]);
        $location = $locationStmt->fetchColumn();

        $companiesStmt = $this->db->prepare("
            SELECT e.Id, e.Nombre
            FROM gcp_usuariosempresas ue
            INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId
            WHERE ue.UsuarioId = :UsuarioId AND e.Estado = 1
            ORDER BY e.Nombre ASC
        ");
        $companiesStmt->execute([':UsuarioId' => $userId]);

        return [
            'Ubicacion' => $location ?: '',
            'Empresas' => $companiesStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function getByStatus(string $estado): array
    {
        $sql = "
            SELECT s.*, 
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   sol.Nombres AS SolicitanteNombre, sol.Apellidos AS SolicitanteApellidos,
                   asig.Nombres AS AsignadoNombre, asig.Apellidos AS AsignadoApellidos,
                   emp.Nombre AS EmpresaNombre
            FROM {$this->table} s
            LEFT JOIN gestion_tickets_categorias c ON c.Id = s.CategoriaId
            LEFT JOIN gcp_usuarios sol ON sol.Id = s.SolicitanteId
            LEFT JOIN gcp_usuarios asig ON asig.Id = s.AsignadoA
            LEFT JOIN gcp_empresas emp ON emp.Id = s.EmpresaId
            WHERE s.Estado = :estado
            ORDER BY s.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $estado]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByAssignee(int $usuarioId): array
    {
        $sql = "
            SELECT s.*, 
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   sol.Nombres AS SolicitanteNombre, sol.Apellidos AS SolicitanteApellidos,
                   asig.Nombres AS AsignadoNombre, asig.Apellidos AS AsignadoApellidos
            FROM {$this->table} s
            LEFT JOIN gestion_tickets_categorias c ON c.Id = s.CategoriaId
            LEFT JOIN gcp_usuarios sol ON sol.Id = s.SolicitanteId
            LEFT JOIN gcp_usuarios asig ON asig.Id = s.AsignadoA
            WHERE s.AsignadoA = :usuarioId AND s.Estado != 'cerrada'
            ORDER BY s.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuarioId' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByRequester(int $usuarioId): array
    {
        $sql = "
            SELECT s.*, 
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   sol.Nombres AS SolicitanteNombre, sol.Apellidos AS SolicitanteApellidos,
                   asig.Nombres AS AsignadoNombre, asig.Apellidos AS AsignadoApellidos
            FROM {$this->table} s
            LEFT JOIN gestion_tickets_categorias c ON c.Id = s.CategoriaId
            LEFT JOIN gcp_usuarios sol ON sol.Id = s.SolicitanteId
            LEFT JOIN gcp_usuarios asig ON asig.Id = s.AsignadoA
            WHERE s.SolicitanteId = :usuarioId
            ORDER BY s.FechaCreacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuarioId' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIncidencias(?int $solicitanteId = null): array
    {
        $sql = "
            SELECT s.*,
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   sol.Nombres AS SolicitanteNombre, sol.Apellidos AS SolicitanteApellidos,
                   asig.Nombres AS AsignadoNombre, asig.Apellidos AS AsignadoApellidos,
                   emp.Nombre AS EmpresaNombre,
                   (SELECT COUNT(*) FROM gestion_tickets_seguimiento seg WHERE seg.SolicitudId = s.Id) AS Seguimientos
            FROM {$this->table} s
            LEFT JOIN gestion_tickets_categorias c ON c.Id = s.CategoriaId
            LEFT JOIN gcp_usuarios sol ON sol.Id = s.SolicitanteId
            LEFT JOIN gcp_usuarios asig ON asig.Id = s.AsignadoA
            LEFT JOIN gcp_empresas emp ON emp.Id = s.EmpresaId
            WHERE s.Tipo = 'incidencia'
        ";
        $params = [];
        if ($solicitanteId !== null) {
            $sql .= ' AND s.SolicitanteId = :SolicitanteId';
            $params[':SolicitanteId'] = $solicitanteId;
        }
        $sql .= ' ORDER BY s.FechaCreacion DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIncidencia(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*,
                   c.Nombre AS CategoriaNombre, c.Color AS CategoriaColor,
                   sol.Nombres AS SolicitanteNombre, sol.Apellidos AS SolicitanteApellidos,
                   asig.Nombres AS AsignadoNombre, asig.Apellidos AS AsignadoApellidos,
                   emp.Nombre AS EmpresaNombre
            FROM {$this->table} s
            LEFT JOIN gestion_tickets_categorias c ON c.Id = s.CategoriaId
            LEFT JOIN gcp_usuarios sol ON sol.Id = s.SolicitanteId
            LEFT JOIN gcp_usuarios asig ON asig.Id = s.AsignadoA
            LEFT JOIN gcp_empresas emp ON emp.Id = s.EmpresaId
            WHERE s.Id = :Id AND s.Tipo = 'incidencia'
        ");
        $stmt->execute([':Id' => $id]);
        $incident = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$incident) {
            return null;
        }

        $history = $this->db->prepare("
            SELECT seg.*, u.Nombres AS UsuarioNombre, u.Apellidos AS UsuarioApellidos
            FROM gestion_tickets_seguimiento seg
            INNER JOIN gcp_usuarios u ON u.Id = seg.UsuarioId
            WHERE seg.SolicitudId = :SolicitudId
            ORDER BY seg.FechaCreacion ASC, seg.Id ASC
        ");
        $history->execute([':SolicitudId' => $id]);
        $incident['Historial'] = $history->fetchAll(PDO::FETCH_ASSOC);

        return $incident;
    }

    public function getIncidentAssignableUsers(): array
    {
        $stmt = $this->db->query("
            SELECT Id, Nombres, Apellidos, EmailCorporativo
            FROM gcp_usuarios
            WHERE Estado = 1
            ORDER BY Nombres ASC, Apellidos ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateIncidencia(int $id, int $userId, array $data): bool
    {
        $this->db->beginTransaction();
        try {
            $currentStmt = $this->db->prepare("SELECT Estado, AsignadoA FROM {$this->table} WHERE Id = :Id AND Tipo = 'incidencia' FOR UPDATE");
            $currentStmt->execute([':Id' => $id]);
            $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                $this->db->commit();
                return false;
            }

            $fields = [];
            $params = [':Id' => $id];
            $comment = trim((string) ($data['Comentario'] ?? ''));
            $nextStatus = $data['Estado'] ?? $current['Estado'];
            $firstResponse = $current['Estado'] === 'nueva'
                && ($nextStatus === 'pendiente' || $comment !== '');
            if ($firstResponse) {
                $nextStatus = 'pendiente';
            }
            if (array_key_exists('Estado', $data) || $firstResponse) {
                $fields[] = 'Estado = :Estado';
                $params[':Estado'] = $nextStatus;
                if ($nextStatus === 'cerrada' && $current['Estado'] !== 'cerrada') {
                    $fields[] = 'FechaResolucion = CURRENT_TIMESTAMP';
                } elseif ($current['Estado'] === 'cerrada') {
                    $fields[] = 'FechaResolucion = NULL';
                }
            }
            if (array_key_exists('Prioridad', $data)) {
                $fields[] = 'Prioridad = :Prioridad';
                $params[':Prioridad'] = $data['Prioridad'];
            }
            if ($firstResponse) {
                $fields[] = 'AsignadoA = :AsignadoA';
                $params[':AsignadoA'] = $userId;
            }

            if ($fields) {
                $update = $this->db->prepare("UPDATE {$this->table} SET " . implode(', ', $fields) . ' WHERE Id = :Id');
                $update->execute($params);
            }

            $stateChanged = $nextStatus !== $current['Estado'];
            $assigneeChanged = $firstResponse && (int) $userId !== (int) ($current['AsignadoA'] ?? 0);
            if ($stateChanged || $assigneeChanged || $comment !== '') {
                $followUp = $this->db->prepare("
                    INSERT INTO gestion_tickets_seguimiento
                        (SolicitudId, UsuarioId, EstadoAnterior, EstadoNuevo, Comentario, TipoCambio)
                    VALUES
                        (:SolicitudId, :UsuarioId, :EstadoAnterior, :EstadoNuevo, :Comentario, :TipoCambio)
                ");
                $followUp->execute([
                    ':SolicitudId' => $id,
                    ':UsuarioId' => $userId,
                    ':EstadoAnterior' => $stateChanged ? $current['Estado'] : null,
                    ':EstadoNuevo' => $stateChanged ? $nextStatus : null,
                    ':Comentario' => $comment !== '' ? $comment : null,
                    ':TipoCambio' => $assigneeChanged ? 'asignacion' : ($stateChanged ? 'estado' : 'comentario'),
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function closeIncidenciaByRequester(int $id, int $userId, array $survey): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT Estado
                FROM {$this->table}
                WHERE Id = :Id AND SolicitanteId = :UsuarioId AND Tipo = 'incidencia'
                FOR UPDATE
            ");
            $stmt->execute([':Id' => $id, ':UsuarioId' => $userId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ticket || $ticket['Estado'] !== 'pendiente') {
                $this->db->rollBack();
                return false;
            }

            $update = $this->db->prepare("
                UPDATE {$this->table}
                SET Estado = 'cerrada',
                    FechaResolucion = CURRENT_TIMESTAMP,
                    SatisfaccionTiempoSolucion = :Tiempo,
                    SatisfaccionCalidadAtencion = :Calidad,
                    SatisfaccionEficaciaSolucion = :Eficacia,
                    ComentarioSatisfaccion = :Comentario
                WHERE Id = :Id AND SolicitanteId = :UsuarioId AND Estado = 'pendiente'
            ");
            $update->execute([
                ':Id' => $id,
                ':UsuarioId' => $userId,
                ':Tiempo' => $survey['SatisfaccionTiempoSolucion'],
                ':Calidad' => $survey['SatisfaccionCalidadAtencion'],
                ':Eficacia' => $survey['SatisfaccionEficaciaSolucion'],
                ':Comentario' => $survey['ComentarioSatisfaccion'] !== '' ? $survey['ComentarioSatisfaccion'] : null,
            ]);
            if ($update->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            $followUp = $this->db->prepare("
                INSERT INTO gestion_tickets_seguimiento
                    (SolicitudId, UsuarioId, EstadoAnterior, EstadoNuevo, Comentario, TipoCambio)
                VALUES (:SolicitudId, :UsuarioId, 'pendiente', 'cerrada', :Comentario, 'estado')
            ");
            $followUp->execute([
                ':SolicitudId' => $id,
                ':UsuarioId' => $userId,
                ':Comentario' => $survey['ComentarioSatisfaccion'] !== '' ? $survey['ComentarioSatisfaccion'] : null,
            ]);
            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function generateTicket(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "SOL-{$year}{$month}-";
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE Ticket LIKE :prefix";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $result = $stmt->fetch();
        
        $sequence = str_pad(($result['count'] + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . $sequence;
    }

    public function create(array $solicitud): int
    {
        $ticket = $this->generateTicket();
        
        $sql = "
            INSERT INTO {$this->table} (Ticket, CategoriaId, SolicitanteId, EmpresaId, AsignadoA, Prioridad, Estado, Tipo, Titulo, Descripcion, Ubicacion, ArchivoAdjunto, ArchivoAdjuntoNombre, Canal, FechaVencimiento)
            VALUES (:Ticket, :CategoriaId, :SolicitanteId, :EmpresaId, :AsignadoA, :Prioridad, :Estado, :Tipo, :Titulo, :Descripcion, :Ubicacion, :ArchivoAdjunto, :ArchivoAdjuntoNombre, :Canal, :FechaVencimiento)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':Ticket' => $ticket,
            ':CategoriaId' => $solicitud['CategoriaId'],
            ':SolicitanteId' => $solicitud['SolicitanteId'],
            ':EmpresaId' => $solicitud['EmpresaId'] ?? null,
            ':AsignadoA' => $solicitud['AsignadoA'] ?? null,
            ':Prioridad' => $solicitud['Prioridad'] ?? 'media',
            ':Estado' => $solicitud['Estado'] ?? 'nueva',
            ':Tipo' => $solicitud['Tipo'] ?? 'soporte',
            ':Titulo' => $solicitud['Titulo'],
            ':Descripcion' => $solicitud['Descripcion'],
            ':Ubicacion' => $solicitud['Ubicacion'] ?? null,
            ':ArchivoAdjunto' => $solicitud['ArchivoAdjunto'] ?? null,
            ':ArchivoAdjuntoNombre' => $solicitud['ArchivoAdjuntoNombre'] ?? null,
            ':Canal' => $solicitud['Canal'] ?? 'portal',
            ':FechaVencimiento' => $solicitud['FechaVencimiento'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $solicitud): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                CategoriaId = :CategoriaId,
                AsignadoA = :AsignadoA,
                Prioridad = :Prioridad,
                Estado = :Estado,
                Tipo = :Tipo,
                Titulo = :Titulo,
                Descripcion = :Descripcion,
                Ubicacion = :Ubicacion,
                ArchivoAdjunto = :ArchivoAdjunto,
                FechaVencimiento = :FechaVencimiento,
                FechaResolucion = :FechaResolucion,
                Satisfaccion = :Satisfaccion,
                ComentarioSatisfaccion = :ComentarioSatisfaccion
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':CategoriaId' => $solicitud['CategoriaId'],
            ':AsignadoA' => $solicitud['AsignadoA'] ?? null,
            ':Prioridad' => $solicitud['Prioridad'] ?? 'media',
            ':Estado' => $solicitud['Estado'] ?? 'nueva',
            ':Tipo' => $solicitud['Tipo'] ?? 'soporte',
            ':Titulo' => $solicitud['Titulo'],
            ':Descripcion' => $solicitud['Descripcion'],
            ':Ubicacion' => $solicitud['Ubicacion'] ?? null,
            ':ArchivoAdjunto' => $solicitud['ArchivoAdjunto'] ?? null,
            ':FechaVencimiento' => $solicitud['FechaVencimiento'] ?? null,
            ':FechaResolucion' => $solicitud['FechaResolucion'] ?? null,
            ':Satisfaccion' => $solicitud['Satisfaccion'] ?? null,
            ':ComentarioSatisfaccion' => $solicitud['ComentarioSatisfaccion'] ?? null,
        ]);
    }

    public function changeStatus(int $id, bool|string $estado): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET Estado = :Estado
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':Estado' => $estado,
        ]);
    }

    public function assignTo(int $id, int $usuarioId): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET AsignadoA = :AsignadoA
            WHERE Id = :Id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':Id' => $id,
            ':AsignadoA' => $usuarioId,
        ]);
    }
}