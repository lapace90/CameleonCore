<?php
// database/seeders/RealisticDataSeeder.php
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
        // 1. CRÉER LES CATÉGORIES (uniquement Activity et Menu selon votre contrainte)
        $categories = [
            ['type' => 'Menu', 'name' => 'Entrées', 'description' => 'Plats d\'entrée'],
            ['type' => 'Menu', 'name' => 'Plats principaux', 'description' => 'Plats de résistance'],
            ['type' => 'Menu', 'name' => 'Desserts', 'description' => 'Desserts et sucreries'],
            ['type' => 'Menu', 'name' => 'Menus du jour', 'description' => 'Menus complets'],
            ['type' => 'Menu', 'name' => 'Menus enfants', 'description' => 'Menus pour les plus petits'],
            ['type' => 'Activity', 'name' => 'Randonnée', 'description' => 'Activités de marche'],
            ['type' => 'Activity', 'name' => 'Sports nautiques', 'description' => 'Activités aquatiques'],
            ['type' => 'Activity', 'name' => 'Détente', 'description' => 'Activités relaxantes'],
            ['type' => 'Activity', 'name' => 'Hébergements', 'description' => 'Hébergements camping'],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['name']] = Category::create($cat);
        }

        // 2. CRÉER LES VRAIS INGRÉDIENTS
        $ingredients = [
            // Légumes
            ['name' => 'Tomate', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 50],
            ['name' => 'Salade verte', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 30],
            ['name' => 'Concombre', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 25],
            ['name' => 'Carotte', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 40],
            ['name' => 'Poivron rouge', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 20],
            ['name' => 'Oignon', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 35],
            ['name' => 'Champignon', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 15],
            
            // Protéines
            ['name' => 'Poulet fermier', 'is_vegetarian' => false, 'is_vegan' => false, 'is_gluten_free' => true, 'stock' => 20],
            ['name' => 'Saumon frais', 'is_vegetarian' => false, 'is_vegan' => false, 'is_gluten_free' => true, 'stock' => 15],
            ['name' => 'Bœuf local', 'is_vegetarian' => false, 'is_vegan' => false, 'is_gluten_free' => true, 'stock' => 12],
            ['name' => 'Tofu nature', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 18],
            
            // Produits laitiers
            ['name' => 'Fromage de chèvre', 'is_vegetarian' => true, 'is_vegan' => false, 'is_lactose_free' => false, 'stock' => 25],
            ['name' => 'Mozzarella', 'is_vegetarian' => true, 'is_vegan' => false, 'is_lactose_free' => false, 'stock' => 20],
            ['name' => 'Crème fraîche', 'is_vegetarian' => true, 'is_vegan' => false, 'is_lactose_free' => false, 'stock' => 30],
            
            // Féculents
            ['name' => 'Pâtes complètes', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => false, 'stock' => 50],
            ['name' => 'Riz basmati', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 60],
            ['name' => 'Pommes de terre nouvelles', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 45],
            
            // Épices et assaisonnements
            ['name' => 'Basilic frais', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 10],
            ['name' => 'Piment rouge', 'is_vegetarian' => true, 'is_vegan' => true, 'is_spicy' => true, 'is_gluten_free' => true, 'stock' => 8],
            ['name' => 'Huile d\'olive bio', 'is_vegetarian' => true, 'is_vegan' => true, 'is_gluten_free' => true, 'stock' => 25],
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
            ], array_diff_key($ing, ['name' => ''])); 
            
            $ingredientModel = Ingredient::create($ingredientData);
            $ingredientModels[$ing['name']] = $ingredientModel;
            
            // CRÉER LE PRODUCT ASSOCIÉ À CHAQUE INGRÉDIENT
            Product::create([
                'name' => $ing['name'],
                'description' => 'Ingrédient frais de qualité - Stock: ' . $ing['stock'] . ' unités',
                'price' => rand(100, 500) / 100, // Prix entre 1€ et 5€
                'productable_id' => $ingredientModel->id,
                'productable_type' => Ingredient::class,
                'category_id' => $categoryModels['Entrées']->id, // Catégorie par défaut
                'status' => $ing['stock'] > 0,
                'is_draft' => false,
                'image' => 'https://picsum.photos/400/300?random=ingredient' . $ingredientModel->id
            ]);
        }

        // 3. CRÉER LES VRAIS PLATS
        $dishes = [
            [
                'name' => 'Salade méditerranéenne',
                'description' => 'Salade fraîche aux légumes du soleil, fromage de chèvre et basilic',
                'price' => 12.50,
                'category' => 'Entrées',
                'ingredients' => ['Salade verte', 'Tomate', 'Concombre', 'Fromage de chèvre', 'Basilic frais', 'Huile d\'olive bio']
            ],
            [
                'name' => 'Pâtes au saumon',
                'description' => 'Pâtes complètes au saumon frais et crème fraîche',
                'price' => 18.00,
                'category' => 'Plats principaux',
                'ingredients' => ['Pâtes complètes', 'Saumon frais', 'Crème fraîche', 'Oignon', 'Basilic frais']
            ],
            [
                'name' => 'Curry de tofu épicé',
                'description' => 'Tofu aux légumes dans une sauce curry parfumée',
                'price' => 15.50,
                'category' => 'Plats principaux',
                'ingredients' => ['Tofu nature', 'Carotte', 'Poivron rouge', 'Riz basmati', 'Piment rouge', 'Oignon']
            ],
            [
                'name' => 'Gratin de pommes de terre',
                'description' => 'Pommes de terre nouvelles gratinées au fromage',
                'price' => 13.00,
                'category' => 'Plats principaux',
                'ingredients' => ['Pommes de terre nouvelles', 'Crème fraîche', 'Mozzarella', 'Oignon']
            ],
            [
                'name' => 'Salade de fruits de saison',
                'description' => 'Assortiment de fruits frais de nos producteurs locaux',
                'price' => 8.50,
                'category' => 'Desserts',
                'ingredients' => []
            ]
        ];

        $dishModels = [];
        foreach ($dishes as $dish) {
            // Créer le plat
            $dishModel = Dish::create();
            $dishModels[$dish['name']] = $dishModel;
            
            // Créer le produit associé
            Product::create([
                'name' => $dish['name'],
                'description' => $dish['description'],
                'price' => $dish['price'],
                'productable_id' => $dishModel->id,
                'productable_type' => Dish::class,
                'category_id' => $categoryModels[$dish['category']]->id,
                'status' => true,
                'is_draft' => false,
                'image' => 'https://picsum.photos/400/300?random=' . $dishModel->id
            ]);
            
            // Associer les ingrédients
            $ingredientIds = [];
            foreach ($dish['ingredients'] as $ingredientName) {
                if (isset($ingredientModels[$ingredientName])) {
                    $ingredientIds[] = $ingredientModels[$ingredientName]->id;
                }
            }
            $dishModel->ingredients()->sync($ingredientIds);
        }

        // 4. CRÉER LES VRAIS MENUS
        $menus = [
            [
                'name' => 'Menu Découverte',
                'description' => 'Entrée + Plat + Dessert - Notre sélection du chef',
                'price' => 28.00,
                'category' => 'Menus du jour'
            ],
            [
                'name' => 'Menu Végétarien',
                'description' => 'Menu 100% végétarien avec produits bio',
                'price' => 25.00,
                'category' => 'Menus du jour'
            ],
            [
                'name' => 'Menu Enfant',
                'description' => 'Plat adapté aux enfants + dessert + boisson',
                'price' => 12.00,
                'category' => 'Menus enfants'
            ]
        ];

        foreach ($menus as $menu) {
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

        // 5. CRÉER LES VRAIES ACTIVITÉS
        $activities = [
            [
                'name' => 'Randonnée au lac de montagne',
                'description' => 'Découverte du lac cristallin à 2h de marche',
                'price' => 15.00,
                'guide' => 'Marie Dupont',
                'duration' => 180,
                'meeting_point' => 'Parking du camping à 9h00',
                'max_people' => 12,
                'difficulty_level' => 3,
                'category' => 'Randonnée'
            ],
            [
                'name' => 'Initiation au canoë',
                'description' => 'Première approche du canoë sur rivière calme',
                'price' => 25.00,
                'guide' => 'Pierre Martin',
                'duration' => 120,
                'meeting_point' => 'Base nautique, 10h00',
                'max_people' => 8,
                'difficulty_level' => 2,
                'category' => 'Sports nautiques'
            ],
            [
                'name' => 'Séance de yoga matinal',
                'description' => 'Réveil en douceur avec vue sur la nature',
                'price' => 12.00,
                'guide' => 'Sophie Laurent',
                'duration' => 60,
                'meeting_point' => 'Prairie du camping, 7h30',
                'max_people' => 15,
                'difficulty_level' => 1,
                'category' => 'Détente'
            ]
        ];

        foreach ($activities as $activity) {
            $activityModel = Activity::create([
                'guide' => $activity['guide'],
                'duration' => $activity['duration'],
                'meeting_point' => $activity['meeting_point'],
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

        // 6. CRÉER LES VRAIS HÉBERGEMENTS (seulement si la table existe)
        if (Schema::hasTable('rooms')) {
            $rooms = [
                [
                    'name' => 'Tente 2 places Vue Lac',
                    'description' => 'Emplacement tente avec vue imprenable sur le lac',
                    'price' => 35.00,
                    'capacity' => 2,
                    'availability' => true,
                    'category' => 'Hébergements'
                ],
                [
                    'name' => 'Tente familiale 4 places',
                    'description' => 'Grand emplacement pour famille avec enfants',
                    'price' => 45.00,
                    'capacity' => 4,
                    'availability' => true,
                    'category' => 'Hébergements'
                ],
                [
                    'name' => 'Mobil-home Confort 6 personnes',
                    'description' => 'Hébergement tout équipé avec terrasse',
                    'price' => 85.00,
                    'capacity' => 6,
                    'availability' => true,
                    'category' => 'Hébergements'
                ]
            ];

            foreach ($rooms as $room) {
                $roomModel = Room::create([
                    'capacity' => $room['capacity'],
                    'availability' => $room['availability']
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
            $rooms = []; // Pour éviter l'erreur dans le résumé
        }

        // 6. CRÉER LES VRAIES OPTIONS (liées aux modèles, pas des Products)
        $this->createOptions();

        $this->command->info('✅ Données réalistes créées avec succès !');
        $this->command->info('📊 Résumé :');
        $this->command->info('- ' . count($ingredients) . ' ingrédients');
        $this->command->info('- ' . count($dishes) . ' plats');
        $this->command->info('- ' . count($menus) . ' menus');
        $this->command->info('- ' . count($activities) . ' activités');
        $this->command->info('- ' . count($rooms) . ' hébergements');
    }

    private function createOptions()
    {
        // Créer des options liées aux activités
        $activities = Activity::all();
        foreach ($activities->take(2) as $activity) {
            Option::create([
                'name' => 'Assurance annulation',
                'description' => 'Protection en cas d\'annulation de dernière minute',
                'price' => 5.00,
                'productable_id' => $activity->id,
                'productable_type' => Activity::class,
            ]);
            
            Option::create([
                'name' => 'Location équipement',
                'description' => 'Location du matériel nécessaire pour l\'activité',
                'price' => 12.00,
                'productable_id' => $activity->id,
                'productable_type' => Activity::class,
            ]);
        }

        // Créer des options liées aux hébergements (si la table rooms existe)
        if (Schema::hasTable('rooms')) {
            $rooms = Room::all();
            foreach ($rooms->take(2) as $room) {
                Option::create([
                    'name' => 'Petit déjeuner',
                    'description' => 'Petit déjeuner continental servi dans votre hébergement',
                    'price' => 8.50,
                    'productable_id' => $room->id,
                    'productable_type' => Room::class,
                ]);
            }
        }
    }
}