<?php

namespace App\Transformers;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductTransformer
{
    /**
     * Configuration des types de produits
     */
    private static $productConfigs = [
        'App\\Models\\Activity' => [
            'label' => 'Activités',
            'singular' => 'Activité',
            'icon' => 'fas fa-hiking',
            'color' => '#3b82f6',
            'listColumns' => ['duration', 'max_people', 'difficulty_level'],
            'detailFields' => ['guide', 'duration', 'meeting_point', 'max_people', 'difficulty_level']
        ],
        'App\\Models\\Menu' => [
            'label' => 'Menus',
            'singular' => 'Menu',
            'icon' => 'fas fa-utensils',
            'color' => '#10b981',
            'listColumns' => ['dishes_count', 'total_price'],
            'detailFields' => ['dishes_count', 'total_ingredients', 'dietary_info']
        ],
        'App\\Models\\Dish' => [
            'label' => 'Plats',
            'singular' => 'Plat',
            'icon' => 'fas fa-drumstick-bite',
            'color' => '#f97316',
            'listColumns' => ['ingredients_count', 'dietary_info'],
            'detailFields' => ['ingredients_count', 'dietary_tags']
        ],
        'App\\Models\\Ingredient' => [
            'label' => 'Ingrédients',
            'singular' => 'Ingrédient',
            'icon' => 'fas fa-seedling',
            'color' => '#22c55e',
            'listColumns' => ['stock', 'is_vegetarian', 'is_vegan'],
            'detailFields' => ['stock', 'dietary_properties']
        ],
        'App\\Models\\Room' => [
            'label' => 'Hébergements',
            'singular' => 'Hébergement',
            'icon' => 'fas fa-bed',
            'color' => '#f59e0b',
            'listColumns' => ['capacity', 'availability'],
            'detailFields' => ['capacity', 'availability']
        ]
    ];

