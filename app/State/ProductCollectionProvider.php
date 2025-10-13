<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\ProductTransformer;
use Illuminate\Support\Facades\Log;

class ProductCollectionProvider implements ProviderInterface
{
    public function __construct(private Request $request) {}


    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $query = Product::with([
            'category',
            'productable',
            'globalTags'
        ]);

        // Appliquer les filtres
        $this->applyFilters($query);
        
        // Pagination
        $perPage = (int) $this->request->query('per_page', 20);
        $page = (int) $this->request->query('page', 1);
        
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        
        // Charger conditionnellement les tags spécifiques pour les modèles qui les supportent
        $collection = $paginator->getCollection();

        $types = $collection->pluck('productable_type')->unique();
        $morphMap = [];

        foreach ($types as $type) {
            if (class_exists($type) && method_exists($type, 'specificTags')) {
                $morphMap[$type] = ['specificTags' => function ($query) {
                    $query->where('is_global', false);
                }];
            }
        }

        if (!empty($morphMap)) {
            $collection->loadMorph('productable', $morphMap);
        }

        // Transformer les données pour le frontend
        $collection->transform(function ($product) {
            return $this->transformProduct($product);
        });

        return $paginator;
    }

    private function applyFilters($query): void
    {
        // Filtre par type
        if ($type = $this->request->query('type')) {
            $query->where('products.productable_type', $type);
        }

        // Filtre par statut
        if ($status = $this->request->query('status')) {
            switch ($status) {
                case 'active':
                    $query->where('products.status', true)->where('products.is_draft', false);
                    break;
                case 'draft':
                    $query->where('products.is_draft', true);
                    break;
                case 'inactive':
                    $query->where('products.status', false)->where('products.is_draft', false);
                    break;
            }
        }

        // Filtre par catégorie
        if ($categoryId = $this->request->query('category_id')) {
            $query->where('products.category_id', $categoryId);
        }

        // Recherche
        if ($search = $this->request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'LIKE', "%{$search}%")
                    ->orWhere('products.description', 'LIKE', "%{$search}%");
            });
        }

        // Tri - IMPORTANT: spécifier la table pour éviter l'ambiguïté
        $sortBy = $this->request->query('sort_by', 'created_at');
        $sortDirection = $this->request->query('sort_direction', 'desc');

        $allowedSortFields = ['name', 'price', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy('products.' . $sortBy, $sortDirection);
        }
    }

    private function transformProduct($product): array
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
            'typeConfig' => $this->getTypeConfig($product->productable_type),
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,

            //  Tags globaux
            'globalTags' => $product->globalTags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'description' => $tag->description,
                'icon' => $tag->icon,
                'color' => '#6b7280' // Couleur par défaut
            ]),

            //  Tags spécifiques via productable
            'specificTags' => $this->getSpecificTags($product)
        ];

        // Ajouter les champs spécifiques pour la liste
        if ($product->productable) {
            $data['productable_data'] = $product->productable->toArray();
            $data['list_fields'] = $this->getListFields($product);
        }

        return $data;
    }

    //  Nouvelle méthode pour récupérer les tags spécifiques
    private function getSpecificTags($product): array
    {
        if (!$product->productable || !method_exists($product->productable, 'specificTags')) {
            return [];
        }

        // Charger les tags spécifiques si pas déjà chargés
        if (!$product->productable->relationLoaded('specificTags')) {
            $product->productable->load('specificTags');
        }

        return $product->productable->specificTags->map(fn($tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'description' => $tag->description,
            'icon' => $tag->icon,
            'color' => '#8b5cf6' // Couleur différente pour les tags spécifiques
        ])->toArray();
    }

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
        if (!$imageUrl) {
            return '/images/placeholder-product.svg';
        }

        // L'accesseur du modèle gère déjà la logique URL vs chemin local
        return $imageUrl;
    }

    public function getTypeConfig(string $type): array
    {
        return ProductTransformer::getTypeConfig($type);
    }

    private function getListFields($product): array
    {
        $fields = [];
        $productable = $product->productable;

        if (!$productable) return $fields;

        switch ($product->productable_type) {
            case 'App\\Models\\Activity':
                if (isset($productable->duration)) {
                    $fields['duration'] = [
                        'label' => 'Durée',
                        'value' => $productable->duration . ' min'
                    ];
                }
                if (isset($productable->max_people)) {
                    $fields['max_people'] = [
                        'label' => 'Capacité',
                        'value' => $productable->max_people . ' pers.'
                    ];
                }
                break;

            case 'App\\Models\\Room':
                if (isset($productable->capacity)) {
                    $fields['capacity'] = [
                        'label' => 'Capacité',
                        'value' => $productable->capacity . ' pers.'
                    ];
                }
                if (isset($productable->availability)) {
                    $fields['availability'] = [
                        'label' => 'Disponibilité',
                        'value' => $productable->availability ? 'Disponible' : 'Non disponible'
                    ];
                }
                break;

            case 'App\\Models\\Ingredient':
                if (isset($productable->stock)) {
                    $fields['stock'] = [
                        'label' => 'Stock',
                        'value' => $productable->stock > 0 ? $productable->stock . ' unités' : 'Rupture'
                    ];
                }
                if (isset($productable->is_vegetarian) && $productable->is_vegetarian) {
                    $fields['vegetarian'] = [
                        'label' => 'Végétarien',
                        'value' => '✓'
                    ];
                }
                break;
        }

        return $fields;
    }
}
