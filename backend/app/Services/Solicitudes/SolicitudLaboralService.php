<?php

namespace App\Services\Solicitudes;

use App\Repositories\NotificacionRepository;
use App\Repositories\Solicitudes\SolicitudLaboralRepository;
use DateTimeImmutable;

class SolicitudLaboralService
{
    private SolicitudLaboralRepository $repository;
    private NotificacionRepository $notifications;

    public function __construct()
    {
        $this->repository = new SolicitudLaboralRepository();
        $this->notifications = new NotificacionRepository();
    }

    public function getTypes(): array
    {
        return $this->repository->getTypes();
    }

    public function getUserCompanies(int $userId): array
    {
        return $this->repository->getUserCompanies($userId);
    }

    public function getMine(int $userId): array
    {
        return $this->repository->getMine($userId);
    }

    public function getPending(int $userId, array $roles): array
    {
        return $this->repository->getPending(
            $userId,
            in_array('rrhh', $roles, true),
            in_array('gerencia', $roles, true)
        );
    }

    public function getDetails(int $requestId, int $userId, array $roles): ?array
    {
        $payrollRole = $this->repository->getPayrollRole($userId);
        if (!$this->repository->canView(
            $requestId,
            $userId,
            in_array('rrhh', $roles, true),
            in_array('gerencia', $roles, true),
            $payrollRole !== null
        )) {
            return null;
        }

        $request = $this->repository->getById($requestId);
        if ($request) {
            $request['RolNomina'] = $payrollRole;
            $request['NominaDocumentos'] = $payrollRole !== null
                ? $this->repository->getPayrollDocuments($requestId)
                : [];
        }

        return $request;
    }

    public function getPayrollQueue(int $userId): array
    {
        $role = $this->repository->getPayrollRole($userId);

        return [
            'Rol' => $role,
            'Solicitudes' => $role ? $this->repository->getPayrollQueue($role) : [],
        ];
    }

    public function uploadPayrollDocument(int $requestId, int $userId, array $document): array
    {
        if ($this->repository->getPayrollRole($userId) !== 'aux') {
            return ['success' => false, 'status' => 403, 'message' => 'Solo Aux Gestión Humana puede subir la liquidación.'];
        }

        $ok = $this->repository->transitionPayroll(
            $requestId,
            $userId,
            ['Pendiente_Liquidacion', 'Con_Errores'],
            'Pendiente_Revision_Nomina',
            'Nomina_Cargada',
            null,
            $document
        );
        if (!$ok) {
            return ['success' => false, 'status' => 409, 'message' => 'La solicitud no está esperando una liquidación.'];
        }

        return ['success' => true, 'message' => 'Liquidación enviada al analista de nómina.'];
    }

    public function reviewPayroll(int $requestId, int $userId, bool $approved, string $comment): array
    {
        if ($this->repository->getPayrollRole($userId) !== 'analista') {
            return ['success' => false, 'status' => 403, 'message' => 'Solo el analista de nómina puede revisar la liquidación.'];
        }
        if (!$approved && $comment === '') {
            return ['success' => false, 'status' => 422, 'message' => 'Indica qué errores contiene la liquidación.'];
        }

        $ok = $this->repository->transitionPayroll(
            $requestId,
            $userId,
            ['Pendiente_Revision_Nomina'],
            $approved ? 'Pendiente_Pago' : 'Con_Errores',
            $approved ? 'Nomina_Aprobada' : 'Nomina_Con_Errores',
            $comment
        );
        if (!$ok) {
            return ['success' => false, 'status' => 409, 'message' => 'La liquidación no está pendiente de revisión.'];
        }

        $link = "/solicitudes?id={$requestId}";
        if ($approved) {
            $requesterId = $this->repository->getRequesterId($requestId);
            if ($requesterId) {
                $this->notifications->create(
                    $requesterId,
                    'solicitud_pendiente_pago',
                    'Tu solicitud está pendiente de pago',
                    "La liquidación de tu solicitud #{$requestId} fue aprobada y está pendiente de pago.",
                    $link
                );
            }
        }

        return [
            'success' => true,
            'message' => $approved ? 'Liquidación aprobada; la solicitud quedó pendiente de pago.' : 'Liquidación devuelta al auxiliar para corrección.',
        ];
    }

