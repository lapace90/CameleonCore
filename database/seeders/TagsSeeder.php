<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagsSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🏷️ Création des tags de base...');

        // Tags globaux
        $globalTags = [
            ['name' => 'budget', 'slug' => 'budget', 'description' => 'Produit économique', 'icon' => 'fas fa-dollar-sign', 'is_global' => true],
            ['name' => 'premium', 'slug' => 'premium', 'description' => 'Produit haut de gamme', 'icon' => 'fas fa-crown', 'is_global' => true],
            ['name' => 'popular', 'slug' => 'popular', 'description' => 'Produit populaire', 'icon' => 'fas fa-star', 'is_global' => true],
            ['name' => 'new', 'slug' => 'new', 'description' => 'Nouveau produit', 'icon' => 'fas fa-sparkles', 'is_global' => true],
        ];

        // Tags spécifiques alimentaire
        $foodTags = [
            ['name' => 'vegetarian', 'slug' => 'vegetarian', 'description' => 'Plat végétarien', 'icon' => 'fas fa-leaf', 'is_global' => false],
            ['name' => 'vegan', 'slug' => 'vegan', 'description' => 'Plat végan', 'icon' => 'fas fa-seedling', 'is_global' => false],
            ['name' => 'spicy', 'slug' => 'spicy', 'description' => 'Plat épicé', 'icon' => 'fas fa-pepper-hot', 'is_global' => false],
            ['name' => 'gluten_free', 'slug' => 'gluten-free', 'description' => 'Sans gluten', 'icon' => 'fas fa-wheat', 'is_global' => false],
            ['name' => 'lactose_free', 'slug' => 'lactose-free', 'description' => 'Sans lactose', 'icon' => 'fas fa-glass-milk', 'is_global' => false],
        ];

        // Tags spécifiques activités
        $activityTags = [
            ['name' => 'extreme', 'slug' => 'extreme', 'description' => 'Activité extrême', 'icon' => 'fas fa-mountain', 'is_global' => false],
            ['name' => 'easy', 'slug' => 'easy', 'description' => 'Activité facile', 'icon' => 'fas fa-smile', 'is_global' => false],
            ['name' => 'outdoor', 'slug' => 'outdoor', 'description' => 'Activité extérieure', 'icon' => 'fas fa-tree', 'is_global' => false],
            ['name' => 'water', 'slug' => 'water', 'description' => 'Activité aquatique', 'icon' => 'fas fa-water', 'is_global' => false],
        ];

        // Tags spécifiques chambres
        $roomTags = [
            ['name' => 'couple', 'slug' => 'couple', 'description' => 'Idéal pour couple', 'icon' => 'fas fa-heart', 'is_global' => false],
            ['name' => 'family', 'slug' => 'family', 'description' => 'Chambre familiale', 'icon' => 'fas fa-users', 'is_global' => false],
            ['name' => 'luxury', 'slug' => 'luxury', 'description' => 'Chambre de luxe', 'icon' => 'fas fa-crown', 'is_global' => false],
            ['name' => 'ocean_view', 'slug' => 'ocean-view', 'description' => 'Vue sur océan', 'icon' => 'fas fa-water', 'is_global' => false],
        ];

        $allTags = array_merge($globalTags, $foodTags, $activityTags, $roomTags);

        foreach ($allTags as $tagData) {
            Tag::updateOrCreate(
                ['name' => $tagData['name']],
                $tagData
            );
        }

        $this->command->info('✅ ' . count($allTags) . ' tags créés !');
    }
}