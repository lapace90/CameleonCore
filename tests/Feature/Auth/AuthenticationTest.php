<?php
// tests/Feature/Auth/AuthenticationTest.php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_login_with_valid_credentials()
    {
        // Arrange
        $role = Role::factory()->create(['slug' => 'user']);
        $user = User::factory()->create([
            'email' => 'test@campcameleonx.fr',
            'password' => Hash::make('password123'),
            'role_id' => $role->id
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@campcameleonx.fr',
            'password' => 'password123'
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'city',
                    'postal_code',
                    'avatar',
                    'role',
                    'last_login_at',
                    'last_login_ip'
                ],
                'token'
            ]);

        // Vérifier que last_login est mis à jour
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function login_tracks_last_login_with_real_ip()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'last_login_at' => null,
            'last_login_ip' => null
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // Assert
        $response->assertStatus(200);

        $user->refresh();
        // Tester ce qui EST vraiment récupéré (127.0.0.1 en test local)
        $this->assertEquals('127.0.0.1', $user->last_login_ip);
        $this->assertNotNull($user->last_login_at);

        // Vérifier que c'est dans la réponse aussi
        $response->assertJsonFragment([
            'last_login_ip' => '127.0.0.1'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_login_with_invalid_credentials()
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password')
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_logout()
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Act
        $response = $this->postJson('/api/auth/logout');

        // Assert
        $response->assertStatus(200)
            ->assertJson(['message' => 'Déconnexion réussie']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_verify_token()
    {
        // Arrange
        $role = Role::factory()->create(['slug' => 'admin', 'name' => 'Administrateur']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'phone' => '+33 6 12 34 56 78',
            'address' => '123 Rue de la Paix',
            'city' => 'Toulon',
            'postal_code' => '83000'
        ]);
        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/auth/verify');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => '+33 6 12 34 56 78',
                    'address' => '123 Rue de la Paix',
                    'city' => 'Toulon',
                    'postal_code' => '83000',
                    'role' => 'Administrateur',
                    'role_id' => $role->id
                ]
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function verify_fails_without_token()
    {
        // Act
        $response = $this->getJson('/api/auth/verify');

        // Assert
        $response->assertStatus(401);
    }
}
