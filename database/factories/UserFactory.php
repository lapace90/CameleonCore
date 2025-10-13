<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            
            //  CHAMPS PROFIL
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'avatar' => null, // Pas d'avatar par défaut
            
            // Champs existants déjà dans la migration
            'status' => 'active',
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),
            'last_login_ip' => fake()->optional(0.7)->ipv4(),
            'password_reset_required' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user with specific role
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin ' . fake()->lastName(),
            'email' => 'admin' . rand(1, 100) . '@campcanteloup.fr',
            'phone' => '+33 6 ' . sprintf('%02d %02d %02d %02d', rand(10, 99), rand(10, 99), rand(10, 99), rand(10, 99)),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Marseille', 'Cassis', 'Bandol', 'Sanary-sur-Mer']),
            'postal_code' => fake()->randomElement(['13000', '13260', '83150', '83110']),
            'last_login_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'last_login_ip' => fake()->ipv4(),
        ]);
    }

    /**
     * Create a manager user
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Manager ' . fake()->lastName(),
            'email' => 'manager' . rand(1, 100) . '@campcanteloup.fr',
            'phone' => '+33 7 ' . sprintf('%02d %02d %02d %02d', rand(10, 99), rand(10, 99), rand(10, 99), rand(10, 99)),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Toulon', 'Hyères', 'La Ciotat', 'Aubagne']),
            'postal_code' => fake()->randomElement(['83000', '83400', '13600', '13400']),
            'last_login_at' => fake()->dateTimeBetween('-3 days', 'now'),
            'last_login_ip' => fake()->ipv4(),
        ]);
    }

    /**
     * Create an employee user
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->firstName() . ' ' . fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.8)->phoneNumber(), // 80% ont un téléphone
            'address' => fake()->optional(0.6)->streetAddress(), // 60% ont une adresse
            'city' => fake()->optional(0.6)->city(),
            'postal_code' => fake()->optional(0.6)->postcode(),
            'last_login_at' => fake()->optional(0.5)->dateTimeBetween('-2 weeks', 'now'),
            'last_login_ip' => fake()->optional(0.5)->ipv4(),
        ]);
    }
}