<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Activity::class;

    public function definition(): array
    {
        return [
            'guide' => $this->faker->name(),
            'duration' => $this->faker->numberBetween(30, 600), // Duration in minutes
            'meeting_point' => $this->faker->address(),
            'max_people' => $this->faker->numberBetween(1, 20),
            'difficulty_level' => $this->faker->numberBetween(1, 5),
        ];
    }
}
