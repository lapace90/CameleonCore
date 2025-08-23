<?php
// app/State/CategoryProcessor.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Category;
use App\Data\CategoryData;
use App\Data\CategoryOutputData;
use Illuminate\Support\Facades\Log;

class CategoryProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        switch (true) {
            case $operation instanceof Post:
                return $this->create($data);

            case $operation instanceof Put:
                return $this->update($data, (int)($uriVariables['id'] ?? 0), isPatch: false);

            case $operation instanceof Patch:
                return $this->update($data, (int)($uriVariables['id'] ?? 0), isPatch: true);

            case $operation instanceof Delete:
                $this->delete((int)($uriVariables['id'] ?? 0));
                return null;

            default:
                throw new \InvalidArgumentException('Unsupported operation: ' . get_class($operation));
        }
    }

    private function create(CategoryData $data): CategoryOutputData
    {
        Log::info('Creating category', ['data' => $data]);

        $category = Category::create([
            'type' => $data->type,
            'name' => $data->name,
            'description' => $data->description,
            'photo' => $data->photo,
        ]);

        $category->loadCount('products');

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

    private function update(CategoryData $data, int $id, bool $isPatch = false): CategoryOutputData
    {
        $category = Category::findOrFail($id);

        // PATCH : uniquement les champs fournis (même si null explicite)
        // PUT   : remplace tout
        $attributes = $isPatch
            ? $this->onlyProvidedAttributes($data)
            : [
                'type'        => $data->type,
                'name'        => $data->name,
                'description' => $data->description,
                'photo'       => $data->photo,
            ];

        if (!empty($attributes)) {
            $category->update($attributes);
        }

        $category->loadCount('products');

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

    private function onlyProvidedAttributes(CategoryData $data): array
    {
        $attrs = [];

        if (isset($data->type)) {
            $attrs['type'] = $data->type;
        }
        if (isset($data->name)) {
            $attrs['name'] = $data->name;
        }
        if (isset($data->description)) {
            $attrs['description'] = $data->description;
        }
        if (isset($data->photo)) {
            $attrs['photo'] = $data->photo;
        }

        return $attrs;
    }

    private function delete(int $id): void
    {
        $category = Category::findOrFail($id);

        // Vérifier qu'il n'y a pas de produits associés
        if ($category->products()->count() > 0) {
            throw new \InvalidArgumentException(
                'Impossible de supprimer une catégorie qui contient des produits'
            );
        }

        $category->delete();
    }
}
