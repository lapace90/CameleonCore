<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;
use App\Models\Activity;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Option;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $class = $this->faker->randomElement([
            Room::class,
            Activity::class,
            Dish::class,
            Menu::class,
        ]);

        // Créer l'instance correspondante pour le productable
        $productable = match ($class) {
            Room::class => Room::factory()->create(),
            Activity::class => Activity::factory()->create(),
            Dish::class => Dish::factory()->create(),
            Menu::class => Menu::factory()->create(),
        };

        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'category_id' => function () {
                $category = Category::inRandomOrder()->first();
                if (!$category) {
                    Log::error('Aucune catégorie trouvée pour l\'ajout d\'un produit.');
                    return null;
                } else {
                    Log::info('Catégorie trouvée : ' . $category->id);
                    return $category->id;
                }
            },
            'productable_type' => get_class($productable),
            'productable_id' => $productable->id,
            'image' => $this->faker->imageUrl(),
            'status' => $this->faker->boolean(),
            'is_draft' => $this->faker->boolean(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            $options = Option::inRandomOrder()->take(3)->pluck('id');
            $product->options()->attach($options);
            Log::info("Options attachées au produit ID {$product->id}: " . implode(', ', $options->toArray()));
        });
    }
}

