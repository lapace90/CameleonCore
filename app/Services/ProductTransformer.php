<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductTransformer
{
    /**
     *  Transformer pour l'affichage détaillé avec gestion d'erreur
     */
    public static function transformForDisplay(Product $product): array
    {
        try {
            // Précharger les relations critiques pour éviter les requêtes N+1
            $product->load([
                'productable',
                'category',
                'globalTags',
                'options'
            ]);

            //  S'assurer que typeConfig est toujours présent
            $typeConfig = self::getTypeConfig($product->productable_type);
            
            //  S'assurer que productableDetail est toujours présent
            $productableDetail = [];
            if ($product->productable) {
                try {
                    $productableDetail = $product->productable->toArray();
                } catch (\Exception $e) {
                    Log::warning("Erreur lors de la sérialisation de productable pour le produit {$product->id}: " . $e->getMessage());
                    $productableDetail = [];
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'formatted_price' => number_format((float) $product->price, 2, ',', ' ') . ' €',
                'image' => $product->image,
                'status' => (bool) $product->status,
                'status_label' => $product->status ? 'Actif' : 'Inactif',
                'status_class' => $product->status ? 'success' : 'danger',
                'is_draft' => (bool) $product->is_draft,
                'productable_type' => $product->productable_type,
                'productableType' => $product->productable_type, // Alias pour compatibilité frontend
                
                //  Toujours inclure typeConfig
                'typeConfig' => $typeConfig,
                
                //  Toujours inclure productableDetail (même si vide)
                'productableDetail' => $productableDetail,
                
                //  Alias pour compatibilité avec l'ancien code
                'productableData' => $productableDetail,
                
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
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
                    'price' => (float) $option->price,
                    'formatted_price' => number_format((float) $option->price, 2, ',', ' ') . ' €'
                ])->toArray(),
                
                //  Relations avec gestion d'erreur
                'relations' => self::getRelations($product),
                
                //  Champs de détail avec gestion d'erreur
                'detail_fields' => self::getDetailFields($product),
                
                //  Statistiques avec gestion d'erreur pour les réservations
                'statistics' => self::getStatistics($product),
                
                'created_at' => $product->created_at?->toISOString(),
                'updated_at' => $product->updated_at?->toISOString(),
            ];
        } catch (\Exception $e) {
            Log::error("Erreur lors de la transformation du produit {$product->id}: " . $e->getMessage());
            
            // Retourner une version minimale en cas d'erreur
            return [
                'id' => $product->id,
                'name' => $product->name ?? 'Produit sans nom',
                'description' => $product->description ?? '',
                'price' => (float) ($product->price ?? 0),
                'typeConfig' => self::getTypeConfig($product->productable_type),
                'productableDetail' => [],
                'productableData' => [],
                'error' => 'Erreur lors du chargement des détails'
            ];
        }
    }

    /**
     *  Récupération des relations avec gestion d'erreur
     */
    private static function getRelations(Product $product): array
    {
        $relations = [];
        
        if (!$product->productable) {
            return $relations;
        }
        
        try {
            switch ($product->productable_type) {
                case 'App\\Models\\Menu':
                    if ($product->productable->relationLoaded('dishes')) {
                        $relations['dishes'] = $product->productable->dishes->map(fn($dish) => [
                            'id' => $dish->id,
                            'product_id' => $dish->product->id ?? null,
                            'name' => $dish->product->name ?? 'N/A',
                            'price' => (float) ($dish->product->price ?? 0),
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
        } catch (\Exception $e) {
            Log::warning("Erreur lors du chargement des relations pour le produit {$product->id}: " . $e->getMessage());
        }
        
        return $relations;
    }

    /**
     *  Récupération des statistiques avec gestion d'erreur pour les réservations
     */
    private static function getStatistics(Product $product): array
    {
        try {
            //  Éviter l'erreur SQL "created_at" ambiguë
            $reservationsCount = $product->reservations()
                ->select('reservations.id') // Spécifier explicitement la colonne
                ->count();
                
            return [
                'views' => 0, // À implémenter
                'reservations_count' => $reservationsCount,
                'average_rating' => 0, // À implémenter
                'total_revenue' => 0 // À implémenter
            ];
        } catch (\Exception $e) {
            Log::error("Erreur lors du chargement des statistiques pour le produit {$product->id}: " . $e->getMessage());
            
            return [
                'views' => 0,
                'reservations_count' => 0,
                'average_rating' => 0,
                'total_revenue' => 0
            ];
        }
    }

    /**
     *  Configuration des types robuste
     */
    public static function getTypeConfig(?string $type): array
    {
        $configs = [
            'App\\Models\\Activity' => [
                'label' => 'Activités',
                'singular' => 'Activité',
                'icon' => 'fas fa-hiking',
                'color' => '#3b82f6',
                'hasRelation' => null,
                'fields' => ['guide', 'duration', 'meeting_point', 'max_people', 'difficulty_level']
            ],
            'App\\Models\\Menu' => [
                'label' => 'Menus',
                'singular' => 'Menu',
                'icon' => 'fas fa-utensils',
                'color' => '#10b981',
                'hasRelation' => 'dishes',
                'fields' => []
            ],
            'App\\Models\\Dish' => [
                'label' => 'Plats',
                'singular' => 'Plat',
                'icon' => 'fas fa-drumstick-bite',
                'color' => '#f97316',
                'hasRelation' => 'ingredients',
                'fields' => []
            ],
            'App\\Models\\Ingredient' => [
                'label' => 'Ingrédients',
                'singular' => 'Ingrédient',
                'icon' => 'fas fa-seedling',
                'color' => '#22c55e',
                'hasRelation' => 'dishes',
                'fields' => ['stock', 'is_vegetarian', 'is_vegan', 'is_spicy', 'is_gluten_free', 'is_lactose_free', 'is_nut_free']
            ],
            'App\\Models\\Room' => [
                'label' => 'Hébergements',
                'singular' => 'Hébergement',
                'icon' => 'fas fa-bed',
                'color' => '#f59e0b',
                'hasRelation' => null,
                'fields' => ['capacity', 'availability']
            ]
        ];

        return $configs[$type] ?? [
            'label' => 'Produit',
            'singular' => 'Produit',
            'icon' => 'fas fa-box',
            'color' => '#6b7280',
            'hasRelation' => null,
            'fields' => []
        ];
    }

    /**
     *  Champs de détail avec gestion d'erreur
     */
    private static function getDetailFields(Product $product): array
    {
        $fields = [];
        
        if (!$product->productable) {
            return $fields;
        }
        
        try {
            $productable = $product->productable;

            switch ($product->productable_type) {
                case 'App\\Models\\Activity':
                    $fields['guide'] = ['label' => 'Guide', 'value' => $productable->guide ?? 'Non défini'];
                    $fields['duration'] = ['label' => 'Durée', 'value' => $productable->duration ?? 'Non définie'];
                    $fields['meeting_point'] = ['label' => 'Point de rendez-vous', 'value' => $productable->meeting_point ?? 'Non défini'];
                    $fields['max_people'] = ['label' => 'Nombre max de personnes', 'value' => $productable->max_people ?? 'Non défini'];
                    $fields['difficulty_level'] = ['label' => 'Niveau de difficulté', 'value' => $productable->difficulty_level ?? 'Non défini'];
                    break;

                case 'App\\Models\\Room':
                    $fields['capacity'] = ['label' => 'Capacité', 'value' => $productable->capacity ?? 'Non définie'];
                    $fields['availability'] = ['label' => 'Disponibilité', 'value' => $productable->availability ? 'Disponible' : 'Non disponible'];
                    break;

                case 'App\\Models\\Ingredient':
                    $fields['stock'] = ['label' => 'Stock', 'value' => $productable->stock ?? 0];
                    
                    // Propriétés alimentaires
                    $foodProperties = [
                        'is_vegetarian' => 'Végétarien',
                        'is_vegan' => 'Végan',
                        'is_spicy' => 'Épicé',
                        'is_gluten_free' => 'Sans gluten',
                        'is_lactose_free' => 'Sans lactose',
                        'is_nut_free' => 'Sans noix'
                    ];
                    
                    foreach ($foodProperties as $property => $label) {
                        if (isset($productable->$property)) {
                            $fields[$property] = [
                                'label' => $label,
                                'value' => $productable->$property ? 'Oui' : 'Non'
                            ];
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::warning("Erreur lors de la récupération des champs de détail pour le produit {$product->id}: " . $e->getMessage());
        }
        
        return $fields;
    }

    /**
     * Transformer pour les formulaires
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
                'productableData' => [],
                'productableDetail' => []
            ];
        }

        $productableDetail = [];
        if ($product->productable) {
            try {
                $productableDetail = $product->productable->toArray();
            } catch (\Exception $e) {
                Log::warning("Erreur lors de la sérialisation pour le formulaire du produit {$product->id}: " . $e->getMessage());
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'status' => (bool) $product->status,
            'is_draft' => (bool) $product->is_draft,
            'image' => $product->image,
            'category_id' => $product->category_id,
            'productableData' => $productableDetail,
            'productableDetail' => $productableDetail,
            'typeConfig' => self::getTypeConfig($product->productable_type)
        ];
    }

    /**
     * Transformer pour les listes
     */
    public static function transformForList(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'formatted_price' => number_format((float) $product->price, 2, ',', ' ') . ' €',
            'image' => $product->image,
            'status' => (bool) $product->status,
            'status_label' => $product->status ? 'Actif' : 'Inactif',
            'status_class' => $product->status ? 'success' : 'danger',
            'productable_type' => $product->productable_type,
            'typeConfig' => self::getTypeConfig($product->productable_type),
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
        ];
    }
}