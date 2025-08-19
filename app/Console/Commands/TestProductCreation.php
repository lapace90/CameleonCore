<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Data\ProductData;
use App\Data\ProductableData;
use App\State\ProductProcessor;
use ApiPlatform\Metadata\Post;
use Illuminate\Support\Facades\Log;

class TestProductCreation extends Command
{
    protected $signature = 'test:product-creation';
    protected $description = 'Test la création de produits avec Laravel Data';

    public function handle()
    {
        $this->info('🧪 Test de création de produit...');

        try {
            // Créer une catégorie de test si elle n'existe pas
            $category = \App\Models\Category::firstOrCreate(
                ['name' => 'Test Category', 'type' => 'Menu'],
                ['description' => 'Catégorie de test']
            );
            
            $this->info('📂 Catégorie de test créée/trouvée: ' . $category->name);

            // Test 1: Menu simple
            $this->info('📝 Test 1: Création d\'un menu');
            $menuData = [
                'name' => 'Menu du jour',
                'description' => 'Menu principal de la journée',
                'price' => '15.50',
                'productableType' => 'App\\Models\\Menu',
                'productable' => [],
                'status' => true,
                'is_draft' => false,
                'category_id' => $category->id
            ];

            $processor = new ProductProcessor();
            $result = $processor->process($menuData, new Post(), []);
            
            $this->info('✅ Menu créé avec succès: ' . $result->name);

            // Test 2: Activité avec données productable
            $this->info('📝 Test 2: Création d\'une activité');
            
            // Créer une catégorie Activity si nécessaire
            $activityCategory = \App\Models\Category::firstOrCreate(
                ['name' => 'Test Activity', 'type' => 'Activity'],
                ['description' => 'Catégorie activité de test']
            );
            
            $activityData = [
                'name' => 'Randonnée en montagne',
                'description' => 'Belle randonnée avec vue panoramique',
                'price' => '45.00',
                'productableType' => 'App\\Models\\Activity',
                'productable' => [
                    'guide' => 'Pierre Dupont',
                    'duration' => 240,
                    'meeting_point' => 'Parking du lac',
                    'max_people' => 12,
                    'difficulty_level' => 'medium'
                ],
                'status' => true,
                'is_draft' => false,
                'category_id' => $activityCategory->id
            ];

            $result2 = $processor->process($activityData, new Post(), []);
            $this->info('✅ Activité créée avec succès: ' . $result2->name);

            // Test 3: Ingrédient
            $this->info('📝 Test 3: Création d\'un ingrédient');
            $ingredientData = [
                'name' => 'Tomate bio',
                'description' => 'Tomates biologiques locales',
                'price' => '3.50',
                'productableType' => 'App\\Models\\Ingredient',
                'productable' => [
                    'stock' => 50,
                    'is_vegetarian' => true,
                    'is_vegan' => true,
                    'is_gluten_free' => true,
                    'is_lactose_free' => true,
                    'is_nut_free' => true
                ],
                'status' => true,
                'is_draft' => false,
                'category_id' => $category->id // Réutiliser la catégorie Menu
            ];

            $result3 = $processor->process($ingredientData, new Post(), []);
            $this->info('✅ Ingrédient créé avec succès: ' . $result3->name);

        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            $this->error('📍 Fichier: ' . $e->getFile() . ':' . $e->getLine());
            
            // Si c'est une erreur de validation Laravel
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $this->error('🔍 Erreurs de validation: ' . json_encode($e->errors(), JSON_PRETTY_PRINT));
            }
            
            $this->error('🔧 Stack trace:');
            $this->error($e->getTraceAsString());
        }
    }
}