    public function markPaid(int $requestId, int $userId): array
    {
        if ($this->repository->getPayrollRole($userId) !== 'analista') {
            return ['success' => false, 'status' => 403, 'message' => 'Solo el analista de nómina puede registrar el pago.'];
        }

        if (!$this->repository->transitionPayroll($requestId, $userId, ['Pendiente_Pago'], 'Pagada', 'Nomina_Pagada', null)) {
            return ['success' => false, 'status' => 409, 'message' => 'La solicitud no está pendiente de pago.'];
        }

        $requesterId = $this->repository->getRequesterId($requestId);
        if ($requesterId) {
            $this->notifications->create(
                $requesterId,
                'solicitud_pagada',
                'Tu solicitud fue pagada',
                "El pago de tu solicitud #{$requestId} ya fue realizado.",
                "/solicitudes?id={$requestId}"
            );
        }

        return ['success' => true, 'message' => 'Pago registrado y notificado al solicitante.'];
    }

    public function getPayrollDocument(int $requestId, int $documentId, int $userId): ?array
    {
        if ($this->repository->getPayrollRole($userId) === null) {
            return null;
        }

        return $this->repository->getPayrollDocument($requestId, $documentId);
    }
    public function create(int $userId, array $data, ?array $attachment): array
    {
        $errors = [];
        $typeId = filter_var($data['TipoId'] ?? null, FILTER_VALIDATE_INT);
        $subject = is_string($data['Asunto'] ?? null) ? trim($data['Asunto']) : '';
        $description = is_string($data['Descripcion'] ?? null) ? trim($data['Descripcion']) : '';
        $startDate = $this->normalizeDate($data['FechaInicio'] ?? null);
        $endDate = $this->normalizeDate($data['FechaFin'] ?? null);
        $type = $typeId !== false && $typeId > 0 ? $this->repository->getType($typeId) : null;

        if (!$type) {
            $errors['TipoId'] = ['Selecciona un tipo de solicitud válido.'];
        }
        if ($subject === '' || mb_strlen($subject) > 200) {
            $errors['Asunto'] = ['El asunto es obligatorio y debe tener máximo 200 caracteres.'];
        }
        if ($description === '') {
            $errors['Descripcion'] = ['La descripción es obligatoria.'];
        }
        if (($data['FechaInicio'] ?? '') !== '' && $startDate === null) {
            $errors['FechaInicio'] = ['La fecha de inicio no es válida.'];
        }
        if (($data['FechaFin'] ?? '') !== '' && $endDate === null) {
            $errors['FechaFin'] = ['La fecha de fin no es válida.'];
        }
        if (in_array($type['Codigo'] ?? '', ['vacaciones', 'licencia', 'permiso'], true) && (!$startDate || !$endDate)) {
            $errors['Fechas'] = ['Indica las fechas de inicio y fin para vacaciones, licencias y permisos.'];
        }
        if ($startDate && $endDate && $startDate > $endDate) {
            $errors['Fechas'] = ['La fecha de inicio no puede ser posterior a la fecha de fin.'];
        }
        if ($type && (int) $type['RequiereAdjunto'] === 1 && !$attachment) {
            $errors['Adjunto'] = ['Este tipo de solicitud requiere un archivo adjunto.'];
        }

        $vacation = ['FechaReintegro' => null, 'DiasDinero' => null, 'DiasDisfrute' => null, 'PeriodoPendiente' => null];
        $companyId = null;
        if (($type['Codigo'] ?? '') === 'vacaciones') {
            $rawCompanyId = filter_var($data['EmpresaId'] ?? null, FILTER_VALIDATE_INT);
            $availableCompanies = $this->repository->getUserCompanies($userId);
            if ($rawCompanyId !== false) {
                foreach ($availableCompanies as $company) {
                    if ((int) $company['Id'] === $rawCompanyId) {
                        $companyId = $rawCompanyId;
                        break;
                    }
                }
            }
            if ($companyId === null) {
                $errors['EmpresaId'] = ['Selecciona una empresa asignada a tu usuario para la solicitud.'];
            }
            $returnDate = $this->normalizeDate($data['FechaReintegro'] ?? null);
            $moneyDays = filter_var($data['DiasDinero'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 365]]);
            $enjoyDays = filter_var($data['DiasDisfrute'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 365]]);
            $pending = is_string($data['PeriodoPendiente'] ?? null) ? trim($data['PeriodoPendiente']) : '';

