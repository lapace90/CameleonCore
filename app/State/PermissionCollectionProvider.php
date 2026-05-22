<?php
// app/State/PermissionCollectionProvider.php - CORRECTION

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


class PermissionCollectionProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {

        $request = $context['request'] ?? request();

        // Essaie d’abord Sanctum (car tes tests font actingAs(..., 'sanctum'))
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', "Token d'authentification requis");
        }
        // LOGIQUE MÉTIER : Groupement côté serveur
        $permissions = Permission::with('roles')
            ->select([
                'permissions.*',
                // Calculs pré-faits côté serveur
                DB::raw('(SELECT COUNT(*) FROM permission_role WHERE permission_id = permissions.id) as roles_count'),
                DB::raw('(SELECT COUNT(DISTINCT u.id) FROM users u 
                         JOIN role_user ru ON u.id = ru.user_id 
                         JOIN permission_role pr ON ru.role_id = pr.role_id 
                         WHERE pr.permission_id = permissions.id) as users_count')
            ])
            ->get();

        // GROUPEMENT par catégories côté serveur - UTILISER LE CHAMP CATEGORY
        $grouped = $permissions->groupBy(function ($permission) {
            // UTILISER LE CHAMP category DE LA BASE (avec fallback automatique)
            return $permission->category; // L'accessor gérera le fallback
        });

        // FORMATAGE pour l'UI côté serveur
        $categories = [];
        foreach ($grouped as $categoryKey => $categoryPermissions) {
            $categories[] = [
                'key' => $categoryKey,
                'name' => $this->getCategoryDisplayName($categoryKey),
                'description' => $this->getCategoryDescription($categoryKey),
                'icon' => $this->getCategoryIcon($categoryKey),
                'color' => $this->getCategoryColor($categoryKey),
                'permissions' => $categoryPermissions->map(function ($perm) {
                    return [
                        'id' => $perm->id,
                        'name' => $perm->name,
                        'action' => $perm->action,
                        'category' => $perm->category, // Inclure la catégorie réelle
                        // Classes CSS pré-calculées
                        'badge_class' => $this->getActionBadgeClass($perm->action),
                        'action_label' => $this->getActionLabel($perm->action),
                        // États métier calculés
                        'is_critical' => $this->isCriticalPermission($perm->action),
                        'is_system' => $this->isSystemPermission($perm->action),
                        'requires_confirmation' => $this->requiresConfirmation($perm->action),
                        // Statistiques pré-calculées
                        'roles_count' => $perm->roles_count,
                        'users_count' => $perm->users_count,
                        // Usage status pour l'UI
                        'usage_status' => $this->getUsageStatus($perm->roles_count, $perm->users_count),
                        // Relations pour l'UI
                        'roles' => $perm->roles->map(function ($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->name,
                                'slug' => $role->slug
                            ];
                        })->toArray()
                    ];
                })->toArray(),
                // Statistiques de catégorie
                'total_permissions' => $categoryPermissions->count(),
                'used_permissions' => $categoryPermissions->where('roles_count', '>', 0)->count(),
                'critical_permissions' => $categoryPermissions->filter(function ($perm) {
                    return $this->isCriticalPermission($perm->action);
                })->count()
            ];
        }

        // TRI des catégories par priorité UI
        usort($categories, function ($a, $b) {
            $priority = [
                'system' => 1,
                'users' => 2,
                'accommodations' => 3,
                'activities' => 4,
                'bookings' => 5,
                'reception' => 6,
                'customers' => 7,
                'restaurant' => 8,
                'finance' => 9,
                'analytics' => 10,
                'communication' => 11,
                'other' => 99
            ];

            $priorityA = $priority[$a['key']] ?? 50;
            $priorityB = $priority[$b['key']] ?? 50;

            return $priorityA - $priorityB;
        });

        // STATS GLOBALES
        $stats = $this->calculateGlobalStats($permissions);
        $stats['total_categories'] = count($categories);

        return [
            'categories' => $categories,
            'stats' => $stats,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'total_count' => $permissions->count(),
                'grouped_count' => count($categories)
            ]
        ];
    }

    // ===========================
    // MÉTHODES UTILITAIRES (gardées identiques)
    // ===========================

    private function getCategoryDisplayName(string $key): string
    {
        return match ($key) {
            'system' => 'Administration Système',
            'users' => 'Gestion Utilisateurs',
            'accommodations' => 'Hébergements',
            'activities' => 'Activités',
            'bookings' => 'Réservations',
            'reception' => 'Réception',
            'customers' => 'Clients',
            'restaurant' => 'Restaurant',
            'finance' => 'Finance',
            'analytics' => 'Analyses',
            'communication' => 'Communication',
            'other' => 'Autres'
        };
    }

    private function getCategoryDescription(string $key): string
    {
        return match ($key) {
            'system' => 'Permissions d\'administration système et maintenance',
            'users' => 'Gestion des utilisateurs, rôles et permissions',
            'accommodations' => 'Gestion des hébergements et disponibilités',
            'activities' => 'Gestion des activités proposées',
            'bookings' => 'Système de réservations et planning',
            'reception' => 'Accueil et gestion quotidienne',
            'customers' => 'Gestion de la clientèle',
            'restaurant' => 'Gestion du restaurant et des commandes',
            'finance' => 'Gestion financière et comptabilité',
            'analytics' => 'Tableaux de bord et rapports',
            'communication' => 'Messagerie et notifications',
            'other' => 'Permissions diverses'
        };
    }

    private function getCategoryIcon(string $key): string
    {
        return match ($key) {
            'system' => 'settings',
            'users' => 'users',
            'accommodations' => 'home',
            'activities' => 'mountain',
            'bookings' => 'calendar-check',
            'reception' => 'bell',
            'customers' => 'users',
            'restaurant' => 'utensils',
            'finance' => 'coins',
            'analytics' => 'trending-up',
            'communication' => 'message-circle',
            'other' => 'ellipsis'
        };
    }

    private function getCategoryColor(string $key): string
    {
        return match ($key) {
            'system' => 'red',
            'users' => 'blue',
            'accommodations' => 'green',
            'activities' => 'orange',
            'bookings' => 'teal',
            'reception' => 'purple',
            'customers' => 'yellow',
            'restaurant' => 'emerald',
            'finance' => 'pink',
            'analytics' => 'cyan',
            'communication' => 'indigo',
            'other' => 'gray'
        };
    }

    // ... (autres méthodes utilitaires restent identiques)

    private function getActionBadgeClass(string $action): string
    {
        if (str_contains($action, 'delete') || str_contains($action, 'remove')) {
            return 'badge-danger';
        }
        if (str_contains($action, 'create') || str_contains($action, 'add')) {
            return 'badge-success';
        }
        if (str_contains($action, 'update') || str_contains($action, 'edit')) {
            return 'badge-warning';
        }
        if (str_contains($action, 'read') || str_contains($action, 'view')) {
            return 'badge-info';
        }
        return 'badge-secondary';
    }

    private function getActionLabel(string $action): string
    {
        return ucwords(str_replace('-', ' ', $action));
    }

    private function isCriticalPermission(string $action): bool
    {
        $critical = ['system-admin', 'delete-users', 'manage-permissions', 'admin-access'];
        return in_array($action, $critical);
    }

    private function isSystemPermission(string $action): bool
    {
        return str_starts_with($action, 'system-') || $action === 'admin-access';
    }

    private function requiresConfirmation(string $action): bool
    {
        return str_contains($action, 'delete') || str_contains($action, 'remove');
    }

    private function getUsageStatus(int $rolesCount, int $usersCount): string
    {
        if ($rolesCount === 0) return 'unused';
        if ($rolesCount <= 2) return 'limited';
        if ($rolesCount <= 5) return 'moderate';
        return 'widespread';
    }

    private function calculateGlobalStats($permissions): array
    {
        $totalPermissions = $permissions->count();
        $usedPermissions = $permissions->where('roles_count', '>', 0)->count();
        $criticalPermissions = $permissions->filter(function ($perm) {
            return $this->isCriticalPermission($perm->action);
        })->count();
        $systemPermissions = $permissions->filter(function ($perm) {
            return $this->isSystemPermission($perm->action);
        })->count();

        return [
            'total_permissions' => $totalPermissions,
            'used_permissions' => $usedPermissions,
            'unused_permissions' => $totalPermissions - $usedPermissions,
            'critical_permissions' => $criticalPermissions,
            'system_permissions' => $systemPermissions,
            'usage_percentage' => $totalPermissions > 0 ? round(($usedPermissions / $totalPermissions) * 100) : 0
        ];
    }
}
