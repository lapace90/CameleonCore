<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use App\Models\User;

/**
 * UserOutputData - Version complète avec tous les champs profil
 */
class UserOutputData extends Data
{
    public function __construct(
        // Champs de base
        public int $id,
        public string $name,
        public string $email,
        public string $status,
        
        // ✅ NOUVEAUX CHAMPS PROFIL
        public ?string $phone,
        public ?string $address,
        public ?string $city,
        public ?string $postal_code,
        public ?string $avatar,
        
        // Relations - format API direct
        public ?array $role,
        public array $additional_roles,
        public array $all_permissions,
        
        // Métadonnées
        public ?string $email_verified_at,
        public int $roles_count,
        public int $permissions_count,
        public ?string $last_login_at,
        public ?string $last_login_ip,
        public bool $password_reset_required,
        
        // Flags
        public bool $is_admin,
        public bool $is_super_admin,
        public bool $can_access_admin,
        
        // Timestamps
        public string $created_at,
        public string $updated_at,
        public ?string $deleted_at = null
    ) {}

    /**
     * Créer depuis un modèle User - Version complète avec tous les champs
     */
    public static function fromUser(User $user): self
    {
        // S'assurer que les relations sont chargées
        $user->loadMissing(['role', 'role.permissions', 'roles', 'roles.permissions']);
        $user->loadCount(['roles']);

        // Préparer le rôle principal
        $roleData = null;
        if ($user->role) {
            $roleData = [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
                'description' => $user->role->description ?? null
            ];
        }

        // Préparer les rôles additionnels
        $additionalRolesData = $user->roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description ?? null,
            ];
        })->toArray();

        // Consolider toutes les permissions uniques
        $allPermissions = collect();
        
        // Permissions du rôle principal
        if ($user->role && $user->role->permissions) {
            $allPermissions = $allPermissions->merge($user->role->permissions);
        }
        
        // Permissions des rôles additionnels
        $user->roles->each(function ($role) use (&$allPermissions) {
            if ($role->permissions) {
                $allPermissions = $allPermissions->merge($role->permissions);
            }
        });

        // Dédupliquer par ID et formater pour l'API
        $permissionsData = $allPermissions->unique('id')->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $permission->action,
                'category' => $permission->category ?? 'general',
            ];
        })->values()->toArray();

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            status: $user->status ?? 'active',
            
            // ✅ NOUVEAUX CHAMPS PROFIL
            phone: $user->phone,
            address: $user->address,
            city: $user->city,
            postal_code: $user->postal_code,
            avatar: $user->avatar,
            
            role: $roleData,
            additional_roles: $additionalRolesData,
            all_permissions: $permissionsData,
            email_verified_at: $user->email_verified_at?->toISOString(),
            roles_count: $user->roles_count ?? 0,
            permissions_count: count($permissionsData),
            last_login_at: $user->last_login_at?->toISOString(),
            last_login_ip: $user->last_login_ip,
            password_reset_required: $user->password_reset_required ?? false,
            is_admin: $user->isAdmin(),
            is_super_admin: $user->isSuperAdmin(),
            can_access_admin: $user->canAccessAdmin(),
            created_at: $user->created_at->toISOString(),
            updated_at: $user->updated_at->toISOString(),
            deleted_at: $user->deleted_at?->toISOString()
        );
    }

    /**
     * Version simplifiée pour les collections/listes (sans permissions pour performance)
     */
    public static function fromUserSimple(User $user): self
    {
        $user->loadMissing(['role', 'roles']);
        $user->loadCount(['roles']);

        $roleData = null;
        if ($user->role) {
            $roleData = [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug
            ];
        }

        $additionalRolesData = $user->roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug
            ];
        })->toArray();

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            status: $user->status ?? 'active',
            
            // ✅ NOUVEAUX CHAMPS PROFIL
            phone: $user->phone,
            address: $user->address,
            city: $user->city,
            postal_code: $user->postal_code,
            avatar: $user->avatar,
            
            role: $roleData,
            additional_roles: $additionalRolesData,
            all_permissions: [], // Vide pour la version simple
            email_verified_at: $user->email_verified_at?->toISOString(),
            roles_count: $user->roles_count ?? 0,
            permissions_count: 0, // Calculé plus tard si nécessaire
            last_login_at: $user->last_login_at?->toISOString(),
            last_login_ip: $user->last_login_ip,
            password_reset_required: $user->password_reset_required ?? false,
            is_admin: $user->isAdmin(),
            is_super_admin: $user->isSuperAdmin(),
            can_access_admin: $user->canAccessAdmin(),
            created_at: $user->created_at->toISOString(),
            updated_at: $user->updated_at->toISOString(),
            deleted_at: $user->deleted_at?->toISOString()
        );
    }
}