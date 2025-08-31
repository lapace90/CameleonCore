<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->command->info('🌱 Démarrage du seeding avec des données réalistes...');
            
            // 0. Créer les catégories spécifiques au camping
            $this->call(CampCategoriesSeeder::class);

            // 1. Créer d'abord les données de base avec des vraies données
            $this->call(RealisticDataSeeder::class);
            
            // 2. Puis créer les relations entre produits
            $this->call(ProductRelationsSeeder::class);

            $this->call(TagsSeeder::class);
            
            // 3. Optionnel : créer des utilisateurs et autres données
            $this->seedAdditionalData();

            DB::commit();
            
            $this->command->info('✅ Seeding terminé avec succès !');
            $this->command->info('🎉 Votre base contient maintenant des données réalistes de camping/restauration');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database seeding failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            $this->command->error('❌ Erreur lors du seeding : ' . $e->getMessage());
            throw $e;
        }
    }

    private function seedAdditionalData()
    {
        $this->command->info('👥 Création d\'utilisateurs admin...');
        
        // Créer un utilisateur admin pour tester
        \App\Models\User::create([
            'name' => 'Admin Camping',
            'email' => 'admin@campcanteloup.fr',
            'password' => 'password',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        // Créer quelques utilisateurs normaux
        \App\Models\User::factory(5)->create();

        // Créer quelques clients
        if (class_exists(\App\Models\Customer::class)) {
            \App\Models\Customer::factory(10)->create();
        }
        
        $this->command->info('✅ Utilisateurs créés');
    }
}