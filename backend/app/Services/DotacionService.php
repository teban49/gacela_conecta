<?php

namespace App\Services;

use App\Repositories\BeneficiaryRepository;
use App\Repositories\DotacionRepository;
use App\Repositories\NotificacionRepository;

class DotacionService
{
    private DotacionRepository $repository;
    private NotificacionRepository $notifications;

    public function __construct()
    {
        $this->repository = new DotacionRepository();
        $this->notifications = new NotificacionRepository();
    }

    public function getDashboard(int $userId, bool $isAdmin = false): array
    {
        return [
            'Opciones' => $this->repository->getCreateOptions($userId),
            'Beneficiarios' => (new BeneficiaryRepository())->getTaquilleros($userId),
            'MisSolicitudes' => $this->repository->getMine($userId),
            'Pendientes' => $this->repository->getPending($userId),
            'PuedeVerEntregas' => $this->repository->canViewDeliveries($userId, $isAdmin),
        ];
    }

    public function getDeliveries(int $userId, bool $isAdmin, string $search, int $page): ?array
    {
        if (!$this->repository->canViewDeliveries($userId, $isAdmin)) {
            return null;
        }
        $limit = 50;

        return $this->repository->getDeliveries($userId, mb_substr(trim($search), 0, 80), $limit, max(0, $page - 1) * $limit)
            + ['PorPagina' => $limit];
    }

    public function getDetails(int $requestId, int $userId): ?array
    {
        if (!$this->repository->canView($requestId, $userId)) {
            return null;
        }
        $request = $this->repository->getById($requestId);
        if ($request) {
            $request['EsSolicitante'] = (int) $request['UsuarioId'] === $userId || (int) ($request['CreadoPorId'] ?? 0) === $userId;
            $request['EsCoordinador'] = (int) $request['CoordinadorId'] === $userId;
            $request['EsJefeGestionHumana'] = (int) $request['JefeGestionHumanaId'] === $userId;
            $request['HistorialEntregas'] = ($request['EsCoordinador'] || $request['EsJefeGestionHumana'])
                ? $this->repository->getDeliveryHistory((string) $request['SolicitanteCedula'], $requestId)
                : [];
        }

        return $request;
    }

    public function create(int $userId, array $data): array
    {
        $creatorId = null;
        $beneficiaryId = filter_var($data['BeneficiarioId'] ?? null, FILTER_VALIDATE_INT);
        if ($beneficiaryId !== false && $beneficiaryId !== null && $beneficiaryId !== $userId) {
            if (!(new BeneficiaryRepository())->canRequestFor($userId, $beneficiaryId)) {
                return ['success' => false, 'status' => 403, 'message' => 'No puedes registrar dotación para ese empleado.'];
            }
            $creatorId = $userId;
            $userId = $beneficiaryId;
        }

        $options = $this->repository->getCreateOptions($userId);
        $errors = [];
        $companyId = filter_var($data['EmpresaId'] ?? null, FILTER_VALIDATE_INT);
        $company = null;
        foreach ($options['Empresas'] as $candidate) {
            if ($companyId !== false && (int) $candidate['Id'] === $companyId) {
                $company = $candidate;
                break;
            }
        }
        if (!$company) {
            $errors['EmpresaId'] = ['Selecciona una empresa asignada a tu usuario.'];
        }

        $place = is_string($data['CedulaExpedidaEn'] ?? null) ? trim($data['CedulaExpedidaEn']) : '';
        if ($place === '' || mb_strlen($place) > 120) {
            $errors['CedulaExpedidaEn'] = ['Indica el lugar de expedición de la cédula (máximo 120 caracteres).'];
        }
        if (trim((string) ($options['Cedula'] ?? '')) === '') {
            $errors['Cedula'] = ['Tu cédula no está registrada. Solicita a Gestión Humana que actualice tus datos.'];
        }

        $itemsInput = $data['Articulos'] ?? null;
        $items = [];
        if (!is_array($itemsInput) || count($itemsInput) < 1 || count($itemsInput) > 20) {
            $errors['Articulos'] = ['Agrega entre 1 y 20 artículos a la solicitud.'];
        } else {
            foreach ($itemsInput as $index => $item) {
                if (!is_array($item)) {
                    $errors['Articulos'] = ['Los datos de cada artículo no son válidos.'];
                    break;
                }
                $quantity = filter_var($item['Cantidad'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1, 'max_range' => 100],
                ]);
                $reference = is_string($item['Referencia'] ?? null) ? trim($item['Referencia']) : '';
                $size = is_string($item['Talla'] ?? null) ? trim($item['Talla']) : '';
                if ($quantity === false || $reference === '' || mb_strlen($reference) > 150 || $size === '' || mb_strlen($size) > 20) {
                    $errors["Articulos.{$index}"] = ['Cada artículo requiere cantidad (1 a 100), referencia (máximo 150 caracteres) y talla (máximo 20 caracteres).'];
                    continue;
                }
                $items[] = ['Cantidad' => $quantity, 'Referencia' => $reference, 'Talla' => $size];
            }
        }

