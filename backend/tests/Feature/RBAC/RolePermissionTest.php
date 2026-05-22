<?php
// tests/Feature/RBAC/RolePermissionTest.php

namespace Tests\Feature\RBAC;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = false;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Rôle admin idempotent
        $this->adminRole = $this->firstOrCreateRole('admin', 'Admin', 'Administrateur');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_has_permissions_from_primary_role()
    {
        // Arrange
        $permUsersRead = $this->ensurePermission('users-read', 'Lire utilisateurs', 'users');
        $roleManager   = $this->firstOrCreateRole('manager', 'Manager', 'Responsable');
        $roleManager->permissions()->syncWithoutDetaching([$permUsersRead->id]);

        $user = User::factory()->create(['role_id' => $roleManager->id]); // rôle principal = manager

        // Act
        $actions = $this->getUserPermissionActions($user);

        // Assert
        $this->assertContains('users-read', $actions);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_has_permissions_from_additional_roles()
    {
        // Arrange (pas de perms sur le principal)
        $permActivitiesManage = $this->ensurePermission('activities-manage', 'Gérer activités', 'activities');

        $roleEmployee = $this->firstOrCreateRole('employee', 'Employé', 'Employé');
        $roleGuide    = $this->firstOrCreateRole('guide', 'Guide', 'Guide');
        $roleGuide->permissions()->syncWithoutDetaching([$permActivitiesManage->id]);

        $user = User::factory()->create(['role_id' => $roleEmployee->id]); // principal = employee
        $user->roles()->syncWithoutDetaching([$roleGuide->id]);            // additionnel = guide

        // Act
        $actions = $this->getUserPermissionActions($user);

        // Assert
        $this->assertContains('activities-manage', $actions);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function permissions_are_deduplicated_across_roles()
    {
        // Arrange
        $permUsersRead = $this->ensurePermission('users-read', 'Lire utilisateurs', 'users');

        $roleA = $this->firstOrCreateRole('role-a', 'Role A', 'A');
        $roleB = $this->firstOrCreateRole('role-b', 'Role B', 'B');

        // Même permission sur 2 rôles
        $roleA->permissions()->syncWithoutDetaching([$permUsersRead->id]);
        $roleB->permissions()->syncWithoutDetaching([$permUsersRead->id]);

        // User avec rôle principal A + additionnel B
        $user = User::factory()->create(['role_id' => $roleA->id]);
        $user->roles()->syncWithoutDetaching([$roleB->id]);

        // Act
        $actions = $this->getUserPermissionActions($user);

        // Assert - déduplication côté agrégat utilisateur
        $this->assertEquals(1, collect($actions)->filter(fn($a) => $a === 'users-read')->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function role_hierarchy_is_respected_admin_cannot_assign_super_admin()
    {
        // Arrange: admin connecté
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        Sanctum::actingAs($admin);

        // Cible + rôles
        $target      = User::factory()->create(); // un autre utilisateur
        $superAdmin  = $this->firstOrCreateRole('super-admin', 'Super Admin', 'Super administrateur');

        // Act: tentative d’élévation par admin vers super-admin
        $response = $this->patchJson("/api/admin/users/{$target->id}", [
            'role_id' => $superAdmin->id
        ]);

        // Assert
        $response->assertStatus(403);
        $target->refresh();
        $this->assertNotEquals($superAdmin->id, $target->role_id);
    }

    // ------------------------------------------------------------------
    // Helpers idempotents (anti-collisions unique name/action/slug)
    // ------------------------------------------------------------------

    private function firstOrCreateRole(string $slug, string $name, string $description): Role
    {
        return Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'description' => $description]
        );
    }

    private function ensurePermission(string $action, string $name, ?string $category = null): Permission
    {
        // Priorité: par action
        $perm = Permission::where('action', $action)->first();
        if ($perm) {
            $updates = [];
            if ($perm->name !== $name) { $updates['name'] = $name; }
            if ($category !== null && $perm->category !== $category) { $updates['category'] = $category; }
            if ($updates) { $perm->update($updates); }
            return $perm;
        }

        // Sinon: par name (unique aussi)
        $permByName = Permission::where('name', $name)->first();
        if ($permByName) {
            if (!Permission::where('action', $action)->exists()) {
                $updates = ['action' => $action];
                if ($category !== null) { $updates['category'] = $category; }
                $permByName->update($updates);
                return $permByName;
            }
            return Permission::where('action', $action)->first(); // réutilise
        }

        $attrs = ['name' => $name, 'action' => $action];
        if ($category !== null) { $attrs['category'] = $category; }
        return Permission::create($attrs);
    }

    /**
     * Agrège les permissions d’un utilisateur (rôle principal + rôles additionnels)
     * et renvoie la liste d’actions unique (dedup).
     */
    private function getUserPermissionActions(User $user): array
    {
        $primary = $user->role ? $user->role->permissions()->pluck('action') : collect();
        $additional = $user->roles()->with('permissions')->get()
            ->flatMap(fn(Role $r) => $r->permissions->pluck('action'));

        return $primary->merge($additional)->unique()->values()->all();
    }
}
