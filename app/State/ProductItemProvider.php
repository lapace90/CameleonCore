<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductItemProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $product = Product::with([
            'category',
            'productable',
            'globalTags',
            'options'
        ])->find($uriVariables['id']);
        
        if (!$product) {
            return null;
        }
        
        // Eager loading conditionnel selon le type
        $this->loadRelations($product);
        
        // Retourner le modèle directement - ApiPlatform se charge de la sérialisation
        return $product;
    }
    
    private function loadRelations(Product $product): void
    {
        try {
            switch ($product->productable_type) {
                case 'App\\Models\\Menu':
                    // Charger les plats avec leurs produits
                    if (method_exists($product->productable, 'dishes')) {
                        $product->load(['productable.dishes.product']);
                    }
                    break;
                    
                case 'App\\Models\\Dish':
                    // Charger les ingrédients avec leurs produits
                    if (method_exists($product->productable, 'ingredients')) {
                        $product->load(['productable.ingredients.product']);
                    }
                    break;
                    
                case 'App\\Models\\Ingredient':
                    // Charger les plats qui utilisent cet ingrédient
                    if (method_exists($product->productable, 'dishes')) {
                        $product->load(['productable.dishes.product']);
                    }
                    break;
            }

            // Charger les réservations récentes de façon sécurisée
            $product->load(['reservations' => function ($query) {
                $query->select('reservations.*')
                      ->latest('reservations.created_at')
                      ->limit(5);
            }]);
        } catch (\Exception $e) {
            // Si une relation échoue, on continue sans planter
            Log::warning("Erreur lors du chargement des relations pour le produit {$product->id}: " . $e->getMessage());
        }
    }
    
    // Méthode de transformation personnalisée - commentée pour laisser ApiPlatform gérer
    private function transformForDetail(Product $product): array
    {
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'formatted_price' => number_format($product->price, 2, ',', ' ') . ' €',
            'status' => $product->status,
            'is_draft' => $product->is_draft,
            'status_label' => $this->getStatusLabel($product),
            'status_class' => $this->getStatusClass($product),
            'image' => $this->getValidImageUrl($product->image),
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name
            ] : null,
            'type_config' => $this->getTypeConfig($product->productable_type),
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at
        ];

        // Données productable complètes
        if ($product->productable) {
            $data['productable_detail'] = $product->productable->toArray();
            $data['detail_fields'] = $this->getDetailFields($product);
            
            // Relations spécifiques
            $data['relations'] = $this->getProductRelations($product);
        }

        // Statistiques
        $data['statistics'] = $this->getProductStatistics($product);

        // Tags et options
        $data['tags'] = $product->globalTags->map(fn($tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color ?? '#6b7280'
        ]);

        $data['options'] = $product->options->map(fn($option) => [
            'id' => $option->id,
            'name' => $option->name,
            'price' => $option->price,
            'formatted_price' => number_format($option->price, 2, ',', ' ') . ' €'
        ]);

        return $data;
    }

    private function getDetailFields($product): array
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
                $fields['difficulty_level'] = ['label' => 'Difficulté', 'value' => $this->formatDifficulty($productable->difficulty_level ?? 'medium')];
                break;
                
            case 'App\\Models\\Room':
                $fields['capacity'] = ['label' => 'Capacité', 'value' => ($productable->capacity ?? 0) . ' pers.'];
                $fields['availability'] = ['label' => 'Disponibilité', 'value' => ($productable->availability ?? false) ? 'Disponible' : 'Non disponible'];
                break;
                
            case 'App\\Models\\Ingredient':
                $fields['stock'] = ['label' => 'Stock', 'value' => ($productable->stock ?? 0) . ' unités'];
                $fields['dietary_info'] = ['label' => 'Info diététique', 'value' => $this->formatDietaryInfo($productable)];
                break;
        }

        return $fields;
    }

    private function getProductRelations($product): array
    {
        $relations = [];
        
        switch ($product->productable_type) {
            case 'App\\Models\\Menu':
                if ($product->productable->dishes ?? false) {
                    $relations['dishes'] = $product->productable->dishes->map(fn($dish) => [
                        'id' => $dish->id,
                        'product_id' => $dish->product->id ?? null,
                        'name' => $dish->product->name ?? 'N/A',
                        'price' => $dish->product->price ?? 0,
                        'formatted_price' => number_format($dish->product->price ?? 0, 2, ',', ' ') . ' €'
                    ]);
                }
                break;
                
            case 'App\\Models\\Dish':
                if ($product->productable->ingredients ?? false) {
                    $relations['ingredients'] = $product->productable->ingredients->map(fn($ingredient) => [
                        'id' => $ingredient->id,
                        'product_id' => $ingredient->product->id ?? null,
                        'name' => $ingredient->product->name ?? 'N/A',
                        'stock' => $ingredient->stock ?? 0
                    ]);
                }
                break;
        }
        
        return $relations;
    }

    private function getProductStatistics($product): array
    {
        return [
            'views' => $product->views ?? 0,
            'reservations_count' => $product->reservations->count(),
            'total_revenue' => 0, // TODO: adapter selon votre structure BDD
            'monthly_revenue' => 0, // TODO: adapter selon votre structure BDD
            'average_rating' => 0, // TODO: implémenter les reviews
            'recent_reservations' => $product->reservations->map(fn($r) => [
                'id' => $r->id,
                'date' => $r->created_at->format('d/m/Y H:i'),
                'total' => '0,00 €' // TODO: adapter selon votre colonne de prix
            ])
        ];
    }

    // Méthodes utilitaires (identiques à ProductCollectionProvider)
    private function getStatusLabel($product): string
    {
        if ($product->is_draft) return 'Brouillon';
        return $product->status ? 'Actif' : 'Inactif';
    }

    private function getStatusClass($product): string
    {
        if ($product->is_draft) return 'status-draft';
        return $product->status ? 'status-active' : 'status-inactive';
    }

    private function getValidImageUrl(?string $imageUrl): string
    {
        if (!$imageUrl) return '/images/placeholder-product.svg';
        // L'accesseur du modèle gère déjà la logique URL vs chemin local
        return $imageUrl;
    }

    private function getTypeConfig(string $type): array
    {
        $configs = [
            'App\\Models\\Activity' => [
                'label' => 'Activités',
                'singular' => 'Activité',
                'icon' => 'fas fa-hiking',
                'color' => '#3b82f6'
            ],
            'App\\Models\\Menu' => [
                'label' => 'Menus',
                'singular' => 'Menu',
                'icon' => 'fas fa-utensils',
                'color' => '#10b981'
            ],
            'App\\Models\\Dish' => [
                'label' => 'Plats',
                'singular' => 'Plat',
                'icon' => 'fas fa-drumstick-bite',
                'color' => '#f97316'
            ],
            'App\\Models\\Ingredient' => [
                'label' => 'Ingrédients',
                'singular' => 'Ingrédient',
                'icon' => 'fas fa-seedling',
                'color' => '#22c55e'
            ],
            'App\\Models\\Room' => [
                'label' => 'Hébergements',
                'singular' => 'Hébergement',
                'icon' => 'fas fa-bed',
                'color' => '#f59e0b'
            ]
        ];

        return $configs[$type] ?? $configs['App\\Models\\Activity'];
    }

    private function formatDifficulty(?string $level): string
    {
        $levels = [
            'easy' => 'Facile',
            'medium' => 'Moyen',
            'hard' => 'Difficile'
        ];
        return $levels[$level] ?? 'Moyen';
    }

    private function formatDietaryInfo($productable): string
    {
        $tags = [];
        if ($productable->is_vegan ?? false) $tags[] = 'Végan';
        elseif ($productable->is_vegetarian ?? false) $tags[] = 'Végétarien';
        if ($productable->is_spicy ?? false) $tags[] = 'Épicé';
        if ($productable->is_gluten_free ?? false) $tags[] = 'Sans gluten';
        if ($productable->is_lactose_free ?? false) $tags[] = 'Sans lactose';
        if ($productable->is_nut_free ?? false) $tags[] = 'Sans noix';

        return implode(', ', $tags) ?: 'Standard';
    }
}