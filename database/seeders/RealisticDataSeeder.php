<?php
// database/seeders/RealisticDataSeeder.php - VERSION CORRIGÉE
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\Activity;
use App\Models\Room;
use App\Models\Product;
use App\Models\Option;
use Illuminate\Support\Facades\Schema;

class RealisticDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🏗️ Création des produits avec les catégories existantes...');

        // 1. RÉCUPÉRER LES CATÉGORIES EXISTANTES (créées par CampCategoriesSeeder)
        $categoryModels = $this->getCategoriesMap();

        // 2. CRÉER LES VRAIS INGRÉDIENTS
        $ingredients = [
            // Légumes
            ['name' => 'Tomate', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 50, 'category' => 'Légumes'],
            ['name' => 'Salade verte', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 30, 'category' => 'Légumes'],
            ['name' => 'Concombre', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 25, 'category' => 'Légumes'],
            ['name' => 'Carotte', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 40, 'category' => 'Légumes'],
            ['name' => 'Poivron rouge', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 20, 'category' => 'Légumes'],
            ['name' => 'Oignon', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 35, 'category' => 'Légumes'],
            ['name' => 'Champignon', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 15, 'category' => 'Légumes'],
            
            // Protéines
            ['name' => 'Agneau du désert', 'is_vegetarian' => false, 'is_vegan' => false, 'is_gluten_free' => true, 'stock' => 20, 'category' => 'Protéines'],
            ['name' => 'Volaille fermière', 'is_vegetarian' => false, 'is_vegan' => false, 'is_gluten_free' => true, 'stock' => 15, 'category' => 'Protéines'],
            ['name' => 'Poisson d\'eau douce', 'is_vegetarian' => false, 'is_vegan' => false, 'is_gluten_free' => true, 'stock' => 12, 'category' => 'Protéines'],
            
            // Épices
            ['name' => 'Safran', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 5, 'category' => 'Épices'],
            ['name' => 'Ras el hanout', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 10, 'category' => 'Épices'],
            ['name' => 'Harissa', 'is_vegetarian' => true, 'is_vegan' => true, 'is_spicy' => true, 'is_gluten_free' => true, 'stock' => 8, 'category' => 'Épices'],
            
            // Fruits
            ['name' => 'Dattes Medjool', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 25, 'category' => 'Fruits'],
            ['name' => 'Figues séchées', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 20, 'category' => 'Fruits'],
            
            // Céréales
            ['name' => 'Semoule traditionnelle', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => false, 'stock' => 50, 'category' => 'Céréales'],
            ['name' => 'Orge du désert', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => false, 'stock' => 30, 'category' => 'Céréales'],
        ];

        $ingredientModels = [];
        foreach ($ingredients as $ing) {
            $ingredientData = array_merge([
                'is_vegetarian' => false,
                'is_vegan' => false,
                'is_spicy' => false,
                'is_gluten_free' => false,
                'is_lactose_free' => true,
                'is_nut_free' => true,
            ], array_diff_key($ing, ['name' => '', 'category' => ''])); 
            
            $ingredientModel = Ingredient::create($ingredientData);
            $ingredientModels[$ing['name']] = $ingredientModel;
            
            // CRÉER LE PRODUCT ASSOCIÉ À CHAQUE INGRÉDIENT
            Product::create([
                'name' => $ing['name'],
                'description' => 'Ingrédient frais de qualité - Stock: ' . $ing['stock'] . ' unités',
                'price' => rand(200, 800) / 100, // Prix entre 2€ et 8€
                'productable_id' => $ingredientModel->id,
                'productable_type' => Ingredient::class,
                'category_id' => $categoryModels[$ing['category']]->id ?? $categoryModels['Légumes']->id,
                'status' => $ing['stock'] > 0,
                'is_draft' => false,
                'image' => 'https://picsum.photos/400/300?random=ingredient' . $ingredientModel->id
            ]);
        }

        // 3. CRÉER LES VRAIS PLATS (table dishes = juste id + timestamps)
        $dishes = [
            ['name' => 'Tagine d\'agneau aux abricots', 'description' => 'Plat traditionnel mijoté aux épices berbères', 'price' => 24.00, 'category' => 'Plats principaux'],
            ['name' => 'Couscous royal aux sept légumes', 'description' => 'Le grand classique avec légumes de saison', 'price' => 22.00, 'category' => 'Plats principaux'],
            ['name' => 'Méchoui d\'agneau du désert', 'description' => 'Agneau grillé aux herbes sauvages', 'price' => 28.00, 'category' => 'Plats principaux'],
            ['name' => 'Chorba traditionnelle', 'description' => 'Soupe aux lentilles et épices', 'price' => 8.00, 'category' => 'Entrées'],
            ['name' => 'Soupe d\'orge aux légumes', 'description' => 'Velouté réconfortant du désert', 'price' => 7.00, 'category' => 'Entrées'],
            ['name' => 'Cornes de gazelle', 'description' => 'Pâtisseries aux amandes et fleur d\'oranger', 'price' => 6.00, 'category' => 'Desserts'],
            ['name' => 'Assortiment de dattes fourrées', 'description' => 'Dattes Medjool fourrées aux noix', 'price' => 12.00, 'category' => 'Desserts'],
            ['name' => 'Pain traditionnel du désert', 'description' => 'Pain cuit au sable chaud', 'price' => 4.00, 'category' => 'Accompagnements'],
            ['name' => 'Thé à la menthe', 'description' => 'Thé vert à la menthe fraîche', 'price' => 3.00, 'category' => 'Boissons'],
        ];

        foreach ($dishes as $dish) {
            // Créer le modèle Dish (table simple : juste id + timestamps)
            $dishModel = Dish::create();
            
            Product::create([
                'name' => $dish['name'],
                'description' => $dish['description'],
                'price' => $dish['price'],
                'productable_id' => $dishModel->id,
                'productable_type' => Dish::class,
                'category_id' => $categoryModels[$dish['category']]->id,
                'status' => true,
                'is_draft' => false,
                'image' => 'https://picsum.photos/400/300?random=dish' . $dishModel->id
            ]);
        }

        // 4. CRÉER LES VRAIS MENUS (table menus = juste id + timestamps)
        $menus = [
            ['name' => 'Menu Découverte du Désert', 'description' => 'Initiation aux saveurs berbères', 'price' => 35.00, 'category' => 'Découverte'],
            ['name' => 'Menu Authentique Nomade', 'description' => 'Expérience culinaire traditionnelle complète', 'price' => 45.00, 'category' => 'Authentique'],
            ['name' => 'Menu Prestige des Oasis', 'description' => 'Gastronomie raffinée du désert', 'price' => 65.00, 'category' => 'Prestige'],
            ['name' => 'Menu Petit Explorateur', 'description' => 'Menu adapté aux enfants aventuriers', 'price' => 18.00, 'category' => 'Enfants'],
        ];

        foreach ($menus as $menu) {
            // Créer le modèle Menu (table simple : juste id + timestamps)
            $menuModel = Menu::create();
            
            Product::create([
                'name' => $menu['name'],
                'description' => $menu['description'],
                'price' => $menu['price'],
                'productable_id' => $menuModel->id,
                'productable_type' => Menu::class,
                'category_id' => $categoryModels[$menu['category']]->id,
                'status' => true,
                'is_draft' => false,
                'image' => 'https://picsum.photos/400/300?random=menu' . $menuModel->id
            ]);
        }

        // 5. CRÉER LES VRAIES ACTIVITÉS (avec les vraies colonnes de la table)
        $activities = [
            ['name' => 'Randonnée sur les grandes dunes', 'description' => 'Ascension des plus hautes dunes du Sahara', 'price' => 25.00, 'guide' => 'Hassan', 'duration' => 180, 'max_people' => 12, 'difficulty_level' => 3, 'category' => 'Aventure'],
            ['name' => 'Balade à dos de chameau', 'description' => 'Traversée traditionnelle du désert', 'price' => 30.00, 'guide' => 'Moha', 'duration' => 120, 'max_people' => 8, 'difficulty_level' => 1, 'category' => 'Aventure'],
            ['name' => 'Observation des étoiles', 'description' => 'Soirée astronomie avec télescope', 'price' => 15.00, 'guide' => 'Ahmed', 'duration' => 90, 'max_people' => 20, 'difficulty_level' => 1, 'category' => 'Nocturne'],
            ['name' => 'Atelier artisanat berbère', 'description' => 'Initiation aux techniques traditionnelles', 'price' => 20.00, 'guide' => 'Fatima', 'duration' => 60, 'max_people' => 15, 'difficulty_level' => 1, 'category' => 'Culturel'],
            ['name' => 'Massage aux huiles d\'argan', 'description' => 'Détente totale dans un cadre exceptionnel', 'price' => 45.00, 'guide' => 'Aicha', 'duration' => 45, 'max_people' => 4, 'difficulty_level' => 1, 'category' => 'Détente'],
            ['name' => 'Soirée musique berbère', 'description' => 'Concert traditionnel autour du feu', 'price' => 12.00, 'guide' => 'Omar', 'duration' => 120, 'max_people' => 25, 'difficulty_level' => 1, 'category' => 'Nocturne'],
        ];

        foreach ($activities as $activity) {
            $activityModel = Activity::create([
                'guide' => $activity['guide'],
                'duration' => $activity['duration'],
                'meeting_point' => 'Camp central',
                'max_people' => $activity['max_people'],
                'difficulty_level' => $activity['difficulty_level']
            ]);
            
            Product::create([
                'name' => $activity['name'],
                'description' => $activity['description'],
                'price' => $activity['price'],
                'productable_id' => $activityModel->id,
                'productable_type' => Activity::class,
                'category_id' => $categoryModels[$activity['category']]->id,
                'status' => true,
                'is_draft' => false,
                'image' => 'https://picsum.photos/400/300?random=activity' . $activityModel->id
            ]);
        }

        // 6. CRÉER LES VRAIS HÉBERGEMENTS
        if (Schema::hasTable('rooms')) {
            $rooms = [
                ['name' => 'Tente Berbère Standard', 'description' => 'Tente traditionnelle 2 places', 'price' => 80.00, 'capacity' => 2, 'category' => 'Standard'],
                ['name' => 'Tente Étoilée Confort', 'description' => 'Vue panoramique sur les dunes', 'price' => 120.00, 'capacity' => 2, 'category' => 'Confort'],
                ['name' => 'Suite Premium des Dunes', 'description' => 'Luxe et intimité avec terrasse privée', 'price' => 200.00, 'capacity' => 2, 'category' => 'Premium'],
                ['name' => 'Tente Familiale Nomade', 'description' => 'Spacieuse pour toute la famille', 'price' => 150.00, 'capacity' => 4, 'category' => 'Familial'],
                ['name' => 'Nid d\'Amour Romantique', 'description' => 'Cadre intime sous les étoiles', 'price' => 180.00, 'capacity' => 2, 'category' => 'Romantique'],
            ];

            foreach ($rooms as $room) {
                $roomModel = Room::create([
                    'capacity' => $room['capacity'],
                    'availability' => true
                ]);
                
                Product::create([
                    'name' => $room['name'],
                    'description' => $room['description'],
                    'price' => $room['price'],
                    'productable_id' => $roomModel->id,
                    'productable_type' => Room::class,
                    'category_id' => $categoryModels[$room['category']]->id,
                    'status' => true,
                    'is_draft' => false,
                    'image' => 'https://picsum.photos/400/300?random=room' . $roomModel->id
                ]);
            }
        } else {
            $this->command->warn('⚠️ Table "rooms" non trouvée - hébergements ignorés');
        }

        // 7. CRÉER LES OPTIONS
        $this->createOptions();

        $this->command->info('✅ Données réalistes créées avec succès !');
        $this->command->info('📊 Résumé :');
        $this->command->info('- ' . count($ingredients) . ' ingrédients');
        $this->command->info('- ' . count($dishes) . ' plats');
        $this->command->info('- ' . count($menus) . ' menus');
        $this->command->info('- ' . count($activities) . ' activités');
        $this->command->info('- 5 hébergements');
    }

    /**
     * Récupère les catégories existantes et les organise par nom
     */
    private function getCategoriesMap(): array
    {
        $categories = Category::all();
        $map = [];
        
        foreach ($categories as $category) {
            $map[$category->name] = $category;
        }
        
        // Vérifier que toutes les catégories nécessaires existent
        $this->command->info('📂 Catégories disponibles : ' . implode(', ', array_keys($map)));
        
        return $map;
    }

    private function createOptions()
    {
        // Créer des options liées aux activités
        $activities = Activity::all();
        foreach ($activities->take(3) as $activity) {
            Option::create([
                'name' => 'Assurance annulation',
                'description' => 'Protection en cas d\'annulation de dernière minute',
                'price' => 5.00,
                'productable_id' => $activity->id,
                'productable_type' => Activity::class,
            ]);
            
            Option::create([
                'name' => 'Guide privé',
                'description' => 'Guide dédié pour votre groupe',
                'price' => 25.00,
                'productable_id' => $activity->id,
                'productable_type' => Activity::class,
            ]);
        }

        // Créer des options liées aux hébergements
        if (Schema::hasTable('rooms')) {
            $rooms = Room::all();
            foreach ($rooms->take(3) as $room) {
                Option::create([
                    'name' => 'Petit déjeuner berbère',
                    'description' => 'Petit déjeuner traditionnel dans votre tente',
                    'price' => 12.00,
                    'productable_id' => $room->id,
                    'productable_type' => Room::class,
                ]);

                Option::create([
                    'name' => 'Dîner romantique privé',
                    'description' => 'Dîner aux chandelles sous les étoiles',
                    'price' => 35.00,
                    'productable_id' => $room->id,
                    'productable_type' => Room::class,
                ]);
            }
        }
    }
}