    /**
     * Transformer pour la liste des produits
     */
    public static function transformForList(Collection $products): array
    {
        return $products->map(function ($product) {
            $config = self::getProductConfig($product->productable_type);
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'formatted_price' => $product->formatted_price,
                'image' => self::getValidImageUrl($product->image),
                'status' => $product->status,
                'is_draft' => $product->is_draft,
                'status_label' => $product->status_label,
                'status_class' => $product->status_class,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name
                ] : null,
                'type_config' => $config,
                'productable_data' => self::transformProductableForList($product),
                'list_fields' => self::getListFields($product),
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ];
        })->toArray();
    }

    /**
     * Transformer pour le détail d'un produit
     */
    public static function transformForDetail(Product $product): array
    {
        $config = self::getProductConfig($product->productable_type);
        $baseData = self::transformForList(collect([$product]))[0];
        
        // Ajouter les données spécifiques au détail
        $detailData = [
            'productable_detail' => self::transformProductableForDetail($product),
            'relations' => self::getProductRelations($product),
            'statistics' => self::getProductStatistics($product),
            'tags' => $product->globalTags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color ?? '#6b7280'
            ]),
            'options' => $product->options->map(fn($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'price' => $option->price,
                'formatted_price' => number_format($option->price, 2, ',', ' ') . ' €'
            ]),
            'detail_fields' => self::getDetailFields($product)
        ];

        return array_merge($baseData, $detailData);
    }

    /**
     * Transformer pour le formulaire
     */
    public static function transformForForm(Product $product = null): array
    {
        if (!$product) {
            return [
                'name' => '',
                'description' => '',
                'price' => 0,
                'status' => true,
                'is_draft' => false,
                'image' => null,
                'category_id' => null,
                'productable' => []
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'status' => $product->status,
            'is_draft' => $product->is_draft,
            'image' => $product->image,
            'category_id' => $product->category_id,
            'productable' => $product->productable ? $product->productable->toArray() : [],
            'relations' => self::getFormRelations($product)
        ];
    }

    /**
     * Obtenir la configuration d'un type de produit
     */
    private static function getProductConfig(string $type): array
    {
        return self::$productConfigs[$type] ?? self::$productConfigs['App\\Models\\Activity'];
    }

    /**
     * Transformer les données productable pour la liste
     */
    private static function transformProductableForList(Product $product): array
    {
        if (!$product->productable) return [];

        $data = $product->productable->toArray();
        
        // Ajouter des champs calculés selon le type
        switch ($product->productable_type) {
            case 'App\\Models\\Menu':
                $data['dishes_count'] = $product->productable->dishes()->count();
                $data['total_price'] = $product->productable->dishes()
                    ->join('products', 'dishes.product_id', '=', 'products.id')
                    ->sum('products.price');
                break;
                
            case 'App\\Models\\Dish':
                $data['ingredients_count'] = $product->productable->ingredients()->count();
                $data['dietary_info'] = self::formatDietaryInfo($product->productable);
                break;
                
            case 'App\\Models\\Ingredient':
                $data['dietary_properties'] = self::getDietaryProperties($product->productable);
                break;
        }

        return $data;
    }

    /**
     * Transformer les données productable pour le détail
     */
    private static function transformProductableForDetail(Product $product): array
    {
        $data = self::transformProductableForList($product);
        
        // Ajouter des données détaillées selon le type
        switch ($product->productable_type) {
            case 'App\\Models\\Menu':
                $data['dishes'] = $product->productable->dishes()
                    ->with('product')
                    ->get()
                    ->map(fn($dish) => [
                        'id' => $dish->id,
                        'name' => $dish->product->name,
                        'description' => $dish->product->description,
                        'price' => $dish->product->price,
                        'formatted_price' => number_format($dish->product->price, 2, ',', ' ') . ' €',
                        'image' => self::getValidImageUrl($dish->product->image)
                    ]);
                break;
                
            case 'App\\Models\\Dish':
                $data['ingredients'] = $product->productable->ingredients()
                    ->with('product')
                    ->get()
                    ->map(fn($ingredient) => [
                        'id' => $ingredient->id,
                        'name' => $ingredient->product->name,
                        'stock' => $ingredient->stock,
                        'dietary_properties' => self::getDietaryProperties($ingredient)
                    ]);
                break;
        }

        return $data;
    }

    /**
     * Obtenir les champs à afficher dans la liste
     */
    private static function getListFields(Product $product): array
    {
        $config = self::getProductConfig($product->productable_type);
        $fields = [];
        
        foreach ($config['listColumns'] as $column) {
            $fields[$column] = [
                'label' => self::getFieldLabel($column),
                'value' => self::formatFieldValue($product->productable, $column),
                'type' => self::getFieldType($column)
            ];
        }
        
        return $fields;
    }

    /**
     * Obtenir les champs à afficher dans le détail
     */
    private static function getDetailFields(Product $product): array
    {
        $config = self::getProductConfig($product->productable_type);
        $fields = [];
        
        foreach ($config['detailFields'] as $field) {
            if ($product->productable && isset($product->productable->$field)) {
                $fields[$field] = [
                    'label' => self::getFieldLabel($field),
                    'value' => self::formatFieldValue($product->productable, $field),
                    'type' => self::getFieldType($field)
                ];
            }
        }
        
        return $fields;
    }

    /**
     * Obtenir les relations du produit
     */
    private static function getProductRelations(Product $product): array
    {
        $relations = [];
        
        switch ($product->productable_type) {
            case 'App\\Models\\Menu':
                $relations['dishes'] = $product->productable->dishes()
                    ->with('product')
                    ->get()
                    ->map(fn($dish) => [
                        'id' => $dish->id,
                        'product_id' => $dish->product->id,
                        'name' => $dish->product->name,
                        'price' => $dish->product->price
                    ]);
                break;
                
            case 'App\\Models\\Dish':
                $relations['ingredients'] = $product->productable->ingredients()
                    ->with('product')
                    ->get()
                    ->map(fn($ingredient) => [
                        'id' => $ingredient->id,
                        'product_id' => $ingredient->product->id,
                        'name' => $ingredient->product->name
                    ]);
                break;
        }
        
        return $relations;
    }

    /**
     * Obtenir les relations pour le formulaire
     */
    private static function getFormRelations(Product $product): array
    {
        $relations = self::getProductRelations($product);
        
        // Simplifier pour le formulaire
        foreach ($relations as $key => $items) {
            $relations[$key] = collect($items)->pluck('id')->toArray();
        }
        
        return $relations;
    }

    /**
     * Obtenir les statistiques du produit
     */
    private static function getProductStatistics(Product $product): array
    {
        return [
            'views' => $product->views ?? 0,
            'reservations_count' => $product->reservations()->count(),
            'total_revenue' => $product->reservations()->sum('total_price') ?? 0,
            'monthly_revenue' => $product->reservations()
                ->whereMonth('created_at', now()->month)
                ->sum('total_price') ?? 0,
            'average_rating' => round($product->reviews()->avg('rating') ?? 0, 2),
            'recent_reservations' => $product->reservations()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'date' => $r->created_at->format('d/m/Y H:i'),
                    'total' => number_format($r->total_price, 2, ',', ' ') . ' €'
                ])
        ];
    }

    /**
     * Utilitaires
     */
    private static function getValidImageUrl(?string $imageUrl): string
    {
        if (!$imageUrl) {
            return '/images/placeholder-product.svg';
        }
        
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return $imageUrl;
        }
        
        return asset('storage/' . $imageUrl);
    }

    private static function getFieldLabel(string $field): string
    {
        $labels = [
            'duration' => 'Durée',
            'capacity' => 'Capacité',
            'max_people' => 'Capacité max',
            'difficulty_level' => 'Difficulté',
            'guide' => 'Guide',
            'meeting_point' => 'Point RDV',
            'stock' => 'Stock',
            'is_vegetarian' => 'Végétarien',
            'is_vegan' => 'Végan',
            'dishes_count' => 'Nb plats',
            'ingredients_count' => 'Nb ingrédients',
            'total_price' => 'Prix total',
            'dietary_info' => 'Info diététique',
            'availability' => 'Disponibilité'
        ];
        
        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    public static function formatFieldValue($productable, string $field): string
    {
        if (!$productable || !isset($productable->$field)) {
            return '-';
        }
        
        $value = $productable->$field;
        
        switch ($field) {
            case 'duration':
                return $value . ' min';
            case 'capacity':
            case 'max_people':
                return $value . ' pers.';
            case 'stock':
                return $value > 0 ? $value . ' unités' : 'Rupture';
            case 'is_vegetarian':
            case 'is_vegan':
            case 'availability':
                return $value ? '✓' : '✗';
            case 'difficulty_level':
                $levels = ['easy' => 'Facile', 'medium' => 'Moyen', 'hard' => 'Difficile'];
                return $levels[$value] ?? $value;
            default:
                return (string) $value;
        }
    }

    private static function getFieldType(string $field): string
    {
        $types = [
            'is_vegetarian' => 'boolean',
            'is_vegan' => 'boolean',
            'availability' => 'boolean',
            'duration' => 'number',
            'capacity' => 'number',
            'stock' => 'number',
            'price' => 'currency'
        ];
        
        return $types[$field] ?? 'text';
    }

    private static function formatDietaryInfo($productable): string
    {
        $tags = [];
        if ($productable->is_vegan) $tags[] = 'Végan';
        elseif ($productable->is_vegetarian) $tags[] = 'Végétarien';
        if ($productable->is_spicy) $tags[] = 'Épicé';
        if ($productable->is_gluten_free) $tags[] = 'Sans gluten';
        
        return implode(', ', $tags) ?: 'Standard';
    }

    private static function getDietaryProperties($productable): array
    {
        return [
            'is_vegetarian' => $productable->is_vegetarian ?? false,
            'is_vegan' => $productable->is_vegan ?? false,
            'is_spicy' => $productable->is_spicy ?? false,
            'is_gluten_free' => $productable->is_gluten_free ?? false,
            'is_lactose_free' => $productable->is_lactose_free ?? false,
            'is_nut_free' => $productable->is_nut_free ?? false
        ];
    }
}