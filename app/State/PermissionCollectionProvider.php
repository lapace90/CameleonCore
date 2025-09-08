<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionCollectionProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $context['request'] ?? request();
        
        // 🧠 LOGIQUE MÉTIER : Groupement côté serveur
        $permissions = Permission::with('roles')
            ->select([
                'permissions.*',
                // ✅ Calculs pré-faits côté serveur
                DB::raw('(SELECT COUNT(*) FROM permission_role WHERE permission_id = permissions.id) as roles_count'),
                DB::raw('(SELECT COUNT(DISTINCT u.id) FROM users u 
                         JOIN role_user ru ON u.id = ru.user_id 
                         JOIN permission_role pr ON ru.role_id = pr.role_id 
                         WHERE pr.permission_id = permissions.id) as users_count')
            ])
            ->get();

        // 🧠 GROUPEMENT par catégories côté serveur
        $grouped = $permissions->groupBy(function($permission) {
            return $this->getCategory($permission->action);
        });

        // 🧠 FORMATAGE pour l'UI côté serveur
        $categories = [];
        foreach ($grouped as $categoryKey => $categoryPermissions) {
            $categories[] = [
                'key' => $categoryKey,
                'name' => $this->getCategoryDisplayName($categoryKey),
                'description' => $this->getCategoryDescription($categoryKey),
                'icon' => $this->getCategoryIcon($categoryKey),
                'color' => $this->getCategoryColor($categoryKey),
                'permissions' => $categoryPermissions->map(function($perm) {
                    return [
                        'id' => $perm->id,
                        'name' => $perm->name,
                        'action' => $perm->action,
                        // ✅ Classes CSS pré-calculées
                        'badge_class' => $this->getActionBadgeClass($perm->action),
                        'action_label' => $this->getActionLabel($perm->action),
                        // ✅ États métier calculés
                        'is_critical' => $this->isCriticalPermission($perm->action),
                        'is_system' => $this->isSystemPermission($perm->action),
                        'requires_confirmation' => $this->requiresConfirmation($perm->action),
                        // ✅ Statistiques pré-calculées
                        'roles_count' => $perm->roles_count,
                        'users_count' => $perm->users_count,
                        'usage_status' => $this->getUsageStatus($perm->roles_count, $perm->users_count)
                    ];
                })->toArray()
            ];
        }

        // 🧠 STATS globales calculées côté serveur
        $stats = $this->calculateGlobalStats($permissions);

        return [
            'categories' => $categories,
            'stats' => $stats,
            'meta' => [
                'total_permissions' => $permissions->count(),
                'total_categories' => count($categories)
            ]
        ];
    }

    private function getCategory(string $action): string
    {
        // 🧠 LOGIQUE MÉTIER centralisée
        $categories = [
            'system' => [
                'system-admin', 'admin-access', 'maintenance-mode', 
                'clear-cache', 'view-logs', 'backup-manage'
            ],
            'users' => [
                'users-read', 'users-create', 'users-update', 'users-delete', 
                'roles-manage', 'permissions-assign'
            ],
            'accommodations' => [
                'accommodations-read', 'accommodations-manage', 'rooms-status',
                'rooms-assign', 'occupancy-manage'
            ],
            'activities' => [
                'activities-read', 'activities-manage', 'activities-book',
                'activities-schedule'
            ],
            'bookings' => [
                'bookings-read-all', 'bookings-create', 'bookings-update', 
                'bookings-cancel', 'bookings-confirm', 'planning-manage'
            ],
            'reception' => [
                'checkin', 'checkout', 'arrivals-today', 'departures-today', 
                'keys-manage', 'reception-desk'
            ],
            'customers' => [
                'customers-read', 'customers-create', 'customers-update', 
                'customers-history', 'customers-export'
            ],
            'restaurant' => [
                'menus-read', 'menus-manage', 'dishes-manage', 
                'orders-take', 'orders-manage', 'kitchen-access'
            ],
            'finance' => [
                'payments-read', 'payments-collect', 'invoicing-manage', 
                'finance-stats', 'accounting-export'
            ],
            'analytics' => [
                'dashboard-view', 'occupancy-stats', 'revenue-reports', 
                'data-export', 'analytics-advanced'
            ],
            'communication' => [
                'messages-customers', 'notifications-team', 'emails-send',
                'sms-send'
            ]
        ];

        foreach ($categories as $category => $actions) {
            if (in_array($action, $actions)) {
                return $category;
            }
        }

        return 'other';
    }

    private function getCategoryDisplayName(string $key): string 
    {
        return match($key) {
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
        return match($key) {
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
        return match($key) {
            'system' => 'fas fa-cogs',
            'users' => 'fas fa-users',
            'accommodations' => 'fas fa-bed',
            'activities' => 'fas fa-hiking',
            'bookings' => 'fas fa-calendar-alt',
            'reception' => 'fas fa-concierge-bell',
            'customers' => 'fas fa-user-friends',
            'restaurant' => 'fas fa-utensils',
            'finance' => 'fas fa-euro-sign',
            'analytics' => 'fas fa-chart-line',
            'communication' => 'fas fa-comments',
            'other' => 'fas fa-ellipsis-h'
        };
    }

    private function getCategoryColor(string $key): string
    {
        return match($key) {
            'system' => 'red',
            'users' => 'blue',
            'accommodations' => 'purple',
            'activities' => 'green',
            'bookings' => 'orange',
            'reception' => 'teal',
            'customers' => 'indigo',
            'restaurant' => 'yellow',
            'finance' => 'emerald',
            'analytics' => 'pink',
            'communication' => 'cyan',
            'other' => 'gray'
        };
    }

    private function getActionBadgeClass(string $action): string
    {
        return match(true) {
            str_contains($action, 'delete') || str_contains($action, 'cancel') => 'badge-danger',
            str_contains($action, 'create') || str_contains($action, 'add') => 'badge-success',
            str_contains($action, 'update') || str_contains($action, 'manage') => 'badge-warning',
            str_contains($action, 'read') || str_contains($action, 'view') => 'badge-info',
            str_contains($action, 'admin') || str_contains($action, 'system') => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    private function getActionLabel(string $action): string
    {
        return match(true) {
            str_contains($action, 'read') => 'Lecture',
            str_contains($action, 'create') => 'Création',
            str_contains($action, 'update') => 'Modification',
            str_contains($action, 'delete') => 'Suppression',
            str_contains($action, 'manage') => 'Gestion',
            str_contains($action, 'admin') => 'Administration',
            default => 'Action'
        };
    }

    private function isCriticalPermission(string $action): bool
    {
        $criticalActions = [
            'system-admin', 'users-delete', 'backup-manage',
            'maintenance-mode', 'finance-stats', 'data-export'
        ];
        
        return in_array($action, $criticalActions);
    }

    private function isSystemPermission(string $action): bool
    {
        return str_starts_with($action, 'system-') || 
               str_contains($action, 'admin') ||
               str_contains($action, 'backup');
    }

    private function requiresConfirmation(string $action): bool
    {
        return str_contains($action, 'delete') ||
               str_contains($action, 'admin') ||
               $action === 'maintenance-mode';
    }

    private function getUsageStatus(int $rolesCount, int $usersCount): string
    {
        if ($usersCount === 0) return 'unused';
        if ($usersCount < 3) return 'limited';
        if ($usersCount < 10) return 'moderate';
        return 'widespread';
    }

    private function calculateGlobalStats($permissions): array
    {
        $totalPermissions = $permissions->count();
        $usedPermissions = $permissions->where('roles_count', '>', 0)->count();
        $criticalPermissions = $permissions->filter(function($perm) {
            return $this->isCriticalPermission($perm->action);
        })->count();
        $systemPermissions = $permissions->filter(function($perm) {
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