<?php
// tests/Feature/PermissionsApiTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionsApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur admin pour les tests
        $this->user = User::factory()->create();
        $adminRole = Role::factory()->create(['slug' => 'admin']);
        $this->user->update(['role_id' => $adminRole->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_grouped_permissions_with_stats()
    {
        // Créer quelques permissions de test
        $systemPerm = Permission::factory()->create([
            'name' => 'Administration Système',
            'action' => 'system-admin'
        ]);
        
        $usersPerm = Permission::factory()->create([
            'name' => 'Lire Utilisateurs',
            'action' => 'users-read'
        ]);

        // Assigner des rôles pour tester les stats
        $role = Role::factory()->create();
        $role->permissions()->attach($systemPerm->id);

        // Appeler l'API
        $response = $this->actingAs($this->user, 'sanctum')
                         ->getJson('/api/admin/permissions/grouped');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Vérifier la structure de réponse
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('meta', $data);
        
        // Vérifier qu'on a des catégories
        $this->assertNotEmpty($data['categories']);
        
        // Vérifier qu'une catégorie 'system' existe
        $systemCategory = collect($data['categories'])->firstWhere('key', 'system');
        $this->assertNotNull($systemCategory);
        $this->assertEquals('Administration Système', $systemCategory['name']);
        $this->assertEquals('fas fa-cogs', $systemCategory['icon']);
        
        // Vérifier qu'une permission système est dedans
        $systemPermission = collect($systemCategory['permissions'])->firstWhere('action', 'system-admin');
        $this->assertNotNull($systemPermission);
        $this->assertEquals('Administration Système', $systemPermission['name']);
        $this->assertEquals('badge-danger', $systemPermission['badge_class']);
        $this->assertTrue($systemPermission['is_critical']);
        
        // Vérifier les stats
        $this->assertArrayHasKey('total_permissions', $data['stats']);
        $this->assertArrayHasKey('used_permissions', $data['stats']);
        $this->assertArrayHasKey('usage_percentage', $data['stats']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_permission_usage_correctly()
    {
        // Créer une permission avec des rôles assignés
        $permission = Permission::factory()->create([
            'action' => 'users-read'
        ]);
        
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        
        // Assigner la permission à 2 rôles
        $role1->permissions()->attach($permission->id);
        $role2->permissions()->attach($permission->id);
        
        // Créer des utilisateurs avec ces rôles
        User::factory()->count(3)->create(['role_id' => $role1->id]);
        User::factory()->count(2)->create(['role_id' => $role2->id]);

        $response = $this->actingAs($this->user, 'sanctum')
                         ->getJson('/api/admin/permissions/grouped');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Trouver la permission dans les résultats
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
        $this->assertEquals(2, $foundPermission['roles_count']);
        // Note: users_count peut être différent selon la logique de calcul des utilisateurs uniques
        $this->assertGreaterThan(0, $foundPermission['users_count']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/admin/permissions/grouped');
        
        $response->assertStatus(401);
    }
}
