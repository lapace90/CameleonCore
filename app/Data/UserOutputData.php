<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use App\Models\User;

class UserOutputData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $emailVerifiedAt,
        public string $status,
        public ?array $role,
        public array $additionalRoles,
        public int $rolesCount,
        public ?string $lastLoginAt,
        public ?string $lastLoginIp,
        public bool $passwordResetRequired,
        public bool $isAdmin,
        public bool $isSuperAdmin,
        public bool $canAccessAdmin,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt = null
    ) {}

    /**
     * Créer depuis un modèle User
     */
    public static function fromUser(User $user): self
    {
        // S'assurer que les relations sont chargées
        $user->loadMissing([
            'role',
        ]);

        // Compter les relations
        $user->loadCount(['roles']);

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
            status: $user->status ?? 'active',
            role: $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
                'description' => $user->role->description ?? null
            ] : null,
            additionalRoles: $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description ?? null,
            ])->toArray(),
            rolesCount: $user->roles_count ?? 0,
            lastLoginAt: $user->last_login_at?->toISOString(),
            lastLoginIp: $user->last_login_ip,
            passwordResetRequired: $user->password_reset_required ?? false,
            isAdmin: $user->isAdmin(),
            isSuperAdmin: $user->isSuperAdmin(),
            canAccessAdmin: $user->canAccessAdmin(),
            createdAt: $user->created_at->toISOString(),
            updatedAt: $user->updated_at->toISOString(),
            deletedAt: $user->deleted_at?->toISOString()
        );
    }

    /**
     * Version simplifiée pour les listes
     */
    public static function fromUserSimple(User $user): self
    {
        $user->loadMissing(['role', 'roles']);
        $user->loadCount(['roles']);

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
            status: $user->status ?? 'active',
            role: $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug
            ] : null,
            additionalRoles: $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug
            ])->toArray(),
            rolesCount: $user->roles_count ?? 0,
            lastLoginAt: $user->last_login_at?->toISOString(),
            lastLoginIp: $user->last_login_ip,
            passwordResetRequired: $user->password_reset_required ?? false,
            isAdmin: $user->isAdmin(),
            isSuperAdmin: $user->isSuperAdmin(),
            canAccessAdmin: $user->canAccessAdmin(),
            createdAt: $user->created_at->toISOString(),
            updatedAt: $user->updated_at->toISOString(),
            deletedAt: $user->deleted_at?->toISOString()
        );
    }

    /**
     * Convertir en array pour les réponses JSON
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->emailVerifiedAt,
            'status' => $this->status,
            'role' => $this->role,
            'additional_roles' => $this->additionalRoles,
            'roles_count' => $this->rolesCount,
            'last_login_at' => $this->lastLoginAt,
            'last_login_ip' => $this->lastLoginIp,
            'password_reset_required' => $this->passwordResetRequired,
            'is_admin' => $this->isAdmin,
            'is_super_admin' => $this->isSuperAdmin,
            'can_access_admin' => $this->canAccessAdmin,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt
        ];
    }
}
