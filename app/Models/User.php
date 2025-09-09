<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\State\UserCollectionProvider;
use App\State\UserItemProvider;
use App\State\UserProcessor;
use App\Data\UserOutputData;
use App\Data\UserData;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/admin/users',
            provider: UserCollectionProvider::class,
            output: UserOutputData::class
        ),
        new Get(
            uriTemplate: '/admin/users/{id}',
            provider: UserItemProvider::class,
            output: UserOutputData::class
        ),
        new Post(
            uriTemplate: '/admin/users',
            processor: UserProcessor::class,
            output: UserOutputData::class,
            input: UserData::class,
            deserialize: false
        ),
        new Patch(
            uriTemplate: '/admin/users/{id}',
            processor: UserProcessor::class,
            output: UserOutputData::class,
            input: UserData::class,
            deserialize: false
        ),
        new Delete(
            uriTemplate: '/admin/users/{id}',
            processor: UserProcessor::class
        ),
    ]
)]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'status',
        'role_id',
        'last_login_at',
        'last_login_ip',
        'password_reset_required'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'password_reset_required' => 'boolean',
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
     * Rôles additionnels (many-to-many)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Réservations créées par cet utilisateur (admin)
     */
    public function createdReservations()
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    // ===========================
    // MÉTHODES D'AUTORISATION
    // ===========================

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        // Vérifier le rôle principal
        if ($this->role && $this->role->slug === $role) {
            return true;
        }

        // Vérifier les rôles additionnels
        return $this->roles()->where('slug', $role)->exists();
    }

    /**
     * Vérifier si l'utilisateur a au moins un des rôles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtenir tous les rôles de l'utilisateur
     */
    public function getAllRoles()
    {
        $roles = collect();

        // Ajouter le rôle principal
        if ($this->role) {
            $roles->push($this->role);
        }

        // Ajouter les rôles additionnels
        $roles = $roles->merge($this->roles);

        return $roles->unique('id');
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    /**
     * Vérifier si l'utilisateur est admin
     * Utilise la logique définie dans le modèle Role
     */
    public function isAdmin(): bool
    {
        // Vérifier le rôle principal
        if ($this->role && $this->role->isAdminRole()) {
            return true;
        }

        // Vérifier les rôles additionnels
        return $this->roles()->get()->contains(function ($role) {
            return $role->isAdminRole();
        });
    }

    /**
     * Vérifier si l'utilisateur est super-admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    // ===========================
    // MÉTHODES UTILITAIRES
    // ===========================

    /**
     * Marquer la dernière connexion
     */
    public function markAsLoggedIn(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
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
     * Obtenir un résumé des permissions de l'utilisateur selon le rôle
     */
    // public function getPermissionsSummary(): array
    // {
    //     $permissions = $this->getAllPermissions();

    //     return [
    //         'total_permissions' => $permissions->count(),
    //         'by_category' => $this->getPermissionsByCategory(),
    //         'roles' => [
    //             'primary' => $this->role ? $this->role->name : null,
    //             'additional' => $this->roles->pluck('name')->toArray()
    //         ],
    //         'direct_permissions' => $this->permissions->count(),
    //         'inherited_permissions' => $permissions->count() - $this->permissions->count()
    //     ];
    // }

    /**
     * Vérifier si l'utilisateur peut accéder à l'administration
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdmin();
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
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'is_admin' => $this->isAdmin()
        ];
    }
}
