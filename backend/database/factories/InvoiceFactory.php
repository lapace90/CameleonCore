<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \App\Models\Invoice::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->unique()->numerify('INV-#####'),
            'amount' => $this->faker->randomFloat(2, 100, 1000),
            'issue_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => $this->faker->randomElement(['paid', 'unpaid', 'overdue']),
            'customer_id' => \App\Models\Customer::factory(),  // OK - lazy
            'reservation_id' => null,                          // PAS de circular ref
        ];
    }
}
