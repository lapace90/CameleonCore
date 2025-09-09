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
     * Architecture RBAC : Users ↔ Roles ↔ Permissions
     * PAS de permissions directes aux utilisateurs
     */
    public function run(): void
    {
        $this->command->info('🏕️ Création du système RBAC pour CampCameleonX...');

        DB::beginTransaction();

        try {
            // 1. Créer les permissions réalistes
            $this->createRealisticPermissions();

            // 2. Créer les rôles adaptés à une maison d'hôte
            $this->createRealisticRoles();

            // 3. Assigner les permissions AUX RÔLES UNIQUEMENT
            $this->assignPermissionsToRoles();

            // 4. Créer des utilisateurs de test avec des RÔLES
            $this->createTestUsers();

            DB::commit();
            $this->command->info('✅ Système RBAC créé : Users → Roles → Permissions');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Erreur : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Créer les permissions adaptées à une maison d'hôte
     *  avec catégories explicites
     */
    private function createRealisticPermissions(): void
    {
        $this->command->info('🔑 Création des permissions réalistes...');

        $permissions = [
            // === SYSTÈME ET ADMINISTRATION ===
            ['name' => 'Administration système', 'action' => 'system-admin', 'category' => 'system'],
            ['name' => 'Accès interface admin', 'action' => 'admin-access', 'category' => 'system'],
            ['name' => 'Mode maintenance', 'action' => 'maintenance-mode', 'category' => 'system'],
            ['name' => 'Vider le cache', 'action' => 'clear-cache', 'category' => 'system'],
            ['name' => 'Voir les logs système', 'action' => 'view-logs', 'category' => 'system'],

            // === GESTION DES UTILISATEURS ===
            ['name' => 'Voir les utilisateurs', 'action' => 'users-read', 'category' => 'users'],
            ['name' => 'Créer des utilisateurs', 'action' => 'users-create', 'category' => 'users'],
            ['name' => 'Modifier les utilisateurs', 'action' => 'users-update', 'category' => 'users'],
            ['name' => 'Supprimer les utilisateurs', 'action' => 'users-delete', 'category' => 'users'],
            ['name' => 'Gérer les rôles', 'action' => 'roles-manage', 'category' => 'users'],

            // === HÉBERGEMENTS ET ACTIVITÉS ===
            ['name' => 'Voir les hébergements', 'action' => 'accommodations-read', 'category' => 'accommodations'],
            ['name' => 'Gérer les hébergements', 'action' => 'accommodations-manage', 'category' => 'accommodations'],
            ['name' => 'Voir les activités', 'action' => 'activities-read', 'category' => 'activities'],
            ['name' => 'Gérer les activités', 'action' => 'activities-manage', 'category' => 'activities'],
            ['name' => 'Publier sur le site', 'action' => 'products-publish', 'category' => 'other'],

            // === RÉSERVATIONS ===
            ['name' => 'Voir toutes les réservations', 'action' => 'bookings-read-all', 'category' => 'bookings'],
            ['name' => 'Créer des réservations', 'action' => 'bookings-create', 'category' => 'bookings'],
            ['name' => 'Modifier les réservations', 'action' => 'bookings-update', 'category' => 'bookings'],
            ['name' => 'Annuler les réservations', 'action' => 'bookings-cancel', 'category' => 'bookings'],
            ['name' => 'Confirmer les réservations', 'action' => 'bookings-confirm', 'category' => 'bookings'],
            ['name' => 'Gérer le planning', 'action' => 'planning-manage', 'category' => 'bookings'],

            // === ACCUEIL ET CHECK-IN/OUT ===
            ['name' => 'Check-in des clients', 'action' => 'checkin', 'category' => 'reception'],
            ['name' => 'Check-out des clients', 'action' => 'checkout', 'category' => 'reception'],
            ['name' => 'Voir les arrivées du jour', 'action' => 'arrivals-today', 'category' => 'reception'],
            ['name' => 'Voir les départs du jour', 'action' => 'departures-today', 'category' => 'reception'],
            ['name' => 'Gérer les clés', 'action' => 'keys-manage', 'category' => 'reception'],

            // === CLIENTS ===
            ['name' => 'Voir les clients', 'action' => 'customers-read', 'category' => 'customers'],
            ['name' => 'Créer des clients', 'action' => 'customers-create', 'category' => 'customers'],
            ['name' => 'Modifier les clients', 'action' => 'customers-update', 'category' => 'customers'],
            ['name' => 'Historique des clients', 'action' => 'customers-history', 'category' => 'customers'],

            // === RESTAURANT ===
            ['name' => 'Voir les menus', 'action' => 'menus-read', 'category' => 'restaurant'],
            ['name' => 'Gérer les menus', 'action' => 'menus-manage', 'category' => 'restaurant'],
            ['name' => 'Gérer les plats', 'action' => 'dishes-manage', 'category' => 'restaurant'],
            ['name' => 'Prendre les commandes', 'action' => 'orders-take', 'category' => 'restaurant'],
            ['name' => 'Gérer les commandes', 'action' => 'orders-manage', 'category' => 'restaurant'],

            // === MAINTENANCE ET ENTRETIEN ===
            ['name' => 'Voir les tâches de maintenance', 'action' => 'maintenance-read', 'category' => 'other'],
            ['name' => 'Signaler un problème', 'action' => 'maintenance-report', 'category' => 'other'],
            ['name' => 'Gérer la maintenance', 'action' => 'maintenance-manage', 'category' => 'other'],
            ['name' => 'État des hébergements', 'action' => 'rooms-status', 'category' => 'accommodations'],

            // === FINANCE ET FACTURATION ===
            ['name' => 'Voir les paiements', 'action' => 'payments-read', 'category' => 'finance'],
            ['name' => 'Encaisser les paiements', 'action' => 'payments-collect', 'category' => 'finance'],
            ['name' => 'Gérer la facturation', 'action' => 'invoicing-manage', 'category' => 'finance'],
            ['name' => 'Voir les statistiques financières', 'action' => 'finance-stats', 'category' => 'finance'],

            // === STATISTIQUES ET RAPPORTS ===
            ['name' => 'Voir le tableau de bord', 'action' => 'dashboard-view', 'category' => 'analytics'],
            ['name' => 'Statistiques d\'occupation', 'action' => 'occupancy-stats', 'category' => 'analytics'],
            ['name' => 'Rapports de revenus', 'action' => 'revenue-reports', 'category' => 'analytics'],
            ['name' => 'Exporter les données', 'action' => 'data-export', 'category' => 'analytics'],

            // === COMMUNICATION ===
            ['name' => 'Messages clients', 'action' => 'messages-customers', 'category' => 'communication'],
            ['name' => 'Notifications équipe', 'action' => 'notifications-team', 'category' => 'communication'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['action' => $permissionData['action']],
                [
                    'name' => $permissionData['name'],
                    'action' => $permissionData['action'],
                    'category' => $permissionData['category'] // ✅ Catégorie explicite
                ]
            );
        }

        $this->command->info("✅ " . count($permissions) . " permissions créées avec catégories");
    }

    /**
     * Créer les rôles adaptés à une maison d'hôte
     */
    private function createRealisticRoles(): void
    {
        $this->command->info('👥 Création des rôles réalistes...');

        $roles = [
            [
                'name' => 'Propriétaire/Directeur',
                'slug' => 'owner',
                'description' => 'Accès complet à toutes les fonctionnalités de la maison d\'hôte'
            ],
            [
                'name' => 'Gestionnaire',
                'slug' => 'manager',
                'description' => 'Gestion opérationnelle quotidienne, réservations et équipe'
            ],
            [
                'name' => 'Réceptionniste',
                'slug' => 'receptionist',
                'description' => 'Accueil clients, check-in/out, réservations et informations'
            ],
            [
                'name' => 'Chef de cuisine',
                'slug' => 'chef',
                'description' => 'Gestion de la cuisine, menus et service restauration'
            ],
            [
                'name' => 'Guide d\'activités',
                'slug' => 'guide',
                'description' => 'Organisation et encadrement des activités du désert'
            ],
            [
                'name' => 'Comptable/Finance',
                'slug' => 'accountant',
                'description' => 'Gestion financière, facturation et paiements'
            ]
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'slug' => $roleData['slug'],
                    'description' => $roleData['description']
                ]
            );
        }

        $this->command->info("✅ " . count($roles) . " rôles créés");
    }

    /**
     * Assigner les permissions AUX RÔLES UNIQUEMENT
     */
    private function assignPermissionsToRoles(): void
    {
        $this->command->info('🔗 Attribution des permissions aux rôles...');

        // === PROPRIÉTAIRE/DIRECTEUR - Accès complet ===
        $owner = Role::where('slug', 'owner')->first();
        if ($owner) {
            $allPermissions = Permission::all()->pluck('id')->toArray();
            $owner->permissions()->sync($allPermissions);
            $this->command->info("→ Propriétaire : " . count($allPermissions) . " permissions");
        }

        // === GESTIONNAIRE - Gestion opérationnelle ===
        $manager = Role::where('slug', 'manager')->first();
        if ($manager) {
            $managerPermissions = Permission::whereIn('action', [
                'admin-access',
                'dashboard-view',
                'users-read',
                'users-create',
                'users-update',
                'accommodations-read',
                'accommodations-manage',
                'activities-read',
                'activities-manage',
                'products-publish',
                'bookings-read-all',
                'bookings-create',
                'bookings-update',
                'bookings-cancel',
                'bookings-confirm',
                'planning-manage',
                'checkin',
                'checkout',
                'arrivals-today',
                'departures-today',
                'keys-manage',
                'customers-read',
                'customers-create',
                'customers-update',
                'customers-history',
                'menus-read',
                'menus-manage',
                'dishes-manage',
                'orders-manage',
                'maintenance-read',
                'maintenance-manage',
                'rooms-status',
                'payments-read',
                'payments-collect',
                'invoicing-manage',
                'finance-stats',
                'occupancy-stats',
                'revenue-reports',
                'data-export',
                'messages-customers',
                'notifications-team'
            ])->pluck('id')->toArray();
            $manager->permissions()->sync($managerPermissions);
            $this->command->info("→ Gestionnaire : " . count($managerPermissions) . " permissions");
        }

        // === RÉCEPTIONNISTE - Accueil et réservations ===
        $receptionist = Role::where('slug', 'receptionist')->first();
        if ($receptionist) {
            $receptionistPermissions = Permission::whereIn('action', [
                'admin-access',
                'dashboard-view',
                'accommodations-read',
                'activities-read',
                'bookings-read-all',
                'bookings-create',
                'bookings-update',
                'bookings-confirm',
                'planning-manage',
                'checkin',
                'checkout',
                'arrivals-today',
                'departures-today',
                'keys-manage',
                'customers-read',
                'customers-create',
                'customers-update',
                'customers-history',
                'maintenance-report',
                'rooms-status',
                'payments-collect',
                'messages-customers'
            ])->pluck('id')->toArray();
            $receptionist->permissions()->sync($receptionistPermissions);
            $this->command->info("→ Réceptionniste : " . count($receptionistPermissions) . " permissions");
        }

        // === SERVEUR/RESTAURATION ===
        $waiter = Role::where('slug', 'waiter')->first();
        if ($waiter) {
            $waiterPermissions = Permission::whereIn('action', [
                'admin-access',
                'dashboard-view',
                'customers-read',
                'menus-read',
                'menus-manage',
                'dishes-manage',
                'orders-take',
                'orders-manage',
                'maintenance-report'
            ])->pluck('id')->toArray();
            $waiter->permissions()->sync($waiterPermissions);
            $this->command->info("→ Serveur : " . count($waiterPermissions) . " permissions");
        }

        // === GUIDE D'ACTIVITÉS ===
        $guide = Role::where('slug', 'guide')->first();
        if ($guide) {
            $guidePermissions = Permission::whereIn('action', [
                'admin-access',
                'dashboard-view',
                'activities-read',
                'activities-manage',
                'bookings-read-all',
                'customers-read',
                'maintenance-report'
            ])->pluck('id')->toArray();
            $guide->permissions()->sync($guidePermissions);
            $this->command->info("→ Guide : " . count($guidePermissions) . " permissions");
        }

        // === AGENT D'ENTRETIEN ===
        $maintenance = Role::where('slug', 'maintenance')->first();
        if ($maintenance) {
            $maintenancePermissions = Permission::whereIn('action', [
                'admin-access',
                'dashboard-view',
                'accommodations-read',
                'rooms-status',
                'maintenance-read',
                'maintenance-report',
                'maintenance-manage',
                'checkin',
                'checkout' // Pour l'état des hébergements
            ])->pluck('id')->toArray();
            $maintenance->permissions()->sync($maintenancePermissions);
            $this->command->info("→ Maintenance : " . count($maintenancePermissions) . " permissions");
        }

        // === COMPTABLE/FINANCE ===
        $accountant = Role::where('slug', 'accountant')->first();
        if ($accountant) {
            $accountantPermissions = Permission::whereIn('action', [
                'admin-access',
                'dashboard-view',
                'bookings-read-all',
                'customers-read',
                'payments-read',
                'payments-collect',
                'invoicing-manage',
                'finance-stats',
                'occupancy-stats',
                'revenue-reports',
                'data-export'
            ])->pluck('id')->toArray();
            $accountant->permissions()->sync($accountantPermissions);
            $this->command->info("→ Comptable : " . count($accountantPermissions) . " permissions");
        }

        $this->command->info('✅ Permissions assignées aux rôles uniquement');
    }

    /**
     * Créer des utilisateurs de test avec des RÔLES
     */
    private function createTestUsers(): void
    {
        $this->command->info('👤 Création des utilisateurs de test...');

        // Récupérer les rôles
        $ownerRole = Role::where('slug', 'owner')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $receptionistRole = Role::where('slug', 'receptionist')->first();
        $chefRole = Role::where('slug', 'chef')->first();

        $users = [
            [
                'name' => 'Amina Bensalem',
                'email' => 'amina@campcanteloup.ma',
                'password' => Hash::make('password'),
                'role_id' => $ownerRole?->id, // Rôle principal
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Youssef Tazi',
                'email' => 'youssef@campcanteloup.ma',
                'password' => Hash::make('password'),
                'role_id' => $managerRole?->id, // Rôle principal
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Fatima Ouali',
                'email' => 'fatima@campcanteloup.ma',
                'password' => Hash::make('password'),
                'role_id' => $receptionistRole?->id, // Rôle principal
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Exemple de rôles additionnels pour Youssef (Gestionnaire + Chef parfois)
            if ($user->email === 'youssef@campcanteloup.ma' && $chefRole) {
                $user->roles()->syncWithoutDetaching([$chefRole->id]);
                $this->command->info("→ {$user->name} : rôle additionnel Chef de cuisine");
            }
        }

        $this->command->info('✅ Utilisateurs de test créés avec des rôles');
        $this->command->info('📧 Emails de test :');
        $this->command->info('   - amina@campcanteloup.ma (Propriétaire)');
        $this->command->info('   - youssef@campcanteloup.ma (Gestionnaire + Chef)');
        $this->command->info('   - fatima@campcanteloup.ma (Réceptionniste)');
        $this->command->info('🔑 Mot de passe : password');
    }
}
