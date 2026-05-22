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
            ['name' => 'budget', 'slug' => 'budget', 'description' => 'Produit économique', 'icon' => 'dollar-sign', 'is_global' => true],
            ['name' => 'premium', 'slug' => 'premium', 'description' => 'Produit haut de gamme', 'icon' => 'crown', 'is_global' => true],
            ['name' => 'popular', 'slug' => 'popular', 'description' => 'Produit populaire', 'icon' => 'star', 'is_global' => true],
            ['name' => 'new', 'slug' => 'new', 'description' => 'Nouveau produit', 'icon' => 'sparkles', 'is_global' => true],
        ];

        // Tags spécifiques alimentaire
        $foodTags = [
            ['name' => 'vegetarian', 'slug' => 'vegetarian', 'description' => 'Plat végétarien', 'icon' => 'leaf', 'is_global' => false],
            ['name' => 'vegan', 'slug' => 'vegan', 'description' => 'Plat végan', 'icon' => 'seedling', 'is_global' => false],
            ['name' => 'spicy', 'slug' => 'spicy', 'description' => 'Plat épicé', 'icon' => 'flame', 'is_global' => false],
            ['name' => 'gluten_free', 'slug' => 'gluten-free', 'description' => 'Sans gluten', 'icon' => 'wheat', 'is_global' => false],
            ['name' => 'lactose_free', 'slug' => 'lactose-free', 'description' => 'Sans lactose', 'icon' => 'glass-water', 'is_global' => false],
        ];

        // Tags spécifiques activités
        $activityTags = [
            ['name' => 'extreme', 'slug' => 'extreme', 'description' => 'Activité extrême', 'icon' => 'mountain', 'is_global' => false],
            ['name' => 'easy', 'slug' => 'easy', 'description' => 'Activité facile', 'icon' => 'smile', 'is_global' => false],
            ['name' => 'outdoor', 'slug' => 'outdoor', 'description' => 'Activité extérieure', 'icon' => 'tree-pine', 'is_global' => false],
            ['name' => 'water', 'slug' => 'water', 'description' => 'Activité aquatique', 'icon' => 'waves', 'is_global' => false],
        ];

        // Tags spécifiques chambres
        $roomTags = [
            ['name' => 'couple', 'slug' => 'couple', 'description' => 'Idéal pour couple', 'icon' => 'heart', 'is_global' => false],
            ['name' => 'family', 'slug' => 'family', 'description' => 'Chambre familiale', 'icon' => 'users', 'is_global' => false],
            ['name' => 'luxury', 'slug' => 'luxury', 'description' => 'Chambre de luxe', 'icon' => 'crown', 'is_global' => false],
            ['name' => 'ocean_view', 'slug' => 'ocean-view', 'description' => 'Vue sur océan', 'icon' => 'waves', 'is_global' => false],
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