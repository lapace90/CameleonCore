<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Option;
use App\Models\Room;
use App\Models\Activity;
use App\Models\Dish;
use App\Models\Menu;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Option>
 */
class OptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Option::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Choisir un type de produit polymorphique
        $productType = $this->faker->randomElement([
            Room::class,
            Activity::class,
            Dish::class,
            Menu::class,
        ]);

        // Créer un produit lié au type polymorphique
        $productable = match ($productType) {
            Room::class => Room::factory()->create(),
            Activity::class => Activity::factory()->create(),
            Dish::class => Dish::factory()->create(),
            Menu::class => Menu::factory()->create(),
        };

        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 1, 100),
            // Utilisation de `for()` pour gérer la relation polymorphique
            'productable_id' => $productable->id,
            'productable_type' => $productType,
        ];
    }
}
