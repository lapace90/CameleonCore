<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Permission;
use App\Models\User;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection()
    ]
)]

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
    ];

    // ===========================
    // RELATIONS
    // ===========================

    /**
     * Utilisateurs ayant ce rôle comme rôle principal
     */
    public function primaryUsers()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Utilisateurs ayant ce rôle comme rôle additionnel
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    /**
     * Permissions de ce rôle
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }

    // ===========================
    // MÉTHODES D'AUTORISATION
    // ===========================

    /**
     * Vérifier si ce rôle a une permission spécifique
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('action', $permission)->exists();
    }

    /**
     * Vérifier si ce rôle a au moins une des permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('action', $permissions)->exists();
    }

    /**
     * Obtenir toutes les permissions de ce rôle
     */
    public function getAllPermissions()
    {
        return $this->permissions;
    }

    /**
     * Assigner une permission à ce rôle
     */
    public function givePermission(string $permission): void
    {
        $permissionModel = Permission::where('action', $permission)->first();

        if ($permissionModel && !$this->hasPermission($permission)) {
            $this->permissions()->attach($permissionModel->id);
        }
    }

    /**
     * Assigner plusieurs permissions à ce rôle
     */
    public function givePermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->givePermission($permission);
        }
    }

    /**
     * Retirer une permission de ce rôle
     */
    public function removePermission(string $permission): void
    {
        $permissionModel = Permission::where('action', $permission)->first();

        if ($permissionModel) {
            $this->permissions()->detach($permissionModel->id);
        }
    }

    /**
     * Synchroniser les permissions de ce rôle
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    // ===========================
    // MÉTHODES UTILITAIRES
    // ===========================

    /**
     * Obtenir tous les utilisateurs de ce rôle (principal + additionnels)
     */
    public function getAllUsers()
    {
        $users = collect();

        // Utilisateurs avec ce rôle principal
        $users = $users->merge($this->primaryUsers);

        // Utilisateurs avec ce rôle additionnel
        $users = $users->merge($this->users);

        return $users->unique('id');
    }

    /**
     * Compter le nombre total d'utilisateurs ayant ce rôle
     */
    public function getTotalUsersCount(): int
    {
        return $this->getAllUsers()->count();
    }

    /**
     * Vérifier si ce rôle est un rôle administratif
     * Inclut tous les rôles avec privilèges d'administration
     */
    public function isAdminRole(): bool
    {
        return in_array($this->slug, [
            'super-admin',  // Super administrateur système
            'admin',        // Administrateur général
            'owner',        // Propriétaire (accès complet à sa maison d'hôte)
            'manager'       // Gestionnaire (gestion opérationnelle)
        ]);
    }

    /**
     * Grouper les permissions par catégorie
     */
    public function getPermissionsByCategory(): array
    {
        $permissions = $this->permissions;
        $grouped = [];

        foreach ($permissions as $permission) {
            $category = $this->getPermissionCategory($permission->action);
            $grouped[$category][] = $permission;
        }

        return $grouped;
    }

    /**
     * Déterminer la catégorie d'une permission
     */
    private function getPermissionCategory(string $action): string
    {
        $categories = [
            'system' => ['system-admin', 'admin-access', 'maintenance-mode', 'clear-cache', 'view-logs'],
            'users' => ['users-read', 'users-create', 'users-update', 'users-delete', 'roles-manage'],
            'accommodations' => ['accommodations-read', 'accommodations-manage', 'rooms-status'],
            'activities' => ['activities-read', 'activities-manage'],
            'bookings' => ['bookings-read-all', 'bookings-create', 'bookings-update', 'bookings-cancel', 'bookings-confirm', 'planning-manage'],
            'reception' => ['checkin', 'checkout', 'arrivals-today', 'departures-today', 'keys-manage'],
            'customers' => ['customers-read', 'customers-create', 'customers-update', 'customers-history'],
            'restaurant' => ['menus-read', 'menus-manage', 'dishes-manage', 'orders-take', 'orders-manage'],
            'finance' => ['payments-read', 'payments-collect', 'invoicing-manage', 'finance-stats'],
            'analytics' => ['dashboard-view', 'occupancy-stats', 'revenue-reports', 'data-export'],
            'communication' => ['messages-customers', 'notifications-team']
        ];

        foreach ($categories as $category => $actions) {
            if (in_array($action, $actions)) {
                return $category;
            }
        }

        return 'other';
    }
}
