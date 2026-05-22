<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * UserData - Version complète avec tous les champs profil
 * Compatible API Platform pour les données d'entrée (formulaires, API calls)
 */
class UserData extends Data
{
    public function __construct(
        // Champs de base
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?string $password_confirmation = null,
        public ?string $current_password = null,    // Pour validation changement MDP
        public string $status = 'active',

        // Champs de rôles
        public ?int $role_id = null,
        public array $additional_roles = [],
        public array $permissions = [],
        public bool $password_reset_required = false,

        //  CHAMPS PROFIL
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $postal_code = null,
        public ?string $avatar = null,
    ) {}

    /**
     * Validation rules générales
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'nullable|string|min:8',
            'password_confirmation' => 'nullable|string|min:8',
            'current_password' => 'nullable|string',
            'status' => 'in:active,inactive,blocked',
            'role_id' => 'nullable|exists:roles,id',
            'additional_roles' => 'array',
            'additional_roles.*' => 'exists:roles,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'password_reset_required' => 'boolean',            
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'avatar' => 'nullable|string|max:255',
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
            'name' => 'sometimes|required|string|max:255',
            'email' => "sometimes|required|string|email|max:255|unique:users,email,{$userId}",
            'password' => 'nullable|string|min:8|confirmed',
            'current_password' => 'nullable|required_with:password|string', // Obligatoire si on change le MDP
            'status' => 'sometimes|in:active,inactive,blocked',
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

        // Fonction helper pour convertir string vide en null
        $stringOrNull = function ($value) {
            if ($value === '' || $value === null) {
                return null;
            }
            return (string) $value;
        };

        return new static(
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            password: $stringOrNull($data['password'] ?? null),
            password_confirmation: $stringOrNull($data['password_confirmation'] ?? $data['passwordConfirmation'] ?? null),
            current_password: $stringOrNull($data['current_password'] ?? $data['currentPassword'] ?? null),
            status: $data['status'] ?? 'active',
            
            // Support des deux formats : camelCase (frontend) et snake_case (API)
            role_id: $intOrNull($data['role_id'] ?? $data['roleId'] ?? null),
            additional_roles: $data['additional_roles'] ?? $data['additionalRoles'] ?? [],
            permissions: $data['permissions'] ?? [],
            password_reset_required: (bool) ($data['password_reset_required'] ?? $data['passwordResetRequired'] ?? false),

            //  CHAMPS PROFIL avec support camelCase/snake_case
            phone: $stringOrNull($data['phone'] ?? null),
            address: $stringOrNull($data['address'] ?? null),
            city: $stringOrNull($data['city'] ?? null),
            postal_code: $stringOrNull($data['postal_code'] ?? $data['postalCode'] ?? null),
            avatar: $stringOrNull($data['avatar'] ?? null),
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
            $data['password'] = $this->password; // Laravel se charge du hashage automatiquement
        }

        // Le role_id sera géré séparément dans UserProcessor
        if ($this->role_id) {
            $data['role_id'] = $this->role_id;
        }

        //  CHAMPS PROFIL - Ajouter seulement s'ils ne sont pas null
        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }
        if ($this->address !== null) {
            $data['address'] = $this->address;
        }
        if ($this->city !== null) {
            $data['city'] = $this->city;
        }
        if ($this->postal_code !== null) {
            $data['postal_code'] = $this->postal_code;
        }
        if ($this->avatar !== null) {
            $data['avatar'] = $this->avatar;
        }

        return $data;
    }

    /**
     * Vérifier si c'est une mise à jour de profil (pas de gestion de rôles)
     */
    public function isProfileUpdate(): bool
    {
        return empty($this->additional_roles) && 
               empty($this->permissions) && 
               !$this->password_reset_required;
    }

    /**
     * Vérifier si un changement de mot de passe est demandé
     */
    public function isPasswordChange(): bool
    {
        return !empty($this->current_password) && !empty($this->password);
    }
}