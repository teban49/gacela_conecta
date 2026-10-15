<?php

namespace App\Services\Gestion;

use App\Repositories\Solicitudes\CategoriaRepository;
use App\Repositories\Solicitudes\SolicitudRepository;
use App\Services\Solicitudes\SolicitudService;

class IncidenciaService
{
    private SolicitudRepository $repository;
    private CategoriaRepository $categoryRepository;
    private SolicitudService $ticketService;

    public function __construct()
    {
        $this->repository = new SolicitudRepository();
        $this->categoryRepository = new CategoriaRepository();
        $this->ticketService = new SolicitudService();
    }

    public function getAll(?int $requesterId): array
    {
        return $this->repository->getIncidencias($requesterId);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->getIncidencia($id);
    }

    public function getCategories(): array
    {
        return $this->categoryRepository->getActive();
    }

    public function getAssignableUsers(): array
    {
        return $this->repository->getIncidentAssignableUsers();
    }

    public function getContext(int $userId): array
    {
        return $this->repository->getIncidentContext($userId);
    }

    public function create(int $requesterId, array $data, ?array $attachment): array
    {
        $context = $this->getContext($requesterId);
        $companies = $context['Empresas'];
        if (!$companies) {
            return [
                'success' => false,
                'errors' => ['EmpresaId' => ['Tu usuario no tiene una empresa activa asignada. Contacta a un administrador.']],
                'message' => 'No se puede crear el ticket sin una empresa asociada.',
            ];
        }

        if (count($companies) === 1) {
            $companyId = (int) $companies[0]['Id'];
        } else {
            $companyId = $this->parseInteger($data['EmpresaId'] ?? null);
            $allowedCompanyIds = array_map(static fn (array $company): int => (int) $company['Id'], $companies);
            if ($companyId === false || !in_array($companyId, $allowedCompanyIds, true)) {
                return [
                    'success' => false,
                    'errors' => ['EmpresaId' => ['Selecciona una de las empresas asociadas a tu usuario.']],
                    'message' => 'La empresa seleccionada no es válida.',
                ];
            }
        }

        $data['SolicitanteId'] = $requesterId;
        $data['EmpresaId'] = $companyId;
        $data['Ubicacion'] = $context['Ubicacion'] ?: null;
        $data['Tipo'] = 'incidencia';
        $data['ArchivoAdjunto'] = $attachment['RutaArchivo'] ?? null;
        $data['ArchivoAdjuntoNombre'] = $attachment['NombreArchivo'] ?? null;

        return $this->ticketService->create($data);
    }

    private function parseInteger(mixed $value): int|false
    {
        if (!is_int($value) && !is_string($value)) return false;
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed === false ? false : $parsed;
    }

    public function update(int $id, int $userId, array $data): array
    {
        $errors = [];
        $updates = [];

        if (array_key_exists('Estado', $data)) {
            $allowedStatuses = ['nueva', 'pendiente'];
            if (!is_string($data['Estado']) || !in_array($data['Estado'], $allowedStatuses, true)) {
                $errors['Estado'] = ['Selecciona un estado válido.'];
            } else {
                $updates['Estado'] = $data['Estado'];
            }
        }

        if (array_key_exists('Prioridad', $data)) {
            $allowedPriorities = ['baja', 'media', 'alta', 'critica'];
            if (!is_string($data['Prioridad']) || !in_array($data['Prioridad'], $allowedPriorities, true)) {
                $errors['Prioridad'] = ['Selecciona una prioridad válida.'];
            } else {
                $updates['Prioridad'] = $data['Prioridad'];
            }
        }

        $comment = is_string($data['Comentario'] ?? null) ? trim($data['Comentario']) : '';
        if (mb_strlen($comment) > 5000) {
            $errors['Comentario'] = ['El comentario no puede superar 5000 caracteres.'];
        }
        if ($comment !== '') {
            $updates['Comentario'] = $comment;
        }
        if ($updates === []) {
            $errors['Actualizacion'] = ['Indica un cambio de estado, asignación, prioridad o comentario.'];
        }
        if ($errors) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Revisa los datos de la actualización.'];
        }

        $incident = $this->repository->getIncidencia($id);
        if (!$incident) {
            return ['success' => false, 'status' => 404, 'message' => 'Incidencia no encontrada.'];
        }
        if ($incident['Estado'] === 'cerrada') {
            return ['success' => false, 'status' => 409, 'message' => 'Una incidencia cerrada no se puede reabrir desde esta pantalla.'];
        }
        if (!$this->repository->updateIncidencia($id, $userId, $updates)) {
            return ['success' => false, 'status' => 404, 'message' => 'Incidencia no encontrada.'];
        }

        return ['success' => true, 'message' => 'Ticket actualizado correctamente.'];
    }

    public function closeByRequester(int $id, int $userId, array $data): array
    {
        $allowedRatings = ['muy_satisfecho', 'satisfecho', 'insatisfecho', 'muy_insatisfecho'];
        $survey = [];
        $errors = [];
        foreach ([
            'SatisfaccionTiempoSolucion',
            'SatisfaccionCalidadAtencion',
            'SatisfaccionEficaciaSolucion',
        ] as $field) {
            $value = $data[$field] ?? null;
            if (!is_string($value) || !in_array($value, $allowedRatings, true)) {
                $errors[$field] = ['Selecciona una opción de satisfacción válida.'];
            } else {
                $survey[$field] = $value;
            }
        }
        $comment = is_string($data['ComentarioSatisfaccion'] ?? null)
            ? trim($data['ComentarioSatisfaccion'])
            : '';
        if (mb_strlen($comment) > 5000) {
            $errors['ComentarioSatisfaccion'] = ['Los comentarios no pueden superar 5000 caracteres.'];
        }
        if ($errors) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Completa la encuesta de satisfacción.'];
        }
        $survey['ComentarioSatisfaccion'] = $comment;
        if (!$this->repository->closeIncidenciaByRequester($id, $userId, $survey)) {
            return ['success' => false, 'status' => 409, 'message' => 'Solo puedes cerrar tu propia incidencia cuando está pendiente.'];
        }
        return ['success' => true, 'message' => 'Incidencia cerrada. Gracias por responder la encuesta.'];
    }
}
