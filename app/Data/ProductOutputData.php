<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Computed;
use Illuminate\Support\Facades\Log;

class ProductOutputData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public float $price,
        public string $formatted_price,
        public bool $status,
        public bool $is_draft,
        public ?string $image,
        public string $productable_type,
        public array $productable_detail,
        public ?CategoryData $category,
        public array $tags,
        public array $options,
        public array $relations,
        public array $statistics,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromProduct(\App\Models\Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'formatted_price' => number_format((float) $product->price, 2, ',', ' ') . ' €',
            'status' => $product->status,
            'is_draft' => $product->is_draft,

            // Champs pour le frontend
            'status_label' => self::getStatusLabel($product),
            'status_class' => self::getStatusClass($product),
            'typeConfig' => self::getTypeConfig($product->productable_type),
            'productableType' => $product->productable_type,

            'image' => $product->image,
            'productable_type' => $product->productable_type,
            'productable_detail' => $product->productable?->toArray() ?? [],
            'productableDetail' => $product->productable?->toArray() ?? [],

            // Detail fields pour les activités, rooms, etc.
            'detail_fields' => self::getDetailFields($product),

            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'type' => $product->category->type ?? null,
                'description' => $product->category->description ?? null,
            ] : null,
            'categories' => $product->categories->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'type' => $cat->type ?? null,
                'description' => $cat->description ?? null,
            ])->toArray(),
            'globalTags' => $product->globalTags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color ?? '#6b7280'
            ])->toArray(),
            'specificTags' => self::getSpecificTags($product),
            'options' => $product->options->map(fn($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'price' => $option->price,
                'formatted_price' => number_format((float) $option->price, 2, ',', ' ') . ' €'
            ])->toArray(),
            'relations' => self::getRelations($product),
            'statistics' => [
                'views' => 0,
                'reservations_count' => $product->reservations()->count(),
                'average_rating' => 0,
                'total_revenue' => 0
            ],
            'created_at' => $product->created_at->toISOString(),
            'updated_at' => $product->updated_at->toISOString(),
        ];
    }

    private static function getRelations(\App\Models\Product $product): array
    {
        $relations = [];

        if (!$product->productable) return $relations;

        switch ($product->productable_type) {
            case 'App\\Models\\Menu':
                if ($product->productable->relationLoaded('dishes')) {
                    $relations['dishes'] = $product->productable->dishes->map(fn($dish) => [
                        'id' => $dish->id,
                        'product_id' => $dish->product->id ?? null,
                        'name' => $dish->product->name ?? 'N/A',
                        'price' => $dish->product->price ?? 0,
                        'formatted_price' => number_format((float) ($dish->product->price ?? 0), 2, ',', ' ') . ' €'
                    ])->toArray();
                }
                break;

            case 'App\\Models\\Dish':
                if ($product->productable->relationLoaded('ingredients')) {
                    $relations['ingredients'] = $product->productable->ingredients->map(fn($ingredient) => [
                        'id' => $ingredient->id,
                        'product_id' => $ingredient->product->id ?? null,
                        'name' => $ingredient->product->name ?? 'N/A',
                        'stock' => $ingredient->stock ?? 0
                    ])->toArray();
                }
                break;
        }

        return $relations;
    }

    private static function getDetailFields(\App\Models\Product $product): array
    {
        $fields = [];
        $productable = $product->productable;

        if (!$productable) return $fields;

        switch ($product->productable_type) {
            case 'App\\Models\\Activity':
                $fields['guide'] = ['label' => 'Guide', 'value' => $productable->guide ?? 'N/A'];
                $fields['duration'] = ['label' => 'Durée', 'value' => ($productable->duration ?? 0) . ' min'];
                $fields['meeting_point'] = ['label' => 'Point RDV', 'value' => $productable->meeting_point ?? 'N/A'];
                $fields['max_people'] = ['label' => 'Capacité max', 'value' => ($productable->max_people ?? 0) . ' pers.'];
                $fields['difficulty_level'] = ['label' => 'Difficulté', 'value' => self::formatDifficulty($productable->difficulty_level ?? 1)];
                break;

            case 'App\\Models\\Room':
                $fields['capacity'] = ['label' => 'Capacité', 'value' => ($productable->capacity ?? 0) . ' pers.'];
                $fields['availability'] = ['label' => 'Disponibilité', 'value' => ($productable->availability ?? false) ? 'Disponible' : 'Non disponible'];
                break;

            case 'App\\Models\\Ingredient':
                $fields['stock'] = ['label' => 'Stock', 'value' => ($productable->stock ?? 0) . ' unités'];
                $fields['dietary_info'] = ['label' => 'Info diététique', 'value' => self::formatDietaryInfo($productable)];
                break;
        }

        return $fields;
    }

    private static function getTypeConfig(string $productableType): array
    {
        $typeConfigs = [
            'App\\Models\\Activity' => [
                'label' => 'Activités',
                'singular' => 'Activité',
                'icon' => 'fas fa-hiking',
                'color' => '#3b82f6',
                'class' => 'App\\Models\\Activity'
            ],
            'App\\Models\\Dish' => [
                'label' => 'Plats',
                'singular' => 'Plat',
                'icon' => 'fas fa-drumstick-bite',
                'color' => '#f97316',
                'class' => 'App\\Models\\Dish'
            ],
            'App\\Models\\Menu' => [
                'label' => 'Menus',
                'singular' => 'Menu',
                'icon' => 'fas fa-utensils',
                'color' => '#10b981',
                'class' => 'App\\Models\\Menu'
            ],
            'App\\Models\\Ingredient' => [
                'label' => 'Ingrédients',
                'singular' => 'Ingrédient',
                'icon' => 'fas fa-seedling',
                'color' => '#22c55e',
                'class' => 'App\\Models\\Ingredient'
            ],
            'App\\Models\\Room' => [
                'label' => 'Hébergements',
                'singular' => 'Hébergement',
                'icon' => 'fas fa-bed',
                'color' => '#f59e0b',
                'class' => 'App\\Models\\Room'
            ]
        ];

        return $typeConfigs[$productableType] ?? $typeConfigs['App\\Models\\Activity'];
    }

    private static function getStatusLabel(\App\Models\Product $product): string
    {
        if ($product->is_draft) return 'Brouillon';
        return $product->status ? 'Actif' : 'Inactif';
    }

    private static function getStatusClass(\App\Models\Product $product): string
    {
        if ($product->is_draft) return 'status-draft';
        return $product->status ? 'status-active' : 'status-inactive';
    }

    private static function getSpecificTags(\App\Models\Product $product): array
    {
        if (!$product->productable || !method_exists($product->productable, 'specificTags')) {
            return [];
        }

        if (!$product->productable->relationLoaded('specificTags')) {
            $product->productable->load('specificTags');
        }

        return $product->productable->specificTags->map(fn($tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
            'icon' => $tag->icon ?? null,
            'color' => '#8b5cf6'
        ])->toArray();
    }

    private static function formatDifficulty(int $level): string
    {
        $difficulties = [
            1 => 'Facile',
            2 => 'Moyen',
            3 => 'Difficile'
        ];

        return $difficulties[$level] ?? 'Non défini';
    }

    private static function formatDietaryInfo($ingredient): string
    {
        $info = [];

        if ($ingredient->is_vegan) $info[] = 'Vegan';
        elseif ($ingredient->is_vegetarian) $info[] = 'Végétarien';

        if ($ingredient->is_spicy) $info[] = 'Épicé';
        if ($ingredient->is_gluten_free) $info[] = 'Sans gluten';
        if ($ingredient->is_lactose_free) $info[] = 'Sans lactose';
        if ($ingredient->is_nut_free) $info[] = 'Sans noix';

        return empty($info) ? 'Aucune restriction' : implode(', ', $info);
    }
}
