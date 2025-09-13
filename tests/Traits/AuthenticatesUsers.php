<?php
// tests/Traits/AuthenticatesUsers.php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Laravel\Sanctum\Sanctum;

trait AuthenticatesUsers
{
    /**
     * Créer et authentifier un admin
     */
    protected function actingAsAdmin(array $attributes = []): User
    {
        $adminRole = Role::factory()->create(['slug' => 'admin', 'name' => 'Administrateur']);
        $admin = User::factory()->create(array_merge([
            'role_id' => $adminRole->id
        ], $attributes));
        
        Sanctum::actingAs($admin);
        return $admin;
    }

    /**
     * Créer et authentifier un utilisateur standard
     */
    protected function actingAsUser(array $attributes = []): User
    {
        $userRole = Role::factory()->create(['slug' => 'user', 'name' => 'Utilisateur']);
        $user = User::factory()->create(array_merge([
            'role_id' => $userRole->id
        ], $attributes));
        
        Sanctum::actingAs($user);
        return $user;
    }

    /**
     * Créer un utilisateur avec des permissions spécifiques
     */
    protected function actingAsUserWithPermissions(array $permissions, array $attributes = []): User
    {
        $role = Role::factory()->create();
        
        foreach ($permissions as $permissionAction) {
            $permission = Permission::firstOrCreate(['action' => $permissionAction], [
                'name' => ucfirst(str_replace('-', ' ', $permissionAction)),
                'category' => explode('-', $permissionAction)[0] ?? 'general'
            ]);
            $role->permissions()->attach($permission->id);
        }

        $user = User::factory()->create(array_merge([
            'role_id' => $role->id
        ], $attributes));
        
        Sanctum::actingAs($user);
        return $user;
    }

    /**
     * Créer un super admin
     */
    protected function actingAsSuperAdmin(array $attributes = []): User
    {
        $superAdminRole = Role::factory()->create([
            'slug' => 'super-admin',
            'name' => 'Super Administrateur',
            'level' => 100
        ]);
        
        $superAdmin = User::factory()->create(array_merge([
            'role_id' => $superAdminRole->id
        ], $attributes));
        
        Sanctum::actingAs($superAdmin);
        return $superAdmin;
    }
}
