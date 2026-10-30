<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private PDO $db;

    // Estado activo por defecto (1 en tinyint)
    public const STATE_ACTIVE = 1;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   a.Nombre as AgenciaNombre,
                   ci.Nombre as AgenciaCiudad,
                   a.Codigo as AgenciaCodigo,
                   car.Nombre as CargoNombre,
                   car.Codigo as CargoCodigo,
                   r.Nombre as RolNombre,
                   GROUP_CONCAT(DISTINCT r.Codigo) as RolesCodigos,
                   GROUP_CONCAT(DISTINCT u.RolId) as Roles,
                   GROUP_CONCAT(DISTINCT e.Nombre SEPARATOR ', ') as EmpresasNombres,
                   GROUP_CONCAT(DISTINCT e.Id) as EmpresasIds
            FROM gcp_usuarios u
            INNER JOIN gcp_agencias a ON u.AgenciaId = a.Id
            INNER JOIN gcp_ciudades ci ON a.CiudadId = ci.Id
            INNER JOIN gcp_cargos car ON u.CargoId = car.Id
            LEFT JOIN gcp_roles r ON u.RolId = r.Id
            LEFT JOIN gcp_usuariosempresas ue ON u.Id = ue.UsuarioId
            LEFT JOIN gcp_empresas e ON ue.EmpresaId = e.Id
            WHERE u.EmailCorporativo = :email AND u.Estado = :estado
            GROUP BY u.Id
        ");
        
        $stmt->execute([
            'email' => $email,
            'estado' => self::STATE_ACTIVE
        ]);

        $user = $stmt->fetch();

        if ($user) {

        $user['Roles'] = !empty($user['Roles']) ? explode(',', $user['Roles']) : [];

        $user['RolesCodigos'] = !empty($user['RolesCodigos'])? explode(',', $user['RolesCodigos']): [];

        $user['EmpresasIds'] = !empty($user['EmpresasIds'])? array_map('intval', explode(',', $user['EmpresasIds'])): [];

        $user['Empresas'] = [];

        if (!empty($user['EmpresasNombres']) && !empty($user['EmpresasIds'])) {

            $empresas = explode(', ', $user['EmpresasNombres']);

            foreach ($empresas as $index => $empresa) {

                $user['Empresas'][] = [
                    'id' => $user['EmpresasIds'][$index] ?? null,
                    'nombre' => trim($empresa)
                ];

            }
        }
    }

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   a.Nombre as AgenciaNombre,
                   ci.Nombre as AgenciaCiudad,
                   a.Codigo as AgenciaCodigo,
                   car.Nombre as CargoNombre,
                   car.Codigo as CargoCodigo,
                   r.Nombre as RolNombre,
                   GROUP_CONCAT(DISTINCT r.Codigo) as RolesCodigos,
                   GROUP_CONCAT(DISTINCT u.RolId) as Roles,
                   GROUP_CONCAT(DISTINCT e.Nombre SEPARATOR ', ') as EmpresasNombres,
                   GROUP_CONCAT(DISTINCT e.Id) as EmpresasIds
            FROM gcp_usuarios u
            INNER JOIN gcp_agencias a ON u.AgenciaId = a.Id
            INNER JOIN gcp_ciudades ci ON a.CiudadId = ci.Id
            INNER JOIN gcp_cargos car ON u.CargoId = car.Id
            LEFT JOIN gcp_roles r ON u.RolId = r.Id
            LEFT JOIN gcp_usuariosempresas ue ON u.Id = ue.UsuarioId
            LEFT JOIN gcp_empresas e ON ue.EmpresaId = e.Id
            WHERE u.Id = :id AND u.Estado = :estado
            GROUP BY u.Id
        ");
        
        $stmt->execute([
            'id' => $id,
            'estado' => self::STATE_ACTIVE
        ]);

        $user = $stmt->fetch();

        if ($user) {
            $user['Roles'] = !empty($user['Roles']) ? explode(',', $user['Roles']) : [];
            $user['RolesCodigos'] = !empty($user['RolesCodigos']) ? explode(',', $user['RolesCodigos']) : [];
            $user['EmpresasIds'] = !empty($user['EmpresasIds']) ? array_map('intval', explode(',', $user['EmpresasIds'])) : [];
        }

        return $user ?: null;
    }

    public function getUserPermissions(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.CodigoUnico
            FROM gcp_permisos p
            INNER JOIN gcp_rolespermisos rp ON p.Id = rp.PermisoId
            INNER JOIN gcp_usuarios u ON rp.RolId = u.RolId
            WHERE u.Id = :userId AND p.Estado = 1
        ");
        
        $stmt->execute(['userId' => $userId]);
        
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $permissions ?: [];
    }

    public function getUserRoles(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.Id, r.Nombre, r.Codigo, r.Descripcion, r.NivelAcceso, r.Estado
            FROM gcp_usuarios u
            INNER JOIN gcp_roles r ON r.Id = u.RolId
            WHERE u.Id = :userId AND r.Estado = 1
        ");
        
        $stmt->execute(['userId' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getOrganizationData(int $userId): array
    {
    $stmt = $this->db->prepare("
        SELECT 
            u.Id as UsuarioId,
            u.Nombres,
            u.Apellidos,
            u.Cedula,
            u.EmailCorporativo,
            u.Telefono,
            a.Nombre as AgenciaNombre,
            ci.Nombre as AgenciaCiudad,
            a.Codigo as AgenciaCodigo,
            r.Nombre as RolNombre,
            car.Nombre as CargoNombre,
            car.Codigo as CargoCodigo,
            GROUP_CONCAT(DISTINCT e.Nombre SEPARATOR ', ') as EmpresasNombres
        FROM gcp_usuarios u
        INNER JOIN gcp_agencias a ON u.AgenciaId = a.Id
        INNER JOIN gcp_ciudades ci ON a.CiudadId = ci.Id
        INNER JOIN gcp_cargos car ON u.CargoId = car.Id
        LEFT JOIN gcp_roles r ON u.RolId = r.Id
        LEFT JOIN gcp_usuariosempresas ue ON u.Id = ue.UsuarioId
        LEFT JOIN gcp_empresas e ON ue.EmpresaId = e.Id
        WHERE u.Id = :userId
        GROUP BY u.Id
    ");
    
    $stmt->execute(['userId' => $userId]);
    
    $organization = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$organization) {
        return [];
    }

    $roleStmt = $this->db->prepare("
        SELECT r.Codigo FROM gcp_usuarios u INNER JOIN gcp_roles r ON r.Id = u.RolId WHERE u.Id = :userId
    ");
    $roleStmt->execute(['userId' => $userId]);
    $seesAll = in_array($roleStmt->fetchColumn(), ['admin', 'directivos'], true);

    $companiesStmt = $this->db->prepare($seesAll ? "
        SELECT e.Id, e.Nombre FROM gcp_empresas e WHERE e.Estado = 1 AND :userId > 0 ORDER BY e.Nombre ASC
    " : "
        SELECT e.Id, e.Nombre
        FROM gcp_usuariosempresas ue
        INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId
        WHERE ue.UsuarioId = :userId AND e.Estado = 1
        ORDER BY e.Nombre ASC
    ");
    $companiesStmt->execute(['userId' => $userId]);
    $organization['Empresas'] = $companiesStmt->fetchAll(PDO::FETCH_ASSOC);

    return $organization;
    }
}