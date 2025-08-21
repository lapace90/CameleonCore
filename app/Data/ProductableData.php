<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ProductableData extends Data
{
    public function __construct(
        // Activity fields
        public ?string $guide = null,
        public ?int $duration = null,
        public ?string $meeting_point = null,
        public ?int $max_people = null,
        public ?string $difficulty_level = null,
        
        // Room fields
        public ?int $capacity = null,
        public ?bool $availability = null,
        
        // Ingredient fields
        public ?int $stock = null,
        public bool $is_vegetarian = false,
        public bool $is_vegan = false,
        public bool $is_spicy = false,
        public bool $is_gluten_free = false,
        public bool $is_lactose_free = false,
        public bool $is_nut_free = false,
    ) {}

    /**
     * Méthode from() personnalisée pour créer depuis les données frontend
     */
    public static function from(mixed ...$payloads): static
    {
        $data = $payloads[0] ?? [];
        
        if (is_array($data)) {
            return self::fromArray($data);
        }
        
        // Fallback sur la méthode parent
        return parent::from(...$payloads);
    }

    /**
     * Création à partir d'un tableau avec mapping automatique
     */
    public static function fromArray(array $data): static
    {
        // Filtrer les métadonnées API Platform
        $cleanData = array_filter($data, function($key) {
            return !str_starts_with($key, '@');
        }, ARRAY_FILTER_USE_KEY);

        // Mapping des champs avec noms différents (camelCase -> snake_case)
        $mappings = [
            'meetingPoint' => 'meeting_point',
            'maxPeople' => 'max_people',
            'difficulty_level' => 'difficulty_level',
            'isVegetarian' => 'is_vegetarian',
            'isVegan' => 'is_vegan',
            'isSpicy' => 'is_spicy',
            'isGlutenFree' => 'is_gluten_free',
            'isLactoseFree' => 'is_lactose_free',
            'isNutFree' => 'is_nut_free',
        ];

        foreach ($mappings as $camelCase => $snakeCase) {
            if (isset($cleanData[$camelCase])) {
                $cleanData[$snakeCase] = $cleanData[$camelCase];
                unset($cleanData[$camelCase]);
            }
        }

        return new static(
            guide: $cleanData['guide'] ?? null,
            duration: isset($cleanData['duration']) ? (int) $cleanData['duration'] : null,
            meeting_point: $cleanData['meeting_point'] ?? null,
            max_people: isset($cleanData['max_people']) ? (int) $cleanData['max_people'] : null,
            difficulty_level: $cleanData['difficulty_level'] ?? null,
            capacity: isset($cleanData['capacity']) ? (int) $cleanData['capacity'] : null,
            availability: isset($cleanData['availability']) ? (bool) $cleanData['availability'] : null,
            stock: isset($cleanData['stock']) ? (int) $cleanData['stock'] : null,
            is_vegetarian: (bool) ($cleanData['is_vegetarian'] ?? false),
            is_vegan: (bool) ($cleanData['is_vegan'] ?? false),
            is_spicy: (bool) ($cleanData['is_spicy'] ?? false),
            is_gluten_free: (bool) ($cleanData['is_gluten_free'] ?? false),
            is_lactose_free: (bool) ($cleanData['is_lactose_free'] ?? false),
            is_nut_free: (bool) ($cleanData['is_nut_free'] ?? false),
        );
    }
    
    public function toActivityArray(): array
    {
        return [
            'guide' => $this->guide,
            'duration' => $this->duration ?? 60,
            'meeting_point' => $this->meeting_point,
            'max_people' => $this->max_people ?? 10,
            'difficulty_level' => $this->convertDifficultyToInteger($this->difficulty_level ?? 'medium')
        ];
    }
    
    /**
     * Convertit les niveaux de difficulté string en entier selon la logique de l'app
     */
    private function convertDifficultyToInteger(?string $difficulty): int
    {
        return match(strtolower($difficulty ?? 'medium')) {
            'easy' => 2,
            'medium' => 3,
            'hard' => 5,
            'extreme' => 7,
            default => 3 // medium par défaut
        };
    }
    
    public function toRoomArray(): array
    {
        return [
            'capacity' => $this->capacity ?? 2,
            'availability' => $this->availability ?? true
        ];
    }
    
    public function toIngredientArray(): array
    {
        return [
            'stock' => $this->stock ?? 0,
            'is_vegetarian' => $this->is_vegetarian,
            'is_vegan' => $this->is_vegan,
            'is_spicy' => $this->is_spicy,
            'is_gluten_free' => $this->is_gluten_free,
            'is_lactose_free' => $this->is_lactose_free,
            'is_nut_free' => $this->is_nut_free
        ];
    }

    public static function rules(): array
    {
        return [
            // Activity validation
            'guide' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'meeting_point' => ['nullable', 'string'],
            'max_people' => ['nullable', 'integer', 'min:1'],
            'difficulty_level' => ['nullable', 'string', 'in:easy,medium,hard,extreme'],
            
            // Room validation
            'capacity' => ['nullable', 'integer', 'min:1'],
            'availability' => ['nullable', 'boolean'],
            
            // Ingredient validation
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_vegetarian' => ['boolean'],
            'is_vegan' => ['boolean'],
            'is_spicy' => ['boolean'],
            'is_gluten_free' => ['boolean'],
            'is_lactose_free' => ['boolean'],
            'is_nut_free' => ['boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'duration.min' => 'La durée doit être d\'au moins 1 minute',
            'max_people.min' => 'Le nombre maximum de personnes doit être d\'au moins 1',
            'capacity.min' => 'La capacité doit être d\'au moins 1',
            'stock.min' => 'Le stock ne peut pas être négatif',
            'difficulty_level.in' => 'Le niveau de difficulté doit être: easy, medium, hard ou extreme',
        ];
    }
}