<?php
// database/seeders/CampCategoriesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CampCategoriesSeeder extends Seeder
{
    /**
     * Seed les catégories pour CampCameleonX - Camping Désert
     */
    public function run(): void
    {
        $this->command->info('🦎 Création des catégories CampCameleonX...');

        // 🏕️ CATÉGORIES ACTIVITÉS
        $this->createActivityCategories();
        
        // 🍽️ CATÉGORIES MENUS
        $this->createMenuCategories();
        
        // 🍲 CATÉGORIES PLATS  
        $this->createDishCategories();
        
        // 🏠 CATÉGORIES HÉBERGEMENTS
        $this->createRoomCategories();
        
        // 🌿 CATÉGORIES INGRÉDIENTS
        $this->createIngredientCategories();

        $this->command->info('✅ Catégories créées avec succès !');
    }

    private function createActivityCategories(): void
    {
        $activities = [
            [
                'name' => 'Aventure',
                'description' => 'Randonnées sur dunes, excursions 4x4, exploration du désert',
                'photo' => 'categories/activities/aventure.jpg'
            ],
            [
                'name' => 'Détente',
                'description' => 'Massages, observation étoiles, moments de repos et relaxation',
                'photo' => 'categories/activities/detente.jpg'
            ],
            [
                'name' => 'Culturel',
                'description' => 'Musique berbère, artisanat local, rencontres avec les nomades',
                'photo' => 'categories/activities/culturel.jpg'
            ],
            [
                'name' => 'Sport',
                'description' => 'Trekking, escalade de dunes, sports dans le désert',
                'photo' => 'categories/activities/sport.jpg'
            ],
            [
                'name' => 'Nocturne',
                'description' => 'Soirées au feu de camp, astronomie, veillées traditionnelles',
                'photo' => 'categories/activities/nocturne.jpg'
            ]
        ];

        foreach ($activities as $activity) {
            Category::create([
                'type' => 'Activity',
                'name' => $activity['name'],
                'description' => $activity['description'],
                'photo' => $activity['photo']
            ]);
        }

        $this->command->info('   🏕️ Catégories Activités créées');
    }

    private function createMenuCategories(): void
    {
        $menus = [
            [
                'name' => 'Découverte',
                'description' => 'Initiation parfaite aux saveurs du désert avec des plats traditionnels revisités',
                'photo' => 'categories/menus/decouverte.jpg'
            ],
            [
                'name' => 'Authentique',
                'description' => 'Expérience culinaire complète avec les spécialités les plus appréciées de la région',
                'photo' => 'categories/menus/authentique.jpg'
            ],
            [
                'name' => 'Prestige',
                'description' => 'Expérience gastronomique exceptionnelle célébrant le raffinement de la cuisine nomade',
                'photo' => 'categories/menus/prestige.jpg'
            ],
            [
                'name' => 'Enfants',
                'description' => 'Menus adaptés aux plus jeunes avec des saveurs douces et familières',
                'photo' => 'categories/menus/enfants.jpg'
            ],
            [
                'name' => 'Régimes spéciaux',
                'description' => 'Menus végétariens, végétaliens, sans gluten et halal',
                'photo' => 'categories/menus/regimes.jpg'
            ]
        ];

        foreach ($menus as $menu) {
            Category::create([
                'type' => 'Menu',
                'name' => $menu['name'],
                'description' => $menu['description'],
                'photo' => $menu['photo']
            ]);
        }

        $this->command->info('   🍽️ Catégories Menus créées');
    }

    private function createDishCategories(): void
    {
        $dishes = [
            [
                'name' => 'Entrées',
                'description' => 'Soupes traditionnelles, mezzes et amuse-bouches du désert',
                'photo' => 'categories/dishes/entrees.jpg'
            ],
            [
                'name' => 'Plats principaux',
                'description' => 'Tagines, couscous, grillades et spécialités berbères',
                'photo' => 'categories/dishes/plats-principaux.jpg'
            ],
            [
                'name' => 'Desserts',
                'description' => 'Pâtisseries orientales, fruits du désert et douceurs traditionnelles',
                'photo' => 'categories/dishes/desserts.jpg'
            ],
            [
                'name' => 'Accompagnements',
                'description' => 'Pains traditionnels, légumes grillés et garnitures',
                'photo' => 'categories/dishes/accompagnements.jpg'
            ],
            [
                'name' => 'Boissons',
                'description' => 'Thé à la menthe, jus de fruits frais et boissons traditionnelles',
                'photo' => 'categories/dishes/boissons.jpg'
            ]
        ];

        foreach ($dishes as $dish) {
            Category::create([
                'type' => 'Dish',
                'name' => $dish['name'],
                'description' => $dish['description'],
                'photo' => $dish['photo']
            ]);
        }

        $this->command->info('   🍲 Catégories Plats créées');
    }

    private function createRoomCategories(): void
    {
        $rooms = [
            [
                'name' => 'Standard',
                'description' => 'Tentes berbères traditionnelles avec équipements de base et confort essentiel',
                'photo' => 'categories/rooms/standard.jpg'
            ],
            [
                'name' => 'Confort',
                'description' => 'Tentes étoilées avec plus d\'équipements et vue panoramique sur les dunes',
                'photo' => 'categories/rooms/confort.jpg'
            ],
            [
                'name' => 'Premium',
                'description' => 'Suites luxueuses avec terrasse privée et services personnalisés',
                'photo' => 'categories/rooms/premium.jpg'
            ],
            [
                'name' => 'Familial',
                'description' => 'Hébergements spacieux adaptés aux familles avec enfants',
                'photo' => 'categories/rooms/familial.jpg'
            ],
            [
                'name' => 'Romantique',
                'description' => 'Tentes intimes pour couples avec décoration soignée et ambiance feutrée',
                'photo' => 'categories/rooms/romantique.jpg'
            ]
        ];

        foreach ($rooms as $room) {
            Category::create([
                'type' => 'Room',
                'name' => $room['name'],
                'description' => $room['description'],
                'photo' => $room['photo']
            ]);
        }

        $this->command->info('   🏠 Catégories Hébergements créées');
    }

    private function createIngredientCategories(): void
    {
        $ingredients = [
            [
                'name' => 'Épices',
                'description' => 'Safran, ras el hanout, harissa, cumin et épices berbères authentiques',
                'photo' => 'categories/ingredients/epices.jpg'
            ],
            [
                'name' => 'Légumes',
                'description' => 'Légumes frais des oasis, courges du désert et légumineuses locales',
                'photo' => 'categories/ingredients/legumes.jpg'
            ],
            [
                'name' => 'Protéines',
                'description' => 'Agneau du désert, volaille fermière et poissons d\'eau douce',
                'photo' => 'categories/ingredients/proteines.jpg'
            ],
            [
                'name' => 'Produits laitiers',
                'description' => 'Fromages de chèvre local, beurre traditionnel et lait d\'oasis',
                'photo' => 'categories/ingredients/laitiers.jpg'
            ],
            [
                'name' => 'Fruits',
                'description' => 'Dattes Medjool, figues séchées, grenades et fruits des oasis',
                'photo' => 'categories/ingredients/fruits.jpg'
            ],
            [
                'name' => 'Céréales',
                'description' => 'Semoule traditionnelle, orge du désert et farines locales',
                'photo' => 'categories/ingredients/cereales.jpg'
            ]
        ];

        foreach ($ingredients as $ingredient) {
            Category::create([
                'type' => 'Ingredient',
                'name' => $ingredient['name'],
                'description' => $ingredient['description'],
                'photo' => $ingredient['photo']
            ]);
        }

        $this->command->info('   🌿 Catégories Ingrédients créées');
    }
}