<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Role;

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

            $this->call(RolesPermissionsSeeder::class);

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
        $superAdminRole = Role::where('slug', 'super-admin')->first();

        // ✅ MISE À JOUR : Créer un utilisateur admin pour tester avec les nouveaux champs
        \App\Models\User::create([
            'name' => 'Pietro Pacciani',
            'email' => 'admin@campcanteloup.fr',
            'password' => 'password',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'role_id' => $superAdminRole?->id,
            'phone' => '+212 5 24 44 55 66',
            'address' => 'Route de Ouarzazate, Km 15',
            'city' => 'Marrakech',
            'postal_code' => '40000',
            'avatar' => null,
            'status' => 'active',
            'last_login_at' => now()->subHours(2),
            'last_login_ip' => '192.168.1.100',
            'password_reset_required' => false,
        ]);

        // Créer quelques utilisateurs normaux avec les nouveaux champs
        \App\Models\User::factory(5)->create();

        $this->command->info('✅ Utilisateurs créés');
    }
}
