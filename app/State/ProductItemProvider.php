<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Product;
use App\Data\ProductOutputData;
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
        
        // ✅ CHARGER les relations pivot correctement
        $this->loadRelations($product);
        
        // ✅ RETOURNER ProductOutputData au lieu du modèle brut
        return ProductOutputData::fromProduct($product);
    }
    
    private function loadRelations(Product $product): void
    {
        try {
            switch ($product->productable_type) {
                case 'App\\Models\\Menu':
                    // ✅ CORRECT : Charger les plats du menu (table pivot dish_menu)
                    $product->productable->load(['dishes' => function($query) {
                        $query->with('product'); // Charger le Product de chaque Dish
                    }]);
                    break;
                    
                case 'App\\Models\\Dish':
                    // ✅ CORRECT : Charger les ingrédients du plat (table pivot dish_ingredient)
                    $product->productable->load(['ingredients' => function($query) {
                        $query->with('product'); // Charger le Product de chaque Ingredient
                    }]);
                    break;
                    
                case 'App\\Models\\Ingredient':
                    // ✅ CORRECT : Charger les plats qui utilisent cet ingrédient
                    $product->productable->load(['dishes' => function($query) {
                        $query->with('product');
                    }]);
                    break;
            }

            // Charger les réservations récentes
            // $product->load(['reservations' => function ($query) {
            //     $query->latest('created_at')->limit(5);
            // }]);
            
        } catch (\Exception $e) {
            Log::warning("Erreur lors du chargement des relations pour le produit {$product->id}: " . $e->getMessage());
        }
    }
}