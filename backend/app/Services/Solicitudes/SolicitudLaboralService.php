<?php

namespace App\Services\Solicitudes;

use App\Repositories\Solicitudes\SolicitudLaboralRepository;
use DateTimeImmutable;

class SolicitudLaboralService
{
    private SolicitudLaboralRepository $repository;

    public function __construct()
    {
        $this->repository = new SolicitudLaboralRepository();
    }

    public function getTypes(): array
    {
        return $this->repository->getTypes();
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
        if (!$this->repository->canView(
            $requestId,
            $userId,
            in_array('rrhh', $roles, true),
            in_array('gerencia', $roles, true)
        )) {
            return null;
        }

        return $this->repository->getById($requestId);
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
            'Adjunto' => $attachment,
        ]);

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
            in_array('gerencia', $roles, true)
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
