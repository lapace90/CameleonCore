<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Computed;

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
        // Retourner un ARRAY directement, pas un objet Data
        // API Platform Laravel sait sérialiser les arrays
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'formatted_price' => number_format((float) $product->price, 2, ',', ' ') . ' €',
            'status' => $product->status,
            'is_draft' => $product->is_draft,
            'image' => $product->image,
            'productable_type' => $product->productable_type,
            'productable_detail' => $product->productable?->toArray() ?? [],
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'type' => $product->category->type ?? null,
                'description' => $product->category->description ?? null,
            ] : null,
            'tags' => $product->globalTags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color ?? '#6b7280'
            ])->toArray(),
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
                        'formatted_price' => number_format((float) $dish->product->price ?? 0, 2, ',', ' ') . ' €'
                    ])->toArray();
                }
                break;
                
            case 'App\\Models\\Dish':
                if ($product->productable->relationLoaded('ingredients')) {
                    $relations['ingredients'] = $product->productable->ingredients->map(fn($ingredient) => [
                        'id' => $ingredient->id,
                        'product_id' => $ingredient->product->id ?? null,
                        'name' => $ingredient->product->name ?? 'N/A',
                        // 'stock' => $ingredient->stock ?? 0
                    ])->toArray();
                }
                break;
        }
        
        return $relations;
    }
}