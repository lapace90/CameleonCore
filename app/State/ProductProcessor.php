<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Product;
use App\Models\Activity;
use App\Models\Menu;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Room;
use App\Services\ProductTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return DB::transaction(function () use ($data, $operation, $uriVariables) {
            $isNew = $operation->getName() === 'post';
            
            if ($isNew) {
                $product = $this->createProduct($data);
            } else {
                $product = $this->updateProduct($data, $uriVariables['id']);
            }
            
            return ProductTransformer::transformForDetail($product);
        });
    }
    
    private function createProduct(array $data): Product
    {
        // Créer l'entité productable
        $productable = $this->createProductable($data);
        
        // Créer le produit
        $product = Product::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'status' => $data['status'] ?? true,
            'is_draft' => $data['is_draft'] ?? false,
            'category_id' => $data['category_id'] ?? null,
            'productable_type' => get_class($productable),
            'productable_id' => $productable->id
        ]);
        
        // Gérer l'image
        if (isset($data['image'])) {
            $this->handleImage($product, $data['image']);
        }
        
        // Gérer les tags
        if (isset($data['tags'])) {
            $product->globalTags()->attach($data['tags']);
        }
        
        // Gérer les relations
        $this->handleRelations($product, $productable, $data);
        
        return $product->loadMissing(['category', 'productable', 'globalTags']);
    }
    
    private function updateProduct(array $data, int $productId): Product
    {
        $product = Product::findOrFail($productId);
        
        // Mettre à jour le produit
        $product->update(array_filter([
            'name' => $data['name'] ?? $product->name,
            'description' => $data['description'] ?? $product->description,
            'price' => $data['price'] ?? $product->price,
            'status' => $data['status'] ?? $product->status,
            'is_draft' => $data['is_draft'] ?? $product->is_draft,
            'category_id' => $data['category_id'] ?? $product->category_id
        ]));
        
        // Mettre à jour l'image
        if (isset($data['image'])) {
            $this->handleImage($product, $data['image']);
        }
        
        // Mettre à jour les tags
        if (isset($data['tags'])) {
            $product->globalTags()->sync($data['tags']);
        }
        
        // Mettre à jour le productable
        if (isset($data['productable']) && $product->productable) {
            $this->updateProductable($product->productable, $data['productable']);
        }
        
        // Mettre à jour les relations
        if (isset($data['relations'])) {
            $this->handleRelations($product, $product->productable, $data);
        }
        
        return $product->loadMissing(['category', 'productable', 'globalTags']);
    }
    
    private function createProductable(array $data): mixed
    {
        $productableData = $data['productable'] ?? [];
        $type = $data['productableType'] ?? $data['productable_type'];
        
        switch ($type) {
            case 'App\\Models\\Activity':
                return Activity::create([
                    'guide' => $productableData['guide'] ?? null,
                    'duration' => $productableData['duration'] ?? 60,
                    'meeting_point' => $productableData['meeting_point'] ?? null,
                    'max_people' => $productableData['max_people'] ?? 10,
                    'difficulty_level' => $productableData['difficulty_level'] ?? 'medium'
                ]);
                
            case 'App\\Models\\Room':
                return Room::create([
                    'capacity' => $productableData['capacity'] ?? 2,
                    'availability' => $productableData['availability'] ?? true
                ]);
                
            case 'App\\Models\\Menu':
                return Menu::create([]);
                
            case 'App\\Models\\Dish':
                return Dish::create([]);
                
            case 'App\\Models\\Ingredient':
                return Ingredient::create([
                    'stock' => $productableData['stock'] ?? 0,
                    'is_vegetarian' => $productableData['is_vegetarian'] ?? false,
                    'is_vegan' => $productableData['is_vegan'] ?? false,
                    'is_spicy' => $productableData['is_spicy'] ?? false,
                    'is_gluten_free' => $productableData['is_gluten_free'] ?? false,
                    'is_lactose_free' => $productableData['is_lactose_free'] ?? false,
                    'is_nut_free' => $productableData['is_nut_free'] ?? false
                ]);
                
            default:
                throw new \InvalidArgumentException("Type de productable non supporté: {$type}");
        }
    }
    
    private function updateProductable(mixed $productable, array $data): void
    {
        $productable->update($data);
    }
    
    private function handleRelations(Product $product, mixed $productable, array $data): void
    {
        // Relations des plats
        if ($productable instanceof Dish && isset($data['relations']['ingredients'])) {
            $productable->ingredients()->sync($data['relations']['ingredients']);
        }
        
        // Relations des menus
        if ($productable instanceof Menu && isset($data['relations']['dishes'])) {
            $productable->dishes()->sync($data['relations']['dishes']);
        }
        
        // Options du produit
        if (isset($data['options'])) {
            $product->options()->sync($data['options']);
        }
    }
    
    private function handleImage(Product $product, $image): void
    {
        // Supprimer l'ancienne image si elle existe
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        
        // Si c'est un fichier uploadé
        if ($image instanceof \Illuminate\Http\UploadedFile) {
            $path = $image->store('products', 'public');
            $product->update(['image' => $path]);
        }
        // Si c'est une URL
        elseif (filter_var($image, FILTER_VALIDATE_URL)) {
            $product->update(['image' => $image]);
        }
        // Si c'est un chemin
        elseif (is_string($image)) {
            $product->update(['image' => $image]);
        }
    }
}