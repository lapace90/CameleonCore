<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;

class UserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public string $status = 'active',
        
        #[MapName('role_id')]
        public ?int $roleId = null,
        
        #[MapName('additional_roles')]
        public array $additionalRoles = [],
        
        #[MapName('permissions')]
        public array $permissions = [],
        
        #[MapName('password_confirmation')]
        public ?string $passwordConfirmation = null,
        
        #[MapName('password_reset_required')]
        public bool $passwordResetRequired = false,
    ) {}

    /**
     * Validation rules pour la création/mise à jour
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'nullable|string|min:8',
            'status' => 'in:active,inactive,suspended',
            'roleId' => 'nullable|exists:roles,id',
            'additionalRoles' => 'array',
            'additionalRoles.*' => 'exists:roles,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ];
    }

    /**
     * Validation rules pour la création uniquement
     */
    public static function rulesForCreate(): array
    {
        return array_merge(self::rules(), [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);
    }

    /**
     * Validation rules pour la mise à jour
     */
    public static function rulesForUpdate(int $userId): array
    {
        return array_merge(self::rules(), [
            'email' => "required|string|email|max:255|unique:users,email,{$userId}",
            'password' => 'nullable|string|min:8|confirmed'
        ]);
    }

    /**
     * Normaliser les données depuis le frontend
     */
    public static function fromArray(array $data): static
    {
        return new static(
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? null,
            status: $data['status'] ?? 'active',
            roleId: $data['role_id'] ?? $data['roleId'] ?? null,
            additionalRoles: $data['additional_roles'] ?? $data['additionalRoles'] ?? [],
            permissions: $data['permissions'] ?? [],
            passwordConfirmation: $data['password_confirmation'] ?? $data['passwordConfirmation'] ?? null,
            passwordResetRequired: $data['password_reset_required'] ?? $data['passwordResetRequired'] ?? false
        );
    }

    /**
     * Convertir en array pour la création du modèle
     */
    public function toModelArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'role_id' => $this->roleId,
            'password_reset_required' => $this->passwordResetRequired
        ];

        // Ajouter le mot de passe seulement s'il est fourni
        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        return $data;
    }
}
