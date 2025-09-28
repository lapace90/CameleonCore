<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Option;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        // Créer un Room simple pour la relation polymorphe
        $room = \App\Models\Room::firstOrCreate([
            'capacity' => 4
        ], [
            'availability' => true
        ]);
        
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'category_id' => Category::factory(), // AJOUT: Créer automatiquement
            'productable_type' => \App\Models\Room::class,
            'productable_id' => $room->id,
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
