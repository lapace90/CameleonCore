<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

// #[ApiResource]
// #[GetCollection(uriTemplate: '/admin/users')]

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role_id',
        'last_login_at',
        'last_login_ip',
        'password_reset_required',
        'metadata',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'password_reset_required' => 'boolean',
            'metadata' => 'array'
        ];
    }

    /**
     * Les valeurs par défaut des attributs
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'active',
        'password_reset_required' => false,
    ];

    // ===========================
    // RELATIONS
    // ===========================

    /**
     * Rôle principal de l'utilisateur
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Rôles additionnels de l'utilisateur (many-to-many)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withTimestamps();
    }

    /**
     * Permissions directes de l'utilisateur
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
                    ->withTimestamps();
    }

    // ===========================
    // SCOPES
    // ===========================

    /**
     * Scope pour les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour les utilisateurs avec un rôle spécifique
     */
    public function scopeWithRole($query, $roleSlug)
    {
        return $query->whereHas('role', function($q) use ($roleSlug) {
            $q->where('slug', $roleSlug);
        });
    }

    /**
     * Scope pour les utilisateurs avec une permission spécifique
     */
    public function scopeWithPermission($query, $permissionAction)
    {
        return $query->where(function($q) use ($permissionAction) {
            $q->whereHas('permissions', function($subQ) use ($permissionAction) {
                $subQ->where('action', $permissionAction);
            })
            ->orWhereHas('role.permissions', function($subQ) use ($permissionAction) {
                $subQ->where('action', $permissionAction);
            })
            ->orWhereHas('roles.permissions', function($subQ) use ($permissionAction) {
                $subQ->where('action', $permissionAction);
            });
        });
    }

    // ===========================
    // MÉTHODES DE GESTION DES PERMISSIONS
    // ===========================

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permissionAction): bool
    {
        // Super admin a toutes les permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getAllPermissions()->contains('action', $permissionAction);
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $roleSlug): bool
    {
        // Vérifier le rôle principal
        if ($this->role && $this->role->slug === $roleSlug) {
            return true;
        }

        // Vérifier les rôles additionnels
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Vérifier si l'utilisateur a au moins un des rôles spécifiés
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        foreach ($roleSlugs as $roleSlug) {
            if ($this->hasRole($roleSlug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si l'utilisateur est super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Obtenir toutes les permissions de l'utilisateur
     */
    public function getAllPermissions(): Collection
    {
        $permissions = collect();

        // Permissions du rôle principal
        if ($this->role) {
            $permissions = $permissions->merge($this->role->permissions);
        }

        // Permissions des rôles additionnels
        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }

        // Permissions directes
        $permissions = $permissions->merge($this->permissions);

        // Supprimer les doublons basés sur l'ID
        return $permissions->unique('id');
    }

    /**
     * Obtenir les permissions groupées par catégorie
     */
    public function getPermissionsByCategory(): array
    {
        $permissions = $this->getAllPermissions();
        $categorized = [];

        foreach ($permissions as $permission) {
            $category = $this->getPermissionCategory($permission->action);
            
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            
            $categorized[$category][] = $permission;
        }

        return $categorized;
    }

    /**
     * Assigner un rôle principal
     */
    public function assignRole(Role $role): void
    {
        $this->update(['role_id' => $role->id]);
    }

    /**
     * Ajouter un rôle additionnel
     */
    public function addRole(Role $role): void
    {
        if (!$this->roles()->where('role_id', $role->id)->exists()) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Retirer un rôle additionnel
     */
    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }

    /**
     * Synchroniser les rôles additionnels
     */
    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
    }

    /**
     * Assigner une permission directe
     */
    public function givePermission(Permission $permission): void
    {
        if (!$this->permissions()->where('permission_id', $permission->id)->exists()) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Retirer une permission directe
     */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }

    /**
     * Synchroniser les permissions directes
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    // ===========================
    // MÉTHODES DE STATUT
    // ===========================

    /**
     * Vérifier si l'utilisateur est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si l'utilisateur est bloqué
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Activer l'utilisateur
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Suspendre l'utilisateur
     */
    public function suspend(): void
    {
        $this->update(['status' => 'inactive']);
    }

    /**
     * Bloquer l'utilisateur
     */
    public function block(): void
    {
        $this->update(['status' => 'blocked']);
    }

    // ===========================
    // MÉTHODES DE CONNEXION
    // ===========================

    /**
     * Enregistrer la dernière connexion
     */
    public function recordLogin(string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
            'password_reset_required' => false
        ]);
    }

    /**
     * Forcer la réinitialisation du mot de passe
     */
    public function requirePasswordReset(): void
    {
        $this->update(['password_reset_required' => true]);
    }

    // ===========================
    // MÉTHODES UTILITAIRES
    // ===========================

    /**
     * Obtenir la catégorie d'une permission
     */
    private function getPermissionCategory(string $action): string
    {
        $action = strtolower($action);
        
        if (in_array($action, ['create', 'add', 'store'])) {
            return 'create';
        } elseif (in_array($action, ['read', 'view', 'show', 'list', 'index'])) {
            return 'read';
        } elseif (in_array($action, ['update', 'edit', 'modify'])) {
            return 'update';
        } elseif (in_array($action, ['delete', 'destroy', 'remove'])) {
            return 'delete';
        } elseif (in_array($action, ['manage', 'admin', 'control'])) {
            return 'admin';
        } else {
            return 'other';
        }
    }

    /**
     * Obtenir un résumé des permissions de l'utilisateur
     */
    public function getPermissionsSummary(): array
    {
        $permissions = $this->getAllPermissions();
        
        return [
            'total_permissions' => $permissions->count(),
            'by_category' => $this->getPermissionsByCategory(),
            'roles' => [
                'primary' => $this->role ? $this->role->name : null,
                'additional' => $this->roles->pluck('name')->toArray()
            ],
            'direct_permissions' => $this->permissions->count(),
            'inherited_permissions' => $permissions->count() - $this->permissions->count()
        ];
    }

    /**
     * Vérifier si l'utilisateur peut accéder à l'administration
     */
    public function canAccessAdmin(): bool
    {
        return $this->hasPermission('admin') || $this->isAdmin();
    }

    /**
     * Vérifier si l'utilisateur peut gérer d'autres utilisateurs
     */
    public function canManageUsers(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin']) || 
               $this->hasPermission('manage');
    }

    /**
     * Format pour l'API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'slug' => $this->role->slug
            ] : null,
            'permissions_count' => $this->getAllPermissions()->count(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'is_admin' => $this->isAdmin()
        ];
    }
}