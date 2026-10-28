<?php

namespace App\Controllers\Intranet;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\Intranet\OrganigramaPuestoRepository;

class OrganigramaPuestoController
{
    private const NIVELES = ['nivel1', 'nivel2', 'gerencia', 'area'];
    private const AREAS = ['calidad', 'sst', 'operaciones', 'sistemas', 'vinculaciones', 'juridica', 'tesoreria', 'contabilidad', 'gestion-humana', 'rrhh'];

    private OrganigramaPuestoRepository $repository;

    public function __construct()
    {
        $this->repository = new OrganigramaPuestoRepository();
    }

    public function index(): void
    {
        $user = Auth::user();
        $empresas = $user && !\App\Core\RoleAccess::seesAllCompanies($user)
            ? $this->repository->getCompaniesForUser((int) ($user['id'] ?? 0))
            : $this->repository->getCompanies();

        Response::success([
            'puestos' => $this->repository->getActive(),
            'empresas' => $empresas,
        ], 'Organigrama obtenido correctamente.');
    }

    public function store(): void
    {
        $this->authorizeAdmin();
        $data = Request::json();
        $nivel = is_string($data['Nivel'] ?? null) ? $data['Nivel'] : '';
        $area = is_string($data['Area'] ?? null) && $data['Area'] !== '' ? $data['Area'] : null;
        $empresaId = ($data['EmpresaId'] ?? null) === null || $data['EmpresaId'] === ''
            ? null
            : filter_var($data['EmpresaId'], FILTER_VALIDATE_INT);
        [$nombre, $cargo, $errors] = $this->validateText($data);

        if (!in_array($nivel, self::NIVELES, true)) {
            $errors['Nivel'] = ['Nivel inválido.'];
        }
        if ($nivel === 'area' && !in_array($area, self::AREAS, true)) {
            $errors['Area'] = ['Selecciona un área válida.'];
        }
        if ($nivel !== 'area') {
            $area = null;
        }
        if ($nivel === 'gerencia' && ($empresaId === null || $empresaId === false)) {
            $errors['EmpresaId'] = ['La gerencia pertenece a una empresa.'];
        }
        if ($nivel === 'area' && ($empresaId === null || $empresaId === false)) {
            $errors['EmpresaId'] = ['El área pertenece a una empresa.'];
        }
        if ($empresaId === false || ($empresaId !== null && !$this->repository->companyExists($empresaId))) {
            $errors['EmpresaId'] = ['Empresa inválida.'];
        }
        if ($errors) {
            Response::validationError($errors, 'Revisa los datos del puesto.');
        }

        $fila = filter_var($data['Fila'] ?? null, FILTER_VALIDATE_INT);
        $id = $this->repository->create([
            'EmpresaId' => $empresaId,
            'Nivel' => $nivel,
            'Area' => $area,
            'Fila' => $fila !== false && $fila >= 1 ? $fila : null,
            'Nombre' => $nombre,
            'Cargo' => $cargo,
        ]);
        Response::created(['Id' => $id], 'Puesto agregado al organigrama.');
    }

    public function update(int $id): void
    {
        $this->authorizeAdmin();
        [$nombre, $cargo, $errors] = $this->validateText(Request::json());
        if ($errors) {
            Response::validationError($errors, 'Revisa los datos del puesto.');
        }
        if (!$this->repository->findById($id)) {
            Response::notFound('Puesto no encontrado.');
        }
        $this->repository->update($id, $nombre, $cargo);
        Response::success(null, 'Puesto actualizado.');
    }

    public function destroy(int $id): void
    {
        $this->authorizeAdmin();
        if (!$this->repository->delete($id)) {
            Response::notFound('Puesto no encontrado.');
        }
        Response::success(null, 'Puesto eliminado del organigrama.');
    }

    private function validateText(array $data): array
    {
        $errors = [];
        $nombre = is_string($data['Nombre'] ?? null) ? trim($data['Nombre']) : '';
        $cargo = is_string($data['Cargo'] ?? null) ? trim($data['Cargo']) : '';
        if ($nombre === '' || mb_strlen($nombre) > 150) {
            $errors['Nombre'] = ['El nombre es obligatorio (máx. 150 caracteres).'];
        }
        if (mb_strlen($cargo) > 150) {
            $errors['Cargo'] = ['El cargo no puede superar 150 caracteres.'];
        }
        return [$nombre, $cargo === '' ? null : $cargo, $errors];
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        $roles = array_map(
            static fn ($role) => is_array($role) ? ($role['Codigo'] ?? '') : $role,
            $user['roles'] ?? []
        );
        if (!$user || !in_array('admin', $roles, true)) {
            Response::forbidden('Solo el administrador puede editar el organigrama.');
        }
    }
}
