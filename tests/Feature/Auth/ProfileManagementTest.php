<?php
// tests/Feature/Auth/ProfileManagementTest.php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;         // user non-admin (auto-édition)
    private Role $adminRole;    // rôle admin idempotent
    private Role $nonAdminRole; // rôle non-admin idempotent

    protected function setUp(): void
    {
        parent::setUp();

        // Rôles de référence, idempotents (alignés à ton modèle)
        $this->adminRole    = $this->firstOrCreateRole('admin', 'Admin', 'Administrateur');
        // Choix d’un rôle explicitement non-admin parmi ceux présents dans ton modèle
        // (cf. Role::isAdminRole() -> 'employee' n’est PAS admin)
        $this->nonAdminRole = $this->firstOrCreateRole('employee', 'Employé', 'Utilisateur standard');

        // Utilisateur par défaut : NON-ADMIN (pour tester l’auto-édition + interdiction d’éditer autrui)
        $this->user = User::factory()->create([
            'name'        => 'Jean Dupont',
            'email'       => 'jean.dupont@example.com',
            'phone'       => '+33 6 12 34 56 78',
            'address'     => '123 Rue de la Paix',
            'city'        => 'Marseille',
            'postal_code' => '13000',
            'role_id'     => $this->nonAdminRole->id,  // principal
        ]);
        $this->user->roles()->sync([$this->nonAdminRole->id]); // additionnels
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_their_profile()
    {
        Sanctum::actingAs($this->user, ['*']);

        // ⚠️ Payload côté test : on peut envoyer snake_case (validé) OU camelCase si ton Processor normalise avant validation.
        // Ici on reste en snake_case pour éviter tout doute côté validation.
        $updateData = [
            'name'        => 'Jean-Pierre Dupont',
            'phone'       => '+33 7 98 76 54 32',
            'address'     => '456 Avenue de la Liberté',
            'city'        => 'Toulon',
            'postal_code' => '83000',
        ];

        $response = $this->patchJson("/api/admin/users/{$this->user->id}", $updateData);

        // Réponse API Platform (DTO) attendue en camelCase
        $response->assertStatus(200)
            ->assertJson([
                'name'       => 'Jean-Pierre Dupont',
                'phone'      => '+33 7 98 76 54 32',
                'address'    => '456 Avenue de la Liberté',
                'city'       => 'Toulon',
                'postalCode' => '83000',
            ]);

        $this->user->refresh();
        $this->assertEquals('Jean-Pierre Dupont', $this->user->name);
        $this->assertEquals('Toulon', $this->user->city);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_update_other_users_profile()
    {
        // User courant = NON-ADMIN
        Sanctum::actingAs($this->user, ['*']);

        $otherUser = User::factory()->create([
            'role_id' => $this->nonAdminRole->id,
        ]);
        $otherUser->roles()->sync([$this->nonAdminRole->id]);

        $response = $this->patchJson("/api/admin/users/{$otherUser->id}", [
            'name' => 'Hacked Name',
        ]);

        // ✅ Doit être bloqué par la Policy -> 403
        $response->assertStatus(403);

        $otherUser->refresh();
        $this->assertNotEquals('Hacked Name', $otherUser->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_change_password()
    {
        $this->user->update(['password' => Hash::make('old-password')]);
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->patchJson("/api/admin/users/{$this->user->id}", [
            'current_password'     => 'old-password',
            'password'             => 'new-secure-password',
            'password_confirmation'=> 'new-secure-password',
        ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $this->user->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function password_change_requires_current_password()
    {
        $this->user->update(['password' => Hash::make('current-password')]);
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->patchJson("/api/admin/users/{$this->user->id}", [
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['current_password']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function profile_validation_works_correctly()
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->patchJson("/api/admin/users/{$this->user->id}", [
            // Pour se concentrer sur phone+postal, on peut laisser un email valide ou ne pas l’envoyer.
            'phone'       => '123', // invalide (regex min 6 etc.)
            'postalCode'  => '!!',  // sera normalisé en postal_code avant validation => invalide
        ]);

        $response->assertStatus(422)
                 // ⚠️ Les clés d'erreurs suivent les noms validés serveur (après normalisation) : snake_case
                 ->assertJsonValidationErrors(['phone', 'postal_code']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_any_user_profile()
    {
        // Créer/charger un admin idempotent et s’authentifier
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $admin->roles()->sync([$this->adminRole->id]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->patchJson("/api/admin/users/{$this->user->id}", [
            'name' => 'Updated by Admin',
            'city' => 'Updated City',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'name' => 'Updated by Admin',
                     'city' => 'Updated City',
                 ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function email_update_requires_unique_email()
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        Sanctum::actingAs($this->user, ['*']);

        $response = $this->patchJson("/api/admin/users/{$this->user->id}", [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function debug_routes_and_auth()
    {
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $admin->roles()->sync([$this->adminRole->id]);

        Sanctum::actingAs($admin, ['*']);

        $response1 = $this->getJson('/admin/users');
        echo "\nCollection (/admin/users): " . $response1->getStatusCode();

        $response2 = $this->getJson("/api/admin/users/{$admin->id}");
        echo "\nItem GET: " . $response2->getStatusCode();

        $response3 = $this->patchJson("/api/admin/users/{$admin->id}", ['name' => 'Test Update']);
        echo "\nPATCH: " . $response3->getStatusCode();

        if ($response3->getStatusCode() !== 200) {
            echo "\nError: " . $response3->getContent();
        }

        $this->assertTrue(true);
    }

    // ---------------------------------------------------------------------
    // Helpers privés (pas besoin de nouveaux fichiers/traits)
    // ---------------------------------------------------------------------

    private function firstOrCreateRole(string $slug, string $name, string $description): Role
    {
        return Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'description' => $description]
        );
    }
}
