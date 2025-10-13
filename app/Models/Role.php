<?php
// app/Models/Role.php - Version CRUD complète avec API Platform

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Permission;
use App\Models\User;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\State\RoleCollectionProvider;
use App\State\RoleProcessor;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            middleware: ['auth:sanctum'],
            provider: RoleCollectionProvider::class,
        ),
        new Post(
            middleware: ['auth:sanctum'],
            processor: RoleProcessor::class,
        ),
        new Patch(
            middleware: ['auth:sanctum'],
            processor: RoleProcessor::class,
        ),
        new Put(
            middleware: ['auth:sanctum'],
            processor: RoleProcessor::class,
        ),
        new Delete(
            middleware: ['auth:sanctum'],
            processor: RoleProcessor::class,
        )
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
    // RELATIONS (existantes)
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
    // MÉTHODES D'AUTORISATION (existantes)
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
     * Indique si ce rôle est un rôle d'administration.
     * Adapte la liste selon ta politique d'accès.
     */
    public function isAdminRole(): bool
    {
        return in_array($this->slug, [
            'owner',
            'super-admin',
            'admin',
            'system-admin',
        ], true);
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
    // NOUVELLES MÉTHODES pour le processor
    // ===========================

    /**
     * Vérifier si le rôle peut être supprimé
     */
    public function canBeDeleted(): bool
    {
        // Un rôle ne peut pas être supprimé s'il a des utilisateurs
        $totalUsers = $this->primaryUsers()->count() + $this->users()->count();
        return $totalUsers === 0;
    }

    /**
     * Vérifier si c'est un rôle critique du système
     */
    public function isCritical(): bool
    {
        $criticalSlugs = [
            'owner',
            'super-admin',
            'admin',
            'system-admin'
        ];

        return in_array($this->slug, $criticalSlugs);
    }

    /**
     * Normaliser le slug
     */
    public static function normalizeSlug(string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    }

    /**
     * Générer le slug depuis le nom
     */
    public static function generateSlugFromName(string $name): string
    {
        return self::normalizeSlug($name);
    }

    /**
     * Obtenir la couleur du rôle pour l'UI
     */
    public function getColorAttribute(): string
    {
        $colorMap = [
            'owner' => '#dc2626',       // Rouge
            'super-admin' => '#7c2d12', // Rouge foncé  
            'admin' => '#ea580c',       // Orange
            'manager' => '#7c3aed',     // Violet
            'employee' => '#0891b2',    // Cyan
            'receptionist' => '#059669', // Vert
            'guide' => '#0d9488',       // Teal
            'chef' => '#dc2626',        // Rouge
            'waiter' => '#4338ca',      // Indigo
            'cleaner' => '#6b7280',     // Gris
        ];

        return $colorMap[$this->slug] ?? '#6b7280'; // Gris par défaut
    }

    /**
     * Obtenir l'icône du rôle pour l'UI
     */
    public function getIconAttribute(): string
    {
        $iconMap = [
            'owner' => 'fa-crown',
            'super-admin' => 'fa-user-shield',
            'admin' => 'fa-user-cog',
            'manager' => 'fa-users-cog',
            'employee' => 'fa-user',
            'receptionist' => 'fa-concierge-bell',
            'guide' => 'fa-route',
            'chef' => 'fa-utensils',
            'waiter' => 'fa-glass-martini-alt',
            'cleaner' => 'fa-broom',
        ];

        return $iconMap[$this->slug] ?? 'fa-user';
    }
}
