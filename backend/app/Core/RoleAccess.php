<?php

namespace App\Core;

/**
 * Reglas de acceso por rol para la intranet (solo lectura salvo el administrador).
 */
class RoleAccess
{
    /** Roles que consultan las empresas de todo el grupo. */
    private const ALL_COMPANIES_ROLES = ['admin', 'directivos'];

    /** Roles que ven todas las áreas de Calidad. */
    private const ALL_AREAS_ROLES = ['admin', 'gerencia', 'directivos'];

    /** Áreas de Calidad visibles por rol; gestion-humana la ven todos. */
    private const AREAS_BY_ROLE = [
        'operaciones' => ['mantenimiento', 'torre-control'],
        'vinculaciones' => ['vinculaciones'],
        'juridica' => [],
        'tesoreria' => ['compras-proveedores'],
        'contabilidad' => [],
        'rrhh' => ['calidad'],
        'usuario' => ['servicio-cliente'],
        'usuario_pruebas' => ['servicio-cliente'],
    ];

    public static function roleCodes(?array $user): array
    {
        return array_values(array_filter(array_map(
            static fn ($role) => is_array($role) ? ($role['Codigo'] ?? '') : $role,
            $user['roles'] ?? []
        )));
    }

    public static function isAdmin(?array $user): bool
    {
        return in_array('admin', self::roleCodes($user), true);
    }

    public static function seesAllCompanies(?array $user): bool
    {
        return (bool) array_intersect(self::ALL_COMPANIES_ROLES, self::roleCodes($user));
    }

    /** @return string[]|null null = todas las áreas */
    public static function calidadAreas(?array $user): ?array
    {
        $roles = self::roleCodes($user);
        if (array_intersect(self::ALL_AREAS_ROLES, $roles)) {
            return null;
        }

        $areas = ['gestion-humana'];
        foreach ($roles as $role) {
            $areas = array_merge($areas, self::AREAS_BY_ROLE[$role] ?? []);
        }
        return array_values(array_unique($areas));
    }

    public static function canViewCalidadArea(?array $user, string $area): bool
    {
        $areas = self::calidadAreas($user);
        return $areas === null || in_array($area, $areas, true);
    }
}
