<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * UserData - Version 100% compatible API Platform
 * Pour les données d'entrée (formulaires, API calls)
 */
class UserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public string $status = 'active',

        // Champs directs sans attributs MapName - API Platform les attend comme ça
        public ?int $role_id = null,
        public array $additional_roles = [],
        public array $permissions = [],
        public ?string $password_confirmation = null,
        public bool $password_reset_required = false,
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
            'role_id' => 'nullable|exists:roles,id',
            'additional_roles' => 'array',
            'additional_roles.*' => 'exists:roles,id',
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
     * Normaliser les données depuis le frontend - Compatible camelCase et snake_case
     */
    public static function fromArray(array $data): static
    {
        // Fonction helper pour convertir string vide en null pour les entiers
        $intOrNull = function ($value) {
            if ($value === '' || $value === null) {
                return null;
            }
            return is_numeric($value) ? (int) $value : null;
        };

        return new static(
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? null,
            status: $data['status'] ?? 'active',
            
            // Support des deux formats : camelCase (frontend) et snake_case (API)
            role_id: $intOrNull($data['role_id'] ?? $data['roleId'] ?? null),
            additional_roles: $data['additional_roles'] ?? $data['additionalRoles'] ?? [],
            permissions: $data['permissions'] ?? [],
            password_confirmation: $data['password_confirmation'] ?? $data['passwordConfirmation'] ?? null,
            password_reset_required: (bool) ($data['password_reset_required'] ?? $data['passwordResetRequired'] ?? false),
        );
    }

    /**
     * Convertir vers array pour Eloquent models
     */
    public function toModelArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'password_reset_required' => $this->password_reset_required,
        ];

        // Ajouter le mot de passe seulement s'il est fourni
        if (!empty($this->password)) {
            $data['password'] = bcrypt($this->password);
        }

        // Le role_id sera géré séparément dans UserProcessor
        if ($this->role_id) {
            $data['role_id'] = $this->role_id;
        }

        return $data;
    }
}