<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \App\Models\Ingredient::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_vegetarian' => $this->faker->boolean(),
            'is_spicy' => $this->faker->boolean(),
            'is_gluten_free' => $this->faker->boolean(),
            'is_lactose_free' => $this->faker->boolean(),
            'is_nut_free' => $this->faker->boolean(),
            'is_vegan' => $this->faker->boolean(),
            'stock' => $this->faker->numberBetween(0, 100),
        ];
    }
}
