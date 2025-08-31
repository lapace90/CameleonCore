<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Création des permissions et rôles...');

        DB::beginTransaction();
        
        try {
            // 1. Créer les permissions de base
            $this->createPermissions();
            
            // 2. Créer les rôles
            $this->createRoles();
            
            // 3. Assigner les permissions aux rôles
            $this->assignPermissionsToRoles();
            
            // 4. Créer un utilisateur admin de base
            $this->createAdminUser();
            
            DB::commit();
            
            $this->command->info('✅ Rôles et permissions créés avec succès !');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Erreur : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Créer les permissions de base du système
     */
    private function createPermissions(): void
    {
        $this->command->info('📝 Création des permissions...');

        $permissions = [
            // Permissions utilisateurs
            ['name' => 'Voir les utilisateurs', 'action' => 'read'],
            ['name' => 'Créer des utilisateurs', 'action' => 'create'],
            ['name' => 'Modifier les utilisateurs', 'action' => 'update'],
            ['name' => 'Supprimer les utilisateurs', 'action' => 'delete'],
            ['name' => 'Gérer les utilisateurs', 'action' => 'manage'],
            
            // Permissions rôles
            ['name' => 'Voir les rôles', 'action' => 'read'],
            ['name' => 'Créer des rôles', 'action' => 'create'],
            ['name' => 'Modifier les rôles', 'action' => 'update'],
            ['name' => 'Supprimer les rôles', 'action' => 'delete'],
            ['name' => 'Gérer les rôles', 'action' => 'manage'],
            
            // Permissions permissions
            ['name' => 'Voir les permissions', 'action' => 'read'],
            ['name' => 'Créer des permissions', 'action' => 'create'],
            ['name' => 'Modifier les permissions', 'action' => 'update'],
            ['name' => 'Supprimer les permissions', 'action' => 'delete'],
            ['name' => 'Gérer les permissions', 'action' => 'manage'],
            
            // Permissions produits
            ['name' => 'Voir les produits', 'action' => 'read'],
            ['name' => 'Créer des produits', 'action' => 'create'],
            ['name' => 'Modifier les produits', 'action' => 'update'],
            ['name' => 'Supprimer les produits', 'action' => 'delete'],
            ['name' => 'Publier les produits', 'action' => 'publish'],
            
            // Permissions catégories
            ['name' => 'Voir les catégories', 'action' => 'read'],
            ['name' => 'Créer des catégories', 'action' => 'create'],
            ['name' => 'Modifier les catégories', 'action' => 'update'],
            ['name' => 'Supprimer les catégories', 'action' => 'delete'],
            
            // Permissions tags
            ['name' => 'Voir les tags', 'action' => 'read'],
            ['name' => 'Créer des tags', 'action' => 'create'],
            ['name' => 'Modifier les tags', 'action' => 'update'],
            ['name' => 'Supprimer les tags', 'action' => 'delete'],
            
            // Permissions commandes/réservations
            ['name' => 'Voir les commandes', 'action' => 'read'],
            ['name' => 'Créer des commandes', 'action' => 'create'],
            ['name' => 'Modifier les commandes', 'action' => 'update'],
            ['name' => 'Annuler les commandes', 'action' => 'cancel'],
            ['name' => 'Traiter les commandes', 'action' => 'process'],
            
            // Permissions clients
            ['name' => 'Voir les clients', 'action' => 'read'],
            ['name' => 'Créer des clients', 'action' => 'create'],
            ['name' => 'Modifier les clients', 'action' => 'update'],
            ['name' => 'Supprimer les clients', 'action' => 'delete'],
            
            // Permissions analytics
            ['name' => 'Voir les statistiques', 'action' => 'read'],
            ['name' => 'Exporter les données', 'action' => 'export'],
            ['name' => 'Voir les rapports avancés', 'action' => 'advanced-read'],
            
            // Permissions système
            ['name' => 'Accès administration', 'action' => 'admin'],
            ['name' => 'Gérer les paramètres', 'action' => 'manage'],
            ['name' => 'Mode maintenance', 'action' => 'maintenance'],
            ['name' => 'Vider le cache', 'action' => 'clear-cache'],
            ['name' => 'Voir les logs', 'action' => 'read-logs'],
            
            // Permissions spécifiques camping
            ['name' => 'Gérer les réservations', 'action' => 'manage'],
            ['name' => 'Check-in clients', 'action' => 'checkin'],
            ['name' => 'Check-out clients', 'action' => 'checkout'],
            ['name' => 'Voir la disponibilité', 'action' => 'availability'],
            ['name' => 'Gérer le planning', 'action' => 'planning']
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                ['action' => $permissionData['action']]
            );
        }

        $this->command->info("✅ " . count($permissions) . " permissions créées");
    }

    /**
     * Créer les rôles de base
     */
    private function createRoles(): void
    {
        $this->command->info('👥 Création des rôles...');

        $roles = [
            [
                'name' => 'Super Administrateur',
                'slug' => 'super-admin',
                'description' => 'Accès complet à toutes les fonctionnalités du système'
            ],
            [
                'name' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Gestion complète du camping et des utilisateurs'
            ],
            [
                'name' => 'Gestionnaire',
                'slug' => 'manager',
                'description' => 'Gestion des réservations et des opérations quotidiennes'
            ],
            [
                'name' => 'Réceptionniste',
                'slug' => 'receptionist',
                'description' => 'Accueil clients, check-in/out, gestion des réservations'
            ],
            [
                'name' => 'Agent d\'entretien',
                'slug' => 'maintenance',
                'description' => 'Maintenance et préparation des emplacements'
            ],
            [
                'name' => 'Client',
                'slug' => 'client',
                'description' => 'Accès client pour réservations et compte personnel'
            ],
            [
                'name' => 'Modérateur',
                'slug' => 'moderator',
                'description' => 'Modération du contenu et support client'
            ]
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description']
                ]
            );
        }

        $this->command->info("✅ " . count($roles) . " rôles créés");
    }

    /**
     * Assigner les permissions aux rôles
     */
    private function assignPermissionsToRoles(): void
    {
        $this->command->info('🔗 Attribution des permissions aux rôles...');

        // Super Admin : toutes les permissions
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $allPermissions = Permission::all()->pluck('id')->toArray();
            $superAdmin->permissions()->sync($allPermissions);
        }

        // Admin : toutes sauf système critique
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $adminPermissions = Permission::whereNotIn('action', ['maintenance', 'clear-cache'])
                ->pluck('id')->toArray();
            $admin->permissions()->sync($adminPermissions);
        }

        // Gestionnaire : gestion opérationnelle
        $manager = Role::where('slug', 'manager')->first();
        if ($manager) {
            $managerPermissions = Permission::whereIn('name', [
                'Voir les produits', 'Créer des produits', 'Modifier les produits',
                'Voir les commandes', 'Créer des commandes', 'Modifier les commandes', 'Traiter les commandes',
                'Voir les clients', 'Créer des clients', 'Modifier les clients',
                'Gérer les réservations', 'Check-in clients', 'Check-out clients',
                'Voir la disponibilité', 'Gérer le planning',
                'Voir les statistiques', 'Exporter les données',
                'Voir les catégories', 'Voir les tags'
            ])->pluck('id')->toArray();
            $manager->permissions()->sync($managerPermissions);
        }

        // Réceptionniste : accueil et réservations
        $receptionist = Role::where('slug', 'receptionist')->first();
        if ($receptionist) {
            $receptionistPermissions = Permission::whereIn('name', [
                'Voir les produits',
                'Voir les commandes', 'Créer des commandes', 'Modifier les commandes',
                'Voir les clients', 'Créer des clients', 'Modifier les clients',
                'Gérer les réservations', 'Check-in clients', 'Check-out clients',
                'Voir la disponibilité'
            ])->pluck('id')->toArray();
            $receptionist->permissions()->sync($receptionistPermissions);
        }

        // Agent d'entretien : maintenance
        $maintenance = Role::where('slug', 'maintenance')->first();
        if ($maintenance) {
            $maintenancePermissions = Permission::whereIn('name', [
                'Voir les produits',
                'Voir les commandes',
                'Voir la disponibilité',
                'Gérer le planning'
            ])->pluck('id')->toArray();
            $maintenance->permissions()->sync($maintenancePermissions);
        }

        // Client : accès minimal
        $client = Role::where('slug', 'client')->first();
        if ($client) {
            $clientPermissions = Permission::whereIn('name', [
                'Voir les produits',
                'Voir la disponibilité'
            ])->pluck('id')->toArray();
            $client->permissions()->sync($clientPermissions);
        }

        // Modérateur : contenu et support
        $moderator = Role::where('slug', 'moderator')->first();
        if ($moderator) {
            $moderatorPermissions = Permission::whereIn('name', [
                'Voir les produits', 'Modifier les produits',
                'Voir les catégories', 'Créer des catégories', 'Modifier les catégories',
                'Voir les tags', 'Créer des tags', 'Modifier les tags',
                'Voir les clients', 'Modifier les clients',
                'Voir les commandes', 'Modifier les commandes'
            ])->pluck('id')->toArray();
            $moderator->permissions()->sync($moderatorPermissions);
        }

        $this->command->info("✅ Permissions attribuées aux rôles");
    }

    /**
     * Créer un utilisateur administrateur de base
     */
    private function createAdminUser(): void
    {
        $this->command->info('👤 Création de l\'utilisateur administrateur...');

        $adminRole = Role::where('slug', 'super-admin')->first();
        
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@campcanteloup.fr'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123456'),
                'email_verified_at' => now(),
                'status' => 'active',
                'role_id' => $adminRole->id
            ]
        );

        if ($adminUser->wasRecentlyCreated) {
            $this->command->info("✅ Utilisateur admin créé :");
            $this->command->info("   Email: admin@campcanteloup.fr");
            $this->command->info("   Mot de passe: admin123456");
            $this->command->warn("   ⚠️  Changez ce mot de passe en production !");
        } else {
            $this->command->info("ℹ️  Utilisateur admin existant mis à jour");
        }
    }

    /**
     * Afficher un résumé des données créées
     */
    private function showSummary(): void
    {
        $this->command->info("\n📊 RÉSUMÉ :");
        $this->command->info("Permissions : " . Permission::count());
        $this->command->info("Rôles : " . Role::count());
        $this->command->info("Utilisateurs admin : " . User::whereHas('role', function($query) {
            $query->whereIn('slug', ['super-admin', 'admin']);
        })->count());
        
        $this->command->info("\n🚀 Système prêt pour la gestion des utilisateurs !");
    }
}