        if ($errors) {
            return ['success' => false, 'status' => 422, 'message' => 'Revisa los datos de la solicitud.', 'errors' => $errors];
        }

        $approvers = $this->repository->getApprovers((int) $company['Id']);
        if (!$approvers['Coordinador']) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'No hay un usuario activo con cargo Coordinador Dotación asignado a esta empresa. Configúralo en Seguridad antes de crear solicitudes.',
            ];
        }
        if (!$approvers['JefeGestionHumana']) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'No hay un usuario activo con cargo Jefe Gestión Humana para firmar la autorización de dotación.',
            ];
        }

        $humanResourcesManagerId = (int) $approvers['JefeGestionHumana']['Id'];
        $jefeId = $approvers['EsFlota'] ? $humanResourcesManagerId : null;
        $id = $this->repository->create([
            'UsuarioId' => $userId,
            'CreadoPorId' => $creatorId,
            'CedulaExpedidaEn' => $place,
            'CoordinadorId' => (int) $approvers['Coordinador']['Id'],
        ], $items, $jefeId, $humanResourcesManagerId);

        return [
            'success' => true,
            'message' => 'Solicitud de dotación enviada para aprobación.',
            'data' => ['Id' => $id],
        ];
    }

    public function decide(int $requestId, int $userId, bool $approve, string $comment): array
    {
        $result = $this->repository->decide($requestId, $userId, $approve, $comment);
        if (isset($result['error'])) {
            [$status, $message] = match ($result['error']) {
                'not_found' => [404, 'Solicitud de dotación no encontrada.'],
                'forbidden' => [403, 'No estás asignado para aprobar esta etapa.'],
                'comment_required' => [422, 'Indica el motivo de la denegación.'],
                default => [409, 'La solicitud ya fue procesada.'],
            };
            return ['success' => false, 'status' => $status, 'message' => $message];
        }

        if ($result['NotificarSolicitante']) {
            $approved = $result['Estado'] === 'Aprobada';
            $this->notifications->create(
                (int) $result['SolicitanteId'],
                $approved ? 'dotacion_aprobada' : 'dotacion_denegada',
                $approved ? 'Tu solicitud de dotación fue aceptada' : 'Tu solicitud de dotación fue denegada',
                $approved
                    ? "La solicitud #{$requestId} fue aceptada. Ya puedes generar la autorización."
                    : "La solicitud #{$requestId} fue denegada. " . ($comment !== '' ? $comment : ''),
                "/dotacion?id={$requestId}"
            );
        }

        return [
            'success' => true,
            'message' => match ($result['Estado']) {
                'Pendiente_Coordinador' => 'Aprobación registrada; se notificó al Coordinador de Dotación.',
                'Aprobada' => 'Solicitud aceptada. El solicitante ya puede generar la autorización.',
                default => 'Solicitud denegada y notificada al solicitante.',
            },
            'data' => ['Estado' => $result['Estado']],
        ];
    }

    public function markReceived(int $requestId, int $userId): array
    {
        $result = $this->repository->markReceived($requestId, $userId);
        if (isset($result['error'])) {
            [$status, $message] = match ($result['error']) {
                'not_found' => [404, 'Solicitud de dotación no encontrada.'],
                'forbidden' => [403, 'Solo el solicitante puede confirmar la recepción.'],
                default => [409, 'La solicitud no está aprobada o ya se confirmó su recepción.'],
            };
            return ['success' => false, 'status' => $status, 'message' => $message];
        }

        $this->notifications->create(
            (int) $result['CoordinadorId'],
            'dotacion_recibida',
            'Dotación recibida: revisa el formato de entrega',
            "El solicitante confirmó la recepción de la solicitud #{$requestId}.",
            "/dotacion?id={$requestId}"
        );

        return ['success' => true, 'message' => 'Recepción confirmada. El Coordinador de Dotación ya puede consultar el formato de entrega.'];
    }
}
