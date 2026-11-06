<?php

namespace App\Repositories;

use PDO;

class DotacionRepository extends BaseRepository
{
    public const CARGO_JEFE_GESTION_HUMANA = 22;
    public const CARGO_COORDINADOR_DOTACION = 36;

    public function getCreateOptions(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.Nombres, u.Apellidos, u.Cedula, cargo.Nombre AS Cargo,
                   e.Id AS EmpresaId, e.Nombre AS EmpresaNombre, e.Codigo AS EmpresaCodigo
            FROM gcp_usuarios u
            INNER JOIN gcp_cargos cargo ON cargo.Id = u.CargoId
            INNER JOIN gcp_usuariosempresas ue ON ue.UsuarioId = u.Id
            INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId AND e.Estado = 1
            WHERE u.Id = :UsuarioId AND u.Estado = 1
            ORDER BY e.Nombre
        ");
        $stmt->execute([':UsuarioId' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $first = $rows[0] ?? [];
        $cargo = trim((string) ($first['Cargo'] ?? ''));

        return [
            'Nombres' => $first['Nombres'] ?? '',
            'Apellidos' => $first['Apellidos'] ?? '',
            'Cedula' => $first['Cedula'] ?? '',
            'Cargo' => $cargo,
            'EsConductor' => $this->normalize($cargo) === 'conductor',
            'Empresas' => array_map(static fn (array $row): array => [
                'Id' => (int) $row['EmpresaId'],
                'Nombre' => $row['EmpresaNombre'],
                'Codigo' => $row['EmpresaCodigo'],
            ], $rows),
        ];
    }

    public function getApprovers(int $companyId): array
    {
        $company = $this->getCompany($companyId);
        if (!$company) {
            return ['Empresa' => null, 'EsFlota' => false, 'JefeGestionHumana' => null, 'Coordinador' => null];
        }

        $isFlota = $this->normalize((string) $company['Codigo']) === 'fm'
            || str_contains($this->normalize((string) $company['Nombre']), 'flota magdalena');

        return [
            'Empresa' => $company,
            'EsFlota' => $isFlota,
            'JefeGestionHumana' => $this->findCompanyApprover($companyId, self::CARGO_JEFE_GESTION_HUMANA, true),
            'Coordinador' => $this->findCompanyApprover($companyId, self::CARGO_COORDINADOR_DOTACION, false),
        ];
    }

    public function getMine(int $userId): array
    {
        $stmt = $this->db->prepare($this->selectSql() . '
            WHERE (s.UsuarioId = :UsuarioId OR s.CreadoPorId = :CreadoPorId)
            ORDER BY s.FechaActualizacion DESC, s.Id DESC
        ');
        $stmt->execute([':UsuarioId' => $userId, ':CreadoPorId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPending(int $userId): array
    {
        $stmt = $this->db->prepare($this->selectSql() . "
            WHERE (s.Estado = 'Pendiente_Jefe' AND s.JefeGestionHumanaId = :JefeId)
               OR (s.Estado = 'Pendiente_Coordinador' AND s.CoordinadorId = :CoordinadorId)
            ORDER BY s.FechaCreacion ASC
        ");
        $stmt->execute([':JefeId' => $userId, ':CoordinadorId' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $requestId): ?array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE s.Id = :Id');
        $stmt->execute([':Id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            return null;
        }

        $items = $this->db->prepare('
            SELECT Id, Cantidad, Referencia, Talla FROM gcp_dotacionitems
            WHERE SolicitudId = :SolicitudId ORDER BY Id
        ');
        $items->execute([':SolicitudId' => $requestId]);
        $request['Articulos'] = $items->fetchAll(PDO::FETCH_ASSOC);

        $history = $this->db->prepare('
            SELECT h.Id, h.Nivel, h.Accion, h.Comentario, h.Fecha, u.Nombres, u.Apellidos
            FROM gcp_dotacionhistorial h
            INNER JOIN gcp_usuarios u ON u.Id = h.UsuarioId
            WHERE h.SolicitudId = :SolicitudId ORDER BY h.Fecha, h.Id
        ');
        $history->execute([':SolicitudId' => $requestId]);
        $request['Historial'] = $history->fetchAll(PDO::FETCH_ASSOC);

        return $request;
    }

    /** Entregas anteriores (histÃ³rico importado y solicitudes recibidas) por cÃ©dula. */
    public function getDeliveryHistory(string $cedula, int $excludeRequestId): array
    {
        $cedula = preg_replace('/\D+/', '', $cedula);
        if ($cedula === '') {
            return [];
        }

        $stmt = $this->db->prepare('
            SELECT e.id, e.fecha_entrega AS Fecha, emp.Nombre AS Empresa
            FROM entregas_dotacion e
            INNER JOIN gcp_empresas emp ON emp.Id = e.empresa_id
            WHERE e.cedula = :Cedula
        ');
        $stmt->execute([':Cedula' => $cedula]);
        $itemsStmt = $this->db->prepare('
            SELECT cantidad AS Cantidad, referencia AS Referencia, talla AS Talla
            FROM entregas_dotacion_items WHERE entrega_id = :Id ORDER BY id
        ');
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $itemsStmt->execute([':Id' => $row['id']]);
            $result[] = [
                'Origen' => 'Historico',
                'Fecha' => $row['Fecha'],
                'Empresa' => $row['Empresa'],
                'Articulos' => $itemsStmt->fetchAll(PDO::FETCH_ASSOC),
            ];
        }

        $current = $this->db->prepare("
            SELECT s.Id, emp.Nombre AS Empresa, MAX(h.Fecha) AS Fecha
            FROM gcp_dotacionsolicitudes s
            INNER JOIN gcp_usuarios u ON u.Id = s.UsuarioId
            INNER JOIN gcp_empresas emp ON emp.Id = s.EmpresaId
            INNER JOIN gcp_dotacionhistorial h ON h.SolicitudId = s.Id AND h.Accion = 'Recibida'
            WHERE s.Estado = 'Recibida' AND s.Id <> :Excluir
              AND u.Cedula = :Cedula
            GROUP BY s.Id, emp.Nombre
        ");
        $current->execute([':Excluir' => $excludeRequestId, ':Cedula' => $cedula]);
        $currentItems = $this->db->prepare('
            SELECT Cantidad, Referencia, Talla FROM gcp_dotacionitems WHERE SolicitudId = :Id ORDER BY Id
        ');
        foreach ($current->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $currentItems->execute([':Id' => $row['Id']]);
            $result[] = [
                'Origen' => 'Sistema',
                'Fecha' => substr((string) $row['Fecha'], 0, 10),
                'Empresa' => $row['Empresa'],
                'Articulos' => $currentItems->fetchAll(PDO::FETCH_ASSOC),
            ];
        }

        usort($result, fn($a, $b) => strcmp((string) $b['Fecha'], (string) $a['Fecha']));

        return $result;
    }

    public function canView(int $requestId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM gcp_dotacionsolicitudes s
            WHERE s.Id = :Id AND (
                s.UsuarioId = :Requester
                OR s.CreadoPorId = :Creator
                OR (s.Estado = 'Pendiente_Jefe' AND s.JefeGestionHumanaId = :Jefe)
                OR s.CoordinadorId = :Coordinador
                OR EXISTS (
                    SELECT 1 FROM gcp_dotacionhistorial h
                    WHERE h.SolicitudId = s.Id AND h.UsuarioId = :HistoryUser
                )
            )
        ");
        $stmt->execute([
            ':Id' => $requestId,
            ':Requester' => $userId,
            ':Creator' => $userId,
            ':Jefe' => $userId,
            ':Coordinador' => $userId,
            ':HistoryUser' => $userId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data, array $items, ?int $approvingJefeId, ?int $humanResourcesManagerId): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO gcp_dotacionsolicitudes
                    (UsuarioId, CreadoPorId, EmpresaId, CedulaExpedidaEn, Estado, NivelActual, JefeGestionHumanaId, CoordinadorId)
                VALUES
                    (:UsuarioId, :CreadoPorId, :EmpresaId, :CedulaExpedidaEn, :Estado, :NivelActual, :JefeId, :CoordinadorId)
            ");
            $stmt->execute([
                ':UsuarioId' => $data['UsuarioId'],
                ':CreadoPorId' => $data['CreadoPorId'] ?? null,
                ':EmpresaId' => $data['EmpresaId'],
                ':CedulaExpedidaEn' => $data['CedulaExpedidaEn'],
                ':Estado' => $approvingJefeId ? 'Pendiente_Jefe' : 'Pendiente_Coordinador',
                ':NivelActual' => $approvingJefeId ? 1 : 2,
                ':JefeId' => $humanResourcesManagerId,
                ':CoordinadorId' => $data['CoordinadorId'],
            ]);
            $id = (int) $this->db->lastInsertId();

            $insertItem = $this->db->prepare('
                INSERT INTO gcp_dotacionitems (SolicitudId, Cantidad, Referencia, Talla)
                VALUES (:SolicitudId, :Cantidad, :Referencia, :Talla)
            ');
            foreach ($items as $item) {
                $insertItem->execute([
                    ':Talla' => $item['Talla'],
                    ':SolicitudId' => $id,
                    ':Cantidad' => $item['Cantidad'],
                    ':Referencia' => $item['Referencia'],
                ]);
            }

            $history = $this->db->prepare("
                INSERT INTO gcp_dotacionhistorial (SolicitudId, UsuarioId, Nivel, Accion)
                VALUES (:SolicitudId, :UsuarioId, :Nivel, 'Creada')
            ");
            $history->execute([
                ':SolicitudId' => $id,
                ':UsuarioId' => $data['CreadoPorId'] ?? $data['UsuarioId'],
                ':Nivel' => $approvingJefeId ? 1 : 2,
            ]);
            $this->db->commit();

            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function decide(int $requestId, int $userId, bool $approve, string $comment): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM gcp_dotacionsolicitudes WHERE Id = :Id FOR UPDATE');
            $stmt->execute([':Id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                $this->db->commit();
                return ['error' => 'not_found'];
            }

            $state = (string) $request['Estado'];
            $isHrStep = $state === 'Pendiente_Jefe' && (int) $request['JefeGestionHumanaId'] === $userId;
            $isCoordinatorStep = $state === 'Pendiente_Coordinador' && (int) $request['CoordinadorId'] === $userId;
            if (!$isHrStep && !$isCoordinatorStep) {
                $this->db->commit();
                return ['error' => 'forbidden'];
            }
            if (!$approve && trim($comment) === '') {
                $this->db->commit();
                return ['error' => 'comment_required'];
            }

            $level = $isHrStep ? 1 : 2;
            $nextState = $approve
                ? ($isHrStep ? 'Pendiente_Coordinador' : 'Aprobada')
                : 'Rechazada';
            $nextLevel = $isHrStep && $approve ? 2 : $level;

            $update = $this->db->prepare('
                UPDATE gcp_dotacionsolicitudes
                SET Estado = :Estado, NivelActual = :Nivel, FechaActualizacion = CURRENT_TIMESTAMP
                WHERE Id = :Id
            ');
            $update->execute([':Estado' => $nextState, ':Nivel' => $nextLevel, ':Id' => $requestId]);
            $history = $this->db->prepare('
                INSERT INTO gcp_dotacionhistorial (SolicitudId, UsuarioId, Nivel, Accion, Comentario)
                VALUES (:SolicitudId, :UsuarioId, :Nivel, :Accion, :Comentario)
            ');
            $history->execute([
                ':SolicitudId' => $requestId,
                ':UsuarioId' => $userId,
                ':Nivel' => $level,
                ':Accion' => $approve ? 'Aprobada' : 'Rechazada',
                ':Comentario' => trim($comment) !== '' ? trim($comment) : null,
            ]);
            $this->db->commit();

            return [
                'Estado' => $nextState,
                'SolicitanteId' => (int) ($request['CreadoPorId'] ?? $request['UsuarioId']),
                'NotificarSolicitante' => !$approve || $nextState === 'Aprobada',
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function markReceived(int $requestId, int $userId): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT UsuarioId, CreadoPorId, CoordinadorId, Estado FROM gcp_dotacionsolicitudes WHERE Id = :Id FOR UPDATE');
            $stmt->execute([':Id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                $this->db->commit();
                return ['error' => 'not_found'];
            }
            if ((int) $request['UsuarioId'] !== $userId && (int) ($request['CreadoPorId'] ?? 0) !== $userId) {
                $this->db->commit();
                return ['error' => 'forbidden'];
            }
            if ($request['Estado'] !== 'Aprobada') {
                $this->db->commit();
                return ['error' => 'invalid_state'];
            }

            $this->db->prepare("
                UPDATE gcp_dotacionsolicitudes
                SET Estado = 'Recibida', FechaActualizacion = CURRENT_TIMESTAMP
                WHERE Id = :Id
            ")->execute([':Id' => $requestId]);
            $this->db->prepare("
                INSERT INTO gcp_dotacionhistorial (SolicitudId, UsuarioId, Nivel, Accion)
                VALUES (:SolicitudId, :UsuarioId, 2, 'Recibida')
            ")->execute([':SolicitudId' => $requestId, ':UsuarioId' => $userId]);

            $this->db->prepare("
                INSERT INTO entregas_dotacion (empresa_id, solicitud_id, cedula, fecha_entrega)
                SELECT s.EmpresaId, s.Id, u.Cedula, CURDATE()
                FROM gcp_dotacionsolicitudes s
                INNER JOIN gcp_usuarios u ON u.Id = s.UsuarioId
                WHERE s.Id = :Id
            ")->execute([':Id' => $requestId]);
            $deliveryId = (int) $this->db->lastInsertId();
            $this->db->prepare('
                INSERT INTO entregas_dotacion_items (entrega_id, cantidad, referencia, talla)
                SELECT :EntregaId, Cantidad, Referencia, Talla FROM gcp_dotacionitems WHERE SolicitudId = :Id
            ')->execute([':EntregaId' => $deliveryId, ':Id' => $requestId]);
            $this->db->commit();

            return ['CoordinadorId' => (int) $request['CoordinadorId']];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function canViewDeliveries(int $userId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM gcp_usuarios WHERE Id = :Id AND Estado = 1 AND CargoId = :Cargo');
        $stmt->execute([':Id' => $userId, ':Cargo' => self::CARGO_COORDINADOR_DOTACION]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Entregas de las empresas vinculadas al usuario, de la mÃ¡s nueva a la mÃ¡s antigua. */
    public function getDeliveries(int $userId, string $search, int $limit, int $offset): array
    {
        $companies = $this->db->prepare('SELECT EmpresaId FROM gcp_usuariosempresas WHERE UsuarioId = :Id');
        $companies->execute([':Id' => $userId]);
        $companyIds = array_map('intval', $companies->fetchAll(PDO::FETCH_COLUMN));

        $where = ['1 = 1'];
        $params = [];
        if ($companyIds) {
            $placeholders = [];
            foreach ($companyIds as $i => $id) {
                $placeholders[] = ":E{$i}";
                $params[":E{$i}"] = $id;
            }
            $where[] = 'e.empresa_id IN (' . implode(',', $placeholders) . ')';
        }
        if ($search !== '') {
            $where[] = "(e.cedula LIKE :Q OR EXISTS (SELECT 1 FROM gcp_usuarios us WHERE us.Cedula = e.cedula AND CONCAT_WS(' ', us.Nombres, us.Apellidos) LIKE :Q2))";
            $params[':Q'] = '%' . preg_replace('/\D+/', '', $search) . '%';
            $params[':Q2'] = '%' . $search . '%';
            if ($params[':Q'] === '%%') {
                $params[':Q'] = '%' . $search . '%';
            }
        }

        $base = "
            FROM entregas_dotacion e
            INNER JOIN gcp_empresas emp ON emp.Id = e.empresa_id
            WHERE " . implode(' AND ', $where);

        $count = $this->db->prepare('SELECT COUNT(*) ' . $base);
        $count->execute($params);

        $stmt = $this->db->prepare("
            SELECT e.id AS Id, e.fecha_entrega AS Fecha, e.cedula AS Cedula, emp.Nombre AS Empresa,
                   (SELECT CONCAT_WS(' ', u.Nombres, u.Apellidos) FROM gcp_usuarios u WHERE u.Cedula = e.cedula ORDER BY u.Estado DESC, u.Id LIMIT 1) AS Empleado
            {$base}
            ORDER BY e.fecha_entrega DESC, e.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = $this->db->prepare('
            SELECT cantidad AS Cantidad, referencia AS Referencia, talla AS Talla
            FROM entregas_dotacion_items WHERE entrega_id = :Id ORDER BY id
        ');
        foreach ($rows as &$row) {
            $items->execute([':Id' => $row['Id']]);
            $row['Articulos'] = $items->fetchAll(PDO::FETCH_ASSOC);
            $row['Empleado'] = trim((string) $row['Empleado']) ?: null;
        }

        return ['Total' => (int) $count->fetchColumn(), 'Entregas' => $rows];
    }

    private function getCompany(int $companyId): ?array
    {
        $stmt = $this->db->prepare('SELECT Id, Nombre, Codigo FROM gcp_empresas WHERE Id = :Id AND Estado = 1');
        $stmt->execute([':Id' => $companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        return $company ?: null;
    }

    private function findCompanyApprover(int $companyId, int $cargoId, bool $allowUnassigned): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.Id, u.Nombres, u.Apellidos, c.Nombre AS Cargo
            FROM gcp_usuarios u
            INNER JOIN gcp_cargos c ON c.Id = u.CargoId
            WHERE u.Estado = 1 AND u.CargoId = :CargoId
              AND (
                    EXISTS (SELECT 1 FROM gcp_usuariosempresas ue WHERE ue.UsuarioId = u.Id AND ue.EmpresaId = :EmpresaId)
                    " . ($allowUnassigned ? "OR NOT EXISTS (SELECT 1 FROM gcp_usuariosempresas ue0 WHERE ue0.UsuarioId = u.Id)" : '') . "
              )
            ORDER BY EXISTS (
                SELECT 1 FROM gcp_usuariosempresas ue2 WHERE ue2.UsuarioId = u.Id AND ue2.EmpresaId = :SortEmpresaId
            ) DESC, u.Id
            LIMIT 1
        ");
        $stmt->execute([
            ':CargoId' => $cargoId,
            ':EmpresaId' => $companyId,
            ':SortEmpresaId' => $companyId,
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function selectSql(): string
    {
        return "
            SELECT s.Id, s.UsuarioId, s.CreadoPorId, s.EmpresaId, e.Nombre AS EmpresaNombre, e.Codigo AS EmpresaCodigo,
                   s.CedulaExpedidaEn, s.Estado, s.NivelActual, s.JefeGestionHumanaId, s.CoordinadorId,
                   s.FechaCreacion, s.FechaActualizacion,
                   u.Nombres AS SolicitanteNombres, u.Apellidos AS SolicitanteApellidos,
                   u.Cedula AS SolicitanteCedula, cargo.Nombre AS SolicitanteCargo,
                   jefe.Nombres AS JefeNombres, jefe.Apellidos AS JefeApellidos, jefeCargo.Nombre AS JefeCargo,
                   CASE
                     WHEN jefe.Id IS NULL THEN NULL
                     WHEN TRIM(COALESCE(jefe.Apellidos, '')) IN ('', '--', '-') THEN (
                        SELECT CONCAT_WS(' ', NULLIF(TRIM(d.first_name), ''), NULLIF(TRIM(d.last_name), ''))
                        FROM directorio_gacela_conecta d
                        WHERE d.cargo = 'Jefe'
                          AND d.area_agencia LIKE '%RRHH%'
                          AND d.empresa LIKE '%Flota%'
                          AND d.first_name LIKE CONCAT(jefe.Nombres, '%')
                        ORDER BY d.id
                        LIMIT 1
                     )
                     ELSE CONCAT_WS(' ', jefe.Nombres, jefe.Apellidos)
                   END AS JefeNombreCompleto,
                   coordinador.Nombres AS CoordinadorNombres, coordinador.Apellidos AS CoordinadorApellidos,
                   coordinadorCargo.Nombre AS CoordinadorCargo,
                   (SELECT COUNT(*) FROM gcp_dotacionitems di WHERE di.SolicitudId = s.Id) AS CantidadArticulos
            FROM gcp_dotacionsolicitudes s
            INNER JOIN gcp_empresas e ON e.Id = s.EmpresaId
            INNER JOIN gcp_usuarios u ON u.Id = s.UsuarioId
            INNER JOIN gcp_cargos cargo ON cargo.Id = u.CargoId
            LEFT JOIN gcp_usuarios jefe ON jefe.Id = s.JefeGestionHumanaId
            LEFT JOIN gcp_cargos jefeCargo ON jefeCargo.Id = jefe.CargoId
            INNER JOIN gcp_usuarios coordinador ON coordinador.Id = s.CoordinadorId
            INNER JOIN gcp_cargos coordinadorCargo ON coordinadorCargo.Id = coordinador.CargoId
        ";
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(strtr($value, [
            'Ã¡' => 'a', 'Ã©' => 'e', 'Ã­' => 'i', 'Ã³' => 'o', 'Ãº' => 'u',
            'Ã' => 'A', 'Ã‰' => 'E', 'Ã' => 'I', 'Ã“' => 'O', 'Ãš' => 'U',
        ]));
    }
}
