<?php

namespace App\Data;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;
use App\Models\Category;
use Illuminate\Support\Str;

/**
 * DTO d'entrée pour création/modification de catégories
 * Validation automatique des données via Symfony Validator
 */
class CategoryData
{
    public function __construct(
        #[ApiProperty(description: 'Category type')]
        #[Assert\NotBlank(message: 'Le type de catégorie est obligatoire')]
        #[Assert\Choice(
            choices: ['Activity', 'Menu', 'Dish', 'Room', 'Ingredient'],
            message: 'Le type doit être : Activity, Menu, Dish, Room ou Ingredient'
        )]
        public string $type,
        
        #[ApiProperty(description: 'Category name')]
        #[Assert\NotBlank(message: 'Le nom de la catégorie est obligatoire')]
        #[Assert\Length(
            min: 3,
            max: 50,
            minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
            maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
        )]
        #[Assert\Regex(
            pattern: '/^[\w\s\-àâäéèêëïîôöùûüÿç]+$/iu',
            message: 'Le nom contient des caractères non autorisés'
        )]
        public string $name,
        
        #[ApiProperty(description: 'URL-friendly slug (optional, auto-generated from name)')]
        #[Assert\Length(
            max: 60,
            maxMessage: 'Le slug ne peut pas dépasser {{ limit }} caractères'
        )]
        #[Assert\Regex(
            pattern: '/^[a-z0-9\-]*$/',
            message: 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets'
        )]
        public ?string $slug = null,
        
        #[ApiProperty(description: 'Category description')]
        #[Assert\Length(
            max: 500,
            maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
        )]
        public ?string $description = null,
        
        #[ApiProperty(description: 'Photo file path')]
        #[Assert\Length(
            max: 255,
            maxMessage: 'Le chemin de la photo ne peut pas dépasser {{ limit }} caractères'
        )]
        #[Assert\Regex(
            pattern: '/^[\w\-\/\.]+\.(jpg|jpeg|png|gif|webp)$/i',
            message: 'Le format de photo n\'est pas valide (jpg, png, gif, webp autorisés)'
        )]
        public ?string $photo = null,
        
        #[ApiProperty(description: 'Is category active')]
        public bool $isActive = true,
        
        #[ApiProperty(description: 'Sort order for display')]
        #[Assert\Range(
            min: 0,
            max: 999,
            notInRangeMessage: 'L\'ordre de tri doit être entre {{ min }} et {{ max }}'
        )]
        public int $sortOrder = 0,
        
        #[ApiProperty(description: 'Additional metadata (JSON object)')]
        public ?array $metadata = null
    ) {}

    /**
     * Créer depuis un tableau de données (request payload)
     */
    public static function from(array $data): self
    {
        return new self(
            type: $data['type'] ?? '',
            name: trim($data['name'] ?? ''),
            slug: !empty($data['slug']) ? Str::slug($data['slug']) : null,
            description: !empty($data['description']) ? trim($data['description']) : null,
            photo: $data['photo'] ?? null,
            isActive: $data['isActive'] ?? $data['is_active'] ?? true,
            sortOrder: (int) ($data['sortOrder'] ?? $data['sort_order'] ?? 0),
            metadata: $data['metadata'] ?? null
        );
    }

    /**
     * Convertir en tableau pour l'ORM
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'slug' => $this->slug ?: Str::slug($this->name),
            'description' => $this->description,
            'photo' => $this->photo,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Valider les données avec les règles métier
     */
    public function validate(?int $categoryId = null): array
    {
        $errors = [];

        // Validation unicité du nom
        $existingByName = Category::where('name', $this->name)
            ->when($categoryId, fn($q) => $q->where('id', '!=', $categoryId))
            ->first();
        
        if ($existingByName) {
            $errors['name'] = 'Une catégorie avec ce nom existe déjà';
        }

        // Validation unicité du slug
        $slug = $this->slug ?: Str::slug($this->name);
        $existingBySlug = Category::where('slug', $slug)
            ->when($categoryId, fn($q) => $q->where('id', '!=', $categoryId))
            ->first();
        
        if ($existingBySlug) {
            $errors['slug'] = 'Ce slug est déjà utilisé';
        }

        // Validation du type
        if (!array_key_exists($this->type, Category::TYPES)) {
            $errors['type'] = 'Type de catégorie invalide';
        }

        return $errors;
    }
}
