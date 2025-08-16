<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductCollectionProvider implements ProviderInterface
{
    public function __construct(private Request $request) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $query = Product::with([
            'category',
            'productable',
            'globalTags',
            'specificTags'
        ]);

        // Appliquer les filtres
        $this->applyFilters($query);

        // Pagination
        $perPage = (int) $this->request->query('per_page', 20);
        $page = (int) $this->request->query('page', 1);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformer les données pour le frontend
        $paginator->getCollection()->transform(function ($product) {
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
            'type_config' => $this->getTypeConfig($product->productable_type),
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at
        ];

        // Ajouter les champs spécifiques pour la liste
        if ($product->productable) {
            $data['productable_data'] = $product->productable->toArray();
            $data['list_fields'] = $this->getListFields($product);
        }

        return $data;
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
