<?php
// tests/Traits/CreatesTestData.php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Category;
use App\Models\Activity;
use Illuminate\Support\Facades\Hash;

trait CreatesTestData
{
    /**
     * Créer structure RBAC complète pour les tests
     */
    protected function createRBACStructure(): array
    {
        // Rôles
        $superAdminRole = Role::factory()->create([
            'slug' => 'super-admin',
            'name' => 'Super Administrateur',
            'level' => 100
        ]);

        $adminRole = Role::factory()->create([
            'slug' => 'admin',
            'name' => 'Administrateur',
            'level' => 50
        ]);

        $managerRole = Role::factory()->create([
            'slug' => 'manager',
            'name' => 'Manager',
            'level' => 25
        ]);

        $userRole = Role::factory()->create([
            'slug' => 'user',
            'name' => 'Utilisateur',
            'level' => 1
        ]);

        // Permissions
        $permissions = [
            'system-admin' => 'Administration Système',
            'users-read' => 'Lecture Utilisateurs',
            'users-write' => 'Écriture Utilisateurs',
            'users-delete' => 'Suppression Utilisateurs',
            'products-read' => 'Lecture Produits',
            'products-write' => 'Écriture Produits',
            'products-delete' => 'Suppression Produits',
            'reservations-read' => 'Lecture Réservations',
            'reservations-write' => 'Écriture Réservations',
            'admin-access' => 'Accès Administration'
        ];

        $permissionObjects = [];
        foreach ($permissions as $action => $name) {
            $permissionObjects[$action] = Permission::factory()->create([
                'action' => $action,
                'name' => $name,
                'category' => explode('-', $action)[0] ?? 'general'
            ]);
        }

        // Assignations
        $superAdminRole->permissions()->attach(array_values($permissionObjects));
        
        $adminRole->permissions()->attach([
            $permissionObjects['users-read']->id,
            $permissionObjects['users-write']->id,
            $permissionObjects['products-read']->id,
            $permissionObjects['products-write']->id,
            $permissionObjects['reservations-read']->id,
            $permissionObjects['reservations-write']->id,
            $permissionObjects['admin-access']->id,
        ]);

        $managerRole->permissions()->attach([
            $permissionObjects['products-read']->id,
            $permissionObjects['reservations-read']->id,
            $permissionObjects['reservations-write']->id,
        ]);

        return [
            'roles' => compact('superAdminRole', 'adminRole', 'managerRole', 'userRole'),
            'permissions' => $permissionObjects
        ];
    }

    /**
     * Créer utilisateurs de test avec profils complets
     */
    protected function createTestUsers(): array
    {
        $rbac = $this->createRBACStructure();

        $superAdmin = User::factory()->create([
            'name' => 'Super Administrateur',
            'email' => 'superadmin@campcameleonx.fr',
            'password' => Hash::make('password123'),
            'role_id' => $rbac['roles']['superAdminRole']->id,
            'phone' => '+33 6 00 00 00 01',
            'address' => '1 Place du Super Admin',
            'city' => 'Paris',
            'postal_code' => '75001',
            'status' => 'active'
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin Principal',
            'email' => 'admin@campcameleonx.fr',
            'password' => Hash::make('password123'),
            'role_id' => $rbac['roles']['adminRole']->id,
            'phone' => '+33 6 00 00 00 02',
            'address' => '2 Avenue de l\'Admin',
            'city' => 'Marseille',
            'postal_code' => '13000',
            'status' => 'active'
        ]);

        $manager = User::factory()->create([
            'name' => 'Manager Produits',
            'email' => 'manager@campcameleonx.fr',
            'password' => Hash::make('password123'),
            'role_id' => $rbac['roles']['managerRole']->id,
            'phone' => '+33 6 00 00 00 03',
            'address' => '3 Rue du Manager',
            'city' => 'Toulon',
            'postal_code' => '83000',
            'status' => 'active'
        ]);

        $user = User::factory()->create([
            'name' => 'Utilisateur Lambda',
            'email' => 'user@campcameleonx.fr',
            'password' => Hash::make('password123'),
            'role_id' => $rbac['roles']['userRole']->id,
            'phone' => '+33 6 00 00 00 04',
            'address' => '4 Chemin de l\'Utilisateur',
            'city' => 'Nice',
            'postal_code' => '06000',
            'status' => 'active'
        ]);

        return compact('superAdmin', 'admin', 'manager', 'user');
    }

    /**
     * Créer produits de test avec catégories
     */
    protected function createTestProducts(int $count = 5): array
    {
        $categories = [
            Category::factory()->create(['name' => 'Activités', 'slug' => 'activities']),
            Category::factory()->create(['name' => 'Hébergements', 'slug' => 'accommodations']),
            Category::factory()->create(['name' => 'Restauration', 'slug' => 'food'])
        ];

        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $activity = Activity::factory()->create([
                'guide' => "Guide " . ($i + 1),
                'duration' => rand(60, 240),
                'difficulty_level' => rand(1, 5),
                'max_people' => rand(5, 20)
            ]);

            $products[] = Product::factory()->create([
                'name' => "Produit Test " . ($i + 1),
                'price' => rand(20, 200),
                'category_id' => $categories[array_rand($categories)]->id,
                'productable_type' => Activity::class,
                'productable_id' => $activity->id,
                'status' => true
            ]);
        }

        return $products;
    }

    /**
     * Créer un utilisateur avec dernière connexion simulée
     */
    protected function createUserWithLastLogin(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'last_login_at' => now()->subHours(2),
            'last_login_ip' => '192.168.1.' . rand(1, 254)
        ], $attributes));

        return $user;
    }
}
