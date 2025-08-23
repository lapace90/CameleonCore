<?php
// app/State/CategoryItemProvider.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Category;
use App\Data\CategoryOutputData;

class CategoryItemProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|null
    {
        $category = Category::withCount('products')->find($uriVariables['id']);
        
        if (!$category) {
            return null;
        }

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