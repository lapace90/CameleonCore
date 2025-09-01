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

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
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
     * Retirer une permission de ce rôle
     */
    public function removePermission(string $permission): void
    {
        $permissionModel = Permission::where('action', $permission)->first();
        
        if ($permissionModel) {
            $this->permissions()->detach($permissionModel->id);
        }
    }

    // ===========================
    // MÉTHODES UTILITAIRES
    // ===========================

    /**
     * Vérifier si c'est un rôle admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->slug, ['admin', 'super-admin']);
    }

    /**
     * Vérifier si c'est le super-admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->slug === 'super-admin';
    }

    /**
     * Obtenir le niveau hiérarchique du rôle (pour les comparaisons)
     */
    public function getLevel(): int
    {
        return match($this->slug) {
            'super-admin' => 100,
            'admin' => 80,
            'manager' => 60,
            'employee' => 40,
            'user' => 20,
            default => 0
        };
    }

    /**
     * Vérifier si ce rôle est supérieur à un autre
     */
    public function isHigherThan(Role $otherRole): bool
    {
        return $this->getLevel() > $otherRole->getLevel();
    }
}