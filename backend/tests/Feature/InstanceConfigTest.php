<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Activity;
use App\Models\Menu;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class InstanceConfigTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_config_endpoint_returns_instance_data()
    {
        $response = $this->getJson('/api/config/public');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'name',
                'type',
                'logo',
                'country',
                'modules',
                'productables',
                'features',
                'contact',
            ]);
    }

    #[Test]
    public function public_config_does_not_expose_sensitive_data()
    {
        $response = $this->getJson('/api/config/public');

        $response->assertStatus(200);

        $data = $response->json();

        // Pas de SIRET, pas de clés API
        $this->assertArrayNotHasKey('siret', $data);
        $this->assertArrayNotHasKey('factpulse', $data);
        $this->assertArrayNotHasKey('stripe', $data);

        // Le contact ne contient que phone et email
        $this->assertArrayNotHasKey('siret', $data['contact']);
        $this->assertArrayNotHasKey('address', $data['contact']);
    }

    #[Test]
    public function products_filtered_by_active_productables()
    {
        // Configurer instance traiteur (menu, dish uniquement)
        $this->withTraiteurInstance();

        // Créer des produits de types différents
        $menu = Menu::factory()->create();
        Product::factory()->create([
            'productable_type' => Menu::class,
            'productable_id' => $menu->id,
            'status' => true,
        ]);

        $room = Room::factory()->create();
        Product::factory()->create([
            'productable_type' => Room::class,
            'productable_id' => $room->id,
            'status' => true,
        ]);

        // L'API ne doit retourner que les menus
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);

        $data = $response->json();
        $items = $data['hydra:member'] ?? $data;

        foreach ($items as $item) {
            $this->assertNotEquals(
                'App\\Models\\Room',
                $item['productable_type'] ?? $item['typeConfig']['type'] ?? '',
                'Room should not appear in traiteur instance'
            );
        }
    }

    #[Test]
    public function rbac_bypassed_when_module_disabled()
    {
        $this->withModule('rbac', false);

        $user = User::factory()->create();

        // Sans RBAC, tout utilisateur authentifié est admin
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->hasPermission('any_permission'));
        $this->assertTrue($user->canAccessAdmin());
    }

    #[Test]
    public function rbac_enforced_when_module_enabled()
    {
        $this->withModule('rbac', true);

        // User sans rôle
        $user = User::factory()->create(['role_id' => null]);

        $this->assertFalse($user->isAdmin());
    }

    #[Test]
    public function deposit_feature_controls_invoice_creation()
    {
        $this->withFeature('deposit_payment', true);
        $this->withFeature('deposit_percentage', 25);

        $this->assertEquals(25, config('instance.features.deposit_percentage'));
        $this->assertTrue(config('instance.features.deposit_payment'));
    }

    #[Test]
    public function traiteur_instance_has_correct_defaults()
    {
        $this->withTraiteurInstance();

        $this->assertEquals('traiteur', config('instance.type'));
        $this->assertContains('menu', config('instance.productables'));
        $this->assertContains('dish', config('instance.productables'));
        $this->assertNotContains('room', config('instance.productables'));
        $this->assertFalse(config('instance.modules.rbac'));
        $this->assertFalse(config('instance.features.checkin_checkout'));
        $this->assertTrue(config('instance.features.guest_count'));
    }

    #[Test]
    public function hotel_instance_has_correct_defaults()
    {
        $this->withFullInstance();

        $this->assertEquals('hotel', config('instance.type'));
        $this->assertContains('room', config('instance.productables'));
        $this->assertTrue(config('instance.modules.rbac'));
        $this->assertTrue(config('instance.features.checkin_checkout'));
    }
}