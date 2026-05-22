<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Data\CategoryOutputData;

class CategoryCollectionProvider implements ProviderInterface
{
    public function __construct(private Request $request) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $query = Category::query()->withCount('products');

        // Appliquer les filtres
        $this->applyFilters($query);

        // Tri par défaut
        $query->orderBy('type')->orderBy('name');

        // Pagination
        $perPage = (int) $this->request->query('per_page', 50);
        $page = (int) $this->request->query('page', 1);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformer les données
        $collection = $paginator->getCollection();
        $collection->transform(function ($category) {
            return $this->transformCategory($category);
        });

        return $paginator;
    }

    private function applyFilters($query): void
    {
        // Filtre par type
        if ($type = $this->request->query('type')) {
            $query->where('type', $type);
        }

        // Recherche dans nom et description
        if ($search = $this->request->query('search')) {
            $query->search($search);
        }
    }

    private function transformCategory($category): CategoryOutputData
    {
        return new CategoryOutputData(
            id: $category->id,
            type: $category->type,
            name: $category->name,
            description: $category->description,
            photo: $category->photo,
            photoUrl: $category->photo_url,
            typeLabel: $category->type_label,
            productCount: $category->products_count ?? 0,
            createdAt: $category->created_at,
            updatedAt: $category->updated_at
        );
    }
}