            if (!$returnDate) {
                $errors['FechaReintegro'] = ['Indica la fecha de reintegro al trabajo.'];
            } elseif ($endDate && $returnDate <= $endDate) {
                $errors['FechaReintegro'] = ['La fecha de reintegro debe ser posterior a la fecha de fin.'];
            }
            if ($moneyDays === false || $enjoyDays === false) {
                $errors['Dias'] = ['Indica cuántos días tomarás en dinero y cuántos en disfrute (números enteros, 0 si ninguno).'];
            } elseif ($moneyDays + $enjoyDays < 1) {
                $errors['Dias'] = ['Debes solicitar al menos un día en dinero o en disfrute.'];
            }
            if (mb_strlen($pending) > 255) {
                $errors['PeriodoPendiente'] = ['El periodo pendiente debe tener máximo 255 caracteres.'];
            }

            $vacation = [
                'FechaReintegro' => $returnDate,
                'DiasDinero' => $moneyDays === false ? null : $moneyDays,
                'DiasDisfrute' => $enjoyDays === false ? null : $enjoyDays,
                'PeriodoPendiente' => $pending !== '' ? $pending : null,
            ];
        }

        $managerCargoId = $this->repository->getDirectManagerCargoId($userId);
        if (!$managerCargoId) {
            $errors['CargoJefeDirecto'] = ['Tu usuario no tiene un cargo de jefe directo configurado. Contacta al administrador.'];
        }

        if ($errors) {
            return ['success' => false, 'message' => 'Revisa los datos de la solicitud.', 'errors' => $errors];
        }

        $id = $this->repository->create([
            'UsuarioId' => $userId,
            'AprobadorCargoId' => $managerCargoId,
            'TipoId' => $typeId,
            'Asunto' => $subject,
            'Descripcion' => $description,
            'FechaInicio' => $startDate,
            'FechaFin' => $endDate,
            'EmpresaSolicitudId' => $companyId,
            'Adjunto' => $attachment,
        ] + $vacation);

        return [
            'success' => true,
            'message' => 'Solicitud enviada al jefe directo para su aprobación.',
            'data' => ['Id' => $id],
        ];
    }

    public function decide(int $requestId, int $userId, array $roles, string $decision, string $comment): array
    {
        if (!in_array($decision, ['Aprobada', 'Rechazada'], true)) {
            return ['success' => false, 'status' => 422, 'message' => 'La decisión debe ser aprobar o rechazar.'];
        }

        $result = $this->repository->decide($requestId, $userId, $decision, $comment, $roles);
        if (isset($result['error'])) {
            [$status, $message] = match ($result['error']) {
                'not_found' => [404, 'Solicitud no encontrada.'],
                'forbidden' => [403, 'No estás autorizado para aprobar esta etapa.'],
                'already_decided' => [409, 'La solicitud ya fue procesada en esta etapa.'],
                'rejection_comment_required' => [422, 'Indica el motivo del rechazo.'],
                default => [400, 'No fue posible procesar la decisión.'],
            };

            return ['success' => false, 'status' => $status, 'message' => $message];
        }

        $requesterId = (int) ($result['SolicitanteId'] ?? 0);
        $link = "/solicitudes?id={$requestId}";
        if ($requesterId > 0) {
            if ($decision === 'Rechazada') {
                $this->notifications->create(
                    $requesterId,
                    'solicitud_rechazada',
                    'Tu solicitud fue rechazada',
                    "Solicitud #{$requestId}: " . ($comment !== '' ? $comment : 'revisa el detalle.'),
                    $link
                );
            } elseif ($result['EstadoSolicitud'] === 'Aprobada') {
                $this->notifications->create(
                    $requesterId,
                    'solicitud_aprobada',
                    'Tu solicitud fue aprobada',
                    "Solicitud #{$requestId} completó todas las aprobaciones.",
                    $link
                );
            } else {
                $this->notifications->create(
                    $requesterId,
                    'solicitud_avance',
                    'Tu solicitud avanzó de etapa',
                    "Solicitud #{$requestId}: pasó a la siguiente etapa de aprobación.",
                    $link
                );
            }
        }

        return [
            'success' => true,
            'message' => $decision === 'Rechazada'
                ? 'La solicitud fue rechazada.'
                : ($result['EstadoSolicitud'] === 'Aprobada'
                    ? 'La solicitud completó todas las aprobaciones.'
                    : 'Aprobación registrada; la solicitud pasó a la siguiente etapa.'),
            'data' => $result,
        ];
    }

    public function getAttachment(int $requestId, int $attachmentId, int $userId, array $roles): ?array
    {
        if (!$this->repository->canView(
            $requestId,
            $userId,
            in_array('rrhh', $roles, true),
            in_array('gerencia', $roles, true),
            $this->repository->getPayrollRole($userId) !== null
        )) {
            return null;
        }

        return $this->repository->getAttachment($requestId, $attachmentId);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
