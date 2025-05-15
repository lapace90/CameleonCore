<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \App\Models\Reservation::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory()->create()->id,
            'date' => $this->faker->date(),
            'checkin' => $this->faker->dateTimeBetween('now', '+1 week'),
            'checkout' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'amount' => $this->faker->randomFloat(2, 50, 1000),
            'invoice_number' => \App\Models\Invoice::factory(),
            'booking_source' => $this->faker->randomElement(['website', 'phone', 'agent']),
            'payment_status' => $this->faker->randomElement(['paid', 'pending', 'failed']),
            'number_of_children' => $this->faker->numberBetween(0, 5),
            'number_of_adults' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['confirmed', 'cancelled', 'pending']),
            'user_id' => \App\Models\User::factory(),
            'product_id' => \App\Models\Product::factory(),
            'product_type' => $this->faker->randomElement(['room', 'package', 'service']),
        ];
    }
}
