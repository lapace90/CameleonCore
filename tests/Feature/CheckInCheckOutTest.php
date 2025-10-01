<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Product;
use App\Models\Role;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CheckInCheckOutTest extends TestCase
{
    use DatabaseMigrations;

    private User $admin;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un admin pour les tests API
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Administrateur']
        );

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@test.com'
        ]);

        // Créer un client et un produit pour les réservations
        $this->customer = Customer::create([
            'name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@test.com'
        ]);

        $this->product = Product::first() ?? Product::factory()->create();
    }

    // =============================
    // TESTS MÉTIER (Modèle)
    // =============================

    #[Test]
    public function can_check_in_when_status_is_confirmed()
    {
        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'confirmed',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        $this->assertTrue($reservation->canCheckIn());
        $this->assertFalse($reservation->canCheckOut());
    }

    #[Test]
    public function cannot_check_in_when_already_checked_in()
    {
        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'confirmed',
            'actual_checkin' => now(), // Déjà fait
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        $this->assertFalse($reservation->canCheckIn());
    }

    #[Test]
    public function can_check_out_when_status_is_checked_in()
    {
        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'checked_in',
            'actual_checkin' => now(),
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        $this->assertTrue($reservation->canCheckOut());
        $this->assertFalse($reservation->canCheckIn());
    }

    #[Test]
    public function cannot_check_out_when_already_checked_out()
    {
        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'checked_in',
            'actual_checkin' => now(),
            'actual_checkout' => now(), // Déjà fait
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        $this->assertFalse($reservation->canCheckOut());
    }

    // =============================
    // TESTS API (Endpoints)
    // =============================

    #[Test]
    public function admin_can_check_in_a_confirmed_reservation()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'confirmed',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        $response = $this->putJson("/api/admin/reservations/{$reservation->id}", [
            'status' => 'checked_in'
        ]);

        $response->assertStatus(201);

        $reservation->refresh();
        $this->assertEquals('checked_in', $reservation->status);
        $this->assertNotNull($reservation->actual_checkin);
    }

    #[Test]
    public function admin_can_check_out_a_checked_in_reservation()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'confirmed',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        $checkinResponse = $this->putJson("/api/admin/reservations/{$reservation->id}", [
            'status' => 'checked_in'  // ⬅️ MANQUAIT
        ]);

        dump($checkinResponse->json()); // ⬅️ Juste ça

        $checkinResponse->assertStatus(201);

        $reservation->refresh();

        dump([
            'status' => $reservation->status,
            'actual_checkin' => $reservation->actual_checkin,
        ]);

        $this->assertEquals('checked_in', $reservation->status);

        // Maintenant faire le check-out
        $response = $this->putJson("/api/admin/reservations/{$reservation->id}", [
            'status' => 'checked_out'
        ]);
        $response->assertStatus(201);

        $reservation->refresh();
        $this->assertEquals('checked_out', $reservation->status);
        $this->assertNotNull($reservation->actual_checkout);
    }

    #[Test]
    public function cannot_check_in_pending_reservation()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'pending', // Pas confirmée
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'pending'
        ]);

        $response = $this->putJson("/api/admin/reservations/{$reservation->id}", [
            'status' => 'checked_in'
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function cannot_check_out_before_check_in()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'confirmed', // Pas encore checked_in
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        $response = $this->putJson("/api/admin/reservations/{$reservation->id}", [
            'status' => 'checked_out'
        ]);
        dump([
            'status' => $reservation->status,
            'actual_checkin' => $reservation->actual_checkin,
            'actual_checkout' => $reservation->actual_checkout,
            'canCheckOut' => $reservation->canCheckOut()
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function check_in_with_custom_datetime()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'confirmed',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        $customTime = Carbon::now()->subHours(2);

        $response = $this->putJson("/api/admin/reservations/{$reservation->id}", [
            'at' => $customTime->toIso8601String()
        ]);

        $response->assertStatus(201);

        $reservation->refresh();
        $this->assertEquals(
            $customTime->format('Y-m-d H:i'),
            $reservation->actual_checkin->format('Y-m-d H:i')
        );
    }

    #[Test]
    public function cannot_check_out_before_check_in_time()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $checkinTime = Carbon::now();

        $reservation = Reservation::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'product_type' => 'room',
            'date' => now(),
            'checkin' => now(),
            'checkout' => now()->addDays(3),
            'amount' => 300.00,
            'status' => 'checked_in',
            'actual_checkin' => $checkinTime,
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'booking_source' => 'website',
            'payment_status' => 'paid'
        ]);

        // Essayer de faire check-out AVANT le check-in
        $checkoutTime = $checkinTime->copy()->subHours(1);

        $response = $this->putJson("/api/admin/reservations/{$reservation->id}", [
            'at' => $checkoutTime->toIso8601String()
        ]);

        $response->assertStatus(422);
    }

    // #[Test]
    // public function guest_cannot_check_in_reservation()
    // {
    //     $reservation = Reservation::create([
    //         'customer_id' => $this->customer->id,
    //         'product_id' => $this->product->id,
    //         'product_type' => 'room',
    //         'date' => now(),
    //         'checkin' => now(),
    //         'checkout' => now()->addDays(3),
    //         'amount' => 300.00,
    //         'status' => 'confirmed',
    //         'number_of_adults' => 2,
    //         'number_of_children' => 0,
    //         'booking_source' => 'website',
    //         'payment_status' => 'paid'
    //     ]);

    //     // Pas de Sanctum::actingAs() = guest
    //     $response = $this->putJson("/api/admin/reservations/{$reservation->id}", [
    //     'status' => 'checked_in'
    // ]);

    //     $response->assertStatus(401);
    // }
}
