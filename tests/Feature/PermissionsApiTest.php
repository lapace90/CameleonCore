<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionsApiTest extends TestCase
{
    use RefreshDatabase;

    // Désactive un éventuel seeding global défini dans TestCase
    protected bool $seed = false;

    protected User $user;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Rôle admin idempotent
        $this->adminRole = $this->firstOrCreateRole('admin', 'Admin', 'Administrateur');

        $this->user = User::factory()->create();
        $this->user->role_id = $this->adminRole->id;
        $this->user->save();
        $this->user->roles()->sync([$this->adminRole->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_grouped_permissions_with_stats()
    {
        // Permissions idempotentes (respect des contraintes unique sur name + action)
        $systemPerm = $this->ensurePermission('system-admin', 'Administration système', 'system');
        $usersPerm  = $this->ensurePermission('users-read', 'Lire utilisateurs', 'users');

        // Lier une permission système à un rôle pour les stats
        $role = $this->firstOrCreateRole('manager', 'Manager', 'Responsable');
        $role->permissions()->syncWithoutDetaching([$systemPerm->id]);

        // Appel API
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/admin/permissions/grouped');

        $response->assertStatus(200);
        $data = $response->json();

        // Structure
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertNotEmpty($data['categories']);

        // Catégorie "system" doit exister
        $systemCategory = collect($data['categories'])->firstWhere('key', 'system');
        $this->assertNotNull($systemCategory);

        // Permission "system-admin" présente
        $systemPermission = collect($systemCategory['permissions'])->firstWhere('action', 'system-admin');
        $this->assertNotNull($systemPermission);

        // Nom + style (si ton provider les renvoie)
        $this->assertEquals('Administration système', $systemPermission['name']);
        $this->assertTrue(
            in_array($systemPermission['badge_class'], [
                'badge-danger',
                'badge-warning',
                'badge-info',
                'badge-success',
                'badge-secondary'
            ], true),
            'Unexpected badge_class: ' . $systemPermission['badge_class']
        );
        $this->assertTrue($systemPermission['is_critical']);

        // Stats globales
        $this->assertArrayHasKey('total_permissions', $data['stats']);
        $this->assertArrayHasKey('used_permissions', $data['stats']);
        $this->assertArrayHasKey('usage_percentage', $data['stats']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_permission_usage_correctly()
    {
        // Assure l’existence (sans collisions)
        $permission = $this->ensurePermission('users-read', 'Users read', 'users');

        $role1 = $this->firstOrCreateRole('role-a', 'Role A', 'A');
        $role2 = $this->firstOrCreateRole('role-b', 'Role B', 'B');

        // Liaison permission ↔ rôles (idempotent)
        $role1->permissions()->syncWithoutDetaching([$permission->id]);
        $role2->permissions()->syncWithoutDetaching([$permission->id]);

        // Créer des users rattachés à ces rôles principaux
        User::factory()->count(3)->create(['role_id' => $role1->id]);
        User::factory()->count(2)->create(['role_id' => $role2->id]);

        $users1 = User::factory()->count(3)->create(['role_id' => $role1->id]);
        foreach ($users1 as $u) {
            $u->roles()->syncWithoutDetaching([$role1->id]);
        }

        $users2 = User::factory()->count(2)->create(['role_id' => $role2->id]);
        foreach ($users2 as $u) {
            $u->roles()->syncWithoutDetaching([$role2->id]);
        }


        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/admin/permissions/grouped');

        $response->assertStatus(200);
        $data = $response->json();

        // Retrouver "users-read"
        $foundPermission = null;
        foreach ($data['categories'] as $category) {
            foreach ($category['permissions'] as $perm) {
                if ($perm['action'] === 'users-read') {
                    $foundPermission = $perm;
                    break 2;
                }
            }
        }

        $this->assertNotNull($foundPermission);

        // ▶️ Comparer au count réel en base (robuste aux seeds)
        $permissionModel = Permission::where('action', 'users-read')->first();
        $expectedRolesCount = $permissionModel ? $permissionModel->roles()->count() : 0;
        $this->assertEquals($expectedRolesCount, $foundPermission['roles_count']);

        // Un minimum d’utilisateurs liés
        $this->assertGreaterThan(0, $foundPermission['users_count']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_authentication()
    {
        // Pas d’actingAs ici
        $response = $this->getJson('/api/admin/permissions/grouped');

        $response->assertStatus(401);
    }

    // ----------------------------------------------------------
    // Helpers idempotents (évite toutes collisions uniques)
    // ----------------------------------------------------------

    private function firstOrCreateRole(string $slug, string $name, string $description): Role
    {
        return Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'description' => $description]
        );
    }

    /**
     * Garantit l’existance d’une permission avec (action, name, category)
     * en respectant les contraintes uniques sur 'action' et 'name'.
     */
    private function ensurePermission(string $action, string $name, ?string $category = null): Permission
    {
        // 1) Priorité : trouver par action
        $perm = Permission::where('action', $action)->first();
        if ($perm) {
            $updates = [];
            if ($perm->name !== $name) {
                $updates['name'] = $name;
            }
            if ($category !== null && $perm->category !== $category) {
                $updates['category'] = $category;
            }
            if (!empty($updates)) {
                $perm->update($updates);
            }
            return $perm;
        }

        // 2) Sinon, essayer par name (unique aussi)
        $permByName = Permission::where('name', $name)->first();
        if ($permByName) {
            // si 'action' est libre, on l’adopte
            if (!Permission::where('action', $action)->exists()) {
                $updates = ['action' => $action];
                if ($category !== null) {
                    $updates['category'] = $category;
                }
                $permByName->update($updates);
                return $permByName;
            }
            // Si l'action est déjà prise ailleurs, on réutilise celle qui a l'action
            return Permission::where('action', $action)->first();
        }

        // 3) Aucun conflit : on peut créer proprement
        $attrs = ['name' => $name, 'action' => $action];
        if ($category !== null) {
            $attrs['category'] = $category;
        }
        return Permission::create($attrs);
    }
}
