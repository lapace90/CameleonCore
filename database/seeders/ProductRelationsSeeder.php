<?php
// database/seeders/ProductRelationsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\Ingredient;

class ProductRelationsSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🔗 Création des relations entre produits...');

        // 1. Associer les ingrédients aux plats existants (table pivot dish_ingredient)
        $this->createDishIngredientRelations();

        $this->command->info('✅ Relations créées avec succès !');
    }

    private function createDishIngredientRelations()
    {
        // Récupérer tous les plats et ingrédients
        $dishProducts = Product::where('productable_type', 'App\\Models\\Dish')->get();
        $ingredientProducts = Product::where('productable_type', 'App\\Models\\Ingredient')->get();

        if ($dishProducts->isEmpty() || $ingredientProducts->isEmpty()) {
            $this->command->warn('⚠️ Pas de plats ou d\'ingrédients trouvés pour créer les relations');
            return;
        }

        $this->command->info("📝 Association de {$dishProducts->count()} plats avec {$ingredientProducts->count()} ingrédients...");

        foreach ($dishProducts as $dishProduct) {
            // Récupérer le modèle Dish correspondant
            $dish = Dish::find($dishProduct->productable_id);
            if (!$dish) continue;

            // Sélectionner 3 à 6 ingrédients aléatoires pour chaque plat
            $randomIngredientProducts = $ingredientProducts->random(rand(3, min(6, $ingredientProducts->count())));
            
            $ingredientIds = [];
            $ingredientNames = [];
            
            foreach ($randomIngredientProducts as $ingredientProduct) {
                // Trouver l'ingrédient correspondant via productable_id
                $ingredient = Ingredient::find($ingredientProduct->productable_id);
                if ($ingredient) {
                    $ingredientIds[] = $ingredient->id;
                    $ingredientNames[] = $ingredientProduct->name; // Nom du Product
                }
            }

            // Associer les ingrédients au plat via la table pivot dish_ingredient
            if (!empty($ingredientIds)) {
                $dish->ingredients()->sync($ingredientIds);
                
                $this->command->info("  🍽️ {$dishProduct->name} → " . implode(', ', $ingredientNames));
            }
        }
    }
}

