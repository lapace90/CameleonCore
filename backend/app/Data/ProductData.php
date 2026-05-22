<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ProductData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public float $price,

        public ?string $productableType = null,
        public ?ProductableData $productable = null,
        public bool $status = true,
        public bool $isDraft = false,
        public ?int $categoryId = null,
        public array $categories = [],
        public ?string $image = null,
        public array $tags = [],
        public array $options = [],
        public array $relations = [],
    ) {}

    /**
     * Mapping personnalisé pour gérer les différences frontend/backend
     */
    public static function fromArray(array $data): static
    {
        // Normaliser les noms de champs
        $normalized = [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? null,
            'price' => (float) ($data['price'] ?? 0),
            'status' => (bool) ($data['status'] ?? true),
            'isDraft' => (bool) ($data['is_draft'] ?? $data['isDraft'] ?? false),
            'categoryId' => $data['category_id'] ?? $data['categoryId'] ?? null,
            'categories' => $data['categories'] ?? [],
            'image' => $data['image'] ?? null,
            'tags' => $data['tags'] ?? [],
            'options' => $data['options'] ?? [],
            'relations' => $data['relations'] ?? [],
        ];

        // Gestion du type productable
        $normalized['productableType'] = $data['productable_type']
            ?? $data['productableType']
            ?? '';

        // Données productable
        $productableData = $data['productable'] ?? [];

        // EXTRAIRE les relations du productable AVANT ProductableData
        $relationExtracted = [];
        if (isset($productableData['dishes'])) {
            $relationExtracted['dishes'] = $productableData['dishes'];
            unset($productableData['dishes']);
        }
        if (isset($productableData['ingredients'])) {
            $relationExtracted['ingredients'] = $productableData['ingredients'];
            unset($productableData['ingredients']);
        }

        $normalized['productable'] = ProductableData::fromArray($productableData);

        // Fusionner les relations
        $normalized['relations'] = array_merge(
            $normalized['relations'],
            $relationExtracted
        );

        return new static(...$normalized);
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'productableType' => [
                'required',
                'string',
                'in:App\\Models\\Activity,App\\Models\\Room,App\\Models\\Menu,App\\Models\\Dish,App\\Models\\Ingredient'
            ],
            'productable' => ['array'],
            'status' => ['boolean'],
            'isDraft' => ['boolean'],
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'image' => ['nullable', 'string'],
            'tags' => ['array'],
            'options' => ['array'],
            'relations' => ['array'],
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est requis',
            'name.min' => 'Le nom doit contenir au moins 2 caractères',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères',
            'price.required' => 'Le prix est requis',
            'price.numeric' => 'Le prix doit être un nombre',
            'price.min' => 'Le prix doit être positif',
            'productableType.required' => 'Le type de produit est requis',
            'productableType.in' => 'Type de produit non valide',
        ];
    }

    /**
     * Conversion vers le format attendu par le modèle Product
     */
    public function toProductArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'status' => $this->status,
            'is_draft' => $this->isDraft,
            'category_id' => $this->categoryId,
            'image' => $this->image,
            'productable_type' => $this->productableType,
        ];
    }
}
