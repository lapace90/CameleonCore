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

        // 2. ✅ AJOUT : Associer les plats aux menus (table pivot dish_menu)
        $this->createMenuDishRelations();

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

    // ✅ MÉTHODE MANQUANTE À AJOUTER
    private function createMenuDishRelations()
    {
        // Récupérer tous les menus et plats
        $menuProducts = Product::where('productable_type', 'App\\Models\\Menu')->get();
        $dishProducts = Product::where('productable_type', 'App\\Models\\Dish')->get();

        if ($menuProducts->isEmpty() || $dishProducts->isEmpty()) {
            $this->command->warn('⚠️ Pas de menus ou de plats trouvés pour créer les relations menu-plats');
            return;
        }

        $this->command->info("🥘 Association de {$menuProducts->count()} menus avec {$dishProducts->count()} plats...");

        foreach ($menuProducts as $menuProduct) {
            // Récupérer le modèle Menu correspondant
            $menu = Menu::find($menuProduct->productable_id);
            if (!$menu) continue;

            // Sélectionner 2 à 4 plats aléatoires pour chaque menu
            $randomDishProducts = $dishProducts->random(rand(2, min(4, $dishProducts->count())));
            
            $dishIds = [];
            $dishNames = [];
            
            foreach ($randomDishProducts as $dishProduct) {
                // Trouver le plat correspondant via productable_id
                $dish = Dish::find($dishProduct->productable_id);
                if ($dish) {
                    $dishIds[] = $dish->id;
                    $dishNames[] = $dishProduct->name; // Nom du Product
                }
            }

            // Associer les plats au menu via la table pivot dish_menu
            if (!empty($dishIds)) {
                $menu->dishes()->sync($dishIds);
                
                $this->command->info("  📋 {$menuProduct->name} → " . implode(', ', $dishNames));
            }
        }
    }
}