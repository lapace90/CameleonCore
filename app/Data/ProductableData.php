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
        // 🔧 IMPORTANT: Changer le type pour accepter int ET string
        public mixed $difficulty_level = null,
        
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
     * 🔧 CORRECTION COMPLÈTE - Création à partir d'un tableau avec mapping automatique
     */
    public static function fromArray(array $data): static
    {
        // Filtrer les métadonnées API Platform ET les champs timestamps
        $cleanData = array_filter($data, function($key) {
            return !str_starts_with($key, '@') && 
                   !in_array($key, ['id', 'created_at', 'updated_at']);
        }, ARRAY_FILTER_USE_KEY);

        // Mapping des champs avec noms différents (camelCase -> snake_case)
        $mappings = [
            'meetingPoint' => 'meeting_point',
            'maxPeople' => 'max_people',
            'difficultyLevel' => 'difficulty_level',
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

        // 🎯 DÉBOGAGE DU DIFFICULTY_LEVEL
        if (isset($cleanData['difficulty_level'])) {
            \Illuminate\Support\Facades\Log::info('🎯 ProductableData - difficulty_level trouvé', [
                'value' => $cleanData['difficulty_level'],
                'type' => gettype($cleanData['difficulty_level'])
            ]);
        }

        return new static(
            guide: $cleanData['guide'] ?? null,
            duration: isset($cleanData['duration']) ? (int) $cleanData['duration'] : null,
            meeting_point: $cleanData['meeting_point'] ?? null,
            max_people: isset($cleanData['max_people']) ? (int) $cleanData['max_people'] : null,
            // 🔧 PRÉSERVER LA VALEUR TELLE QUELLE (int ou string)
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
    
    /**
     * 🔧 CORRECTION SYSTÈME UNIFIÉ - 1=facile, 2=moyen, 3=difficile
     */
    public function toActivityArray(): array
    {
        $difficultyValue = $this->normalizeDifficultyLevel($this->difficulty_level);
        
        \Illuminate\Support\Facades\Log::info('🎯 toActivityArray - difficulty processing', [
            'raw_value' => $this->difficulty_level,
            'normalized_value' => $difficultyValue
        ]);
        
        return [
            'guide' => $this->guide,
            'duration' => $this->duration ?? 60,
            'meeting_point' => $this->meeting_point,
            'max_people' => $this->max_people ?? 10,
            'difficulty_level' => $difficultyValue
        ];
    }
    
    /**
     * 🔧 NOUVEAU - Normalise le niveau de difficulté vers le système unifié
     * Frontend: easy/medium/hard → 1/2/3
     * Database: 1/2/3
     * Évite les conversions multiples qui corrompent les données
     */
    private function normalizeDifficultyLevel(mixed $difficulty): int
    {
        // Si c'est déjà un entier valide (1, 2, 3), le garder
        if (is_int($difficulty) && in_array($difficulty, [1, 2, 3])) {
            return $difficulty;
        }
        
        // Si c'est une string numérique valide
        if (is_string($difficulty) && ctype_digit($difficulty)) {
            $intVal = (int) $difficulty;
            if (in_array($intVal, [1, 2, 3])) {
                return $intVal;
            }
        }
        
        // Si c'est une string descriptive
        if (is_string($difficulty)) {
            return match(strtolower(trim($difficulty))) {
                'easy', 'facile' => 1,
                'medium', 'moyen' => 2,
                'hard', 'difficile' => 3,
                default => 2 // medium par défaut
            };
        }
        
        // Défaut
        return 2;
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
            'is_nut_free' => $this->is_nut_free,
        ];
    }
}