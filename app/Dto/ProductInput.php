<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Models\Product;    

class ProductInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $name;
    
    #[Assert\Length(max: 1000)]
    public ?string $description = null;
    
    #[Assert\NotBlank]
    #[Assert\Positive]
    public float $price;
    
    public bool $status = true;
    public bool $is_draft = false;
    
    public ?int $category_id = null;
    
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [
        'App\\Models\\Activity',
        'App\\Models\\Room',
        'App\\Models\\Menu',
        'App\\Models\\Dish',
        'App\\Models\\Ingredient'
    ])]
    public string $productable_type;
    
    #[Assert\Valid]
    public ?ProductableInput $productable = null;
    
    public ?string $image = null;
    public array $tags = [];
    public array $options = [];
    
    // Relations
    public array $ingredients = []; // Pour les plats
    public array $dishes = []; // Pour les menus
}

class ProductableInput
{
    // Activity fields
    public ?string $guide = null;
    public ?int $duration = null;
    public ?string $meeting_point = null;
    public ?int $max_people = null;
    #[Assert\Choice(choices: ['easy', 'medium', 'hard'])]
    public ?string $difficulty_level = null;
    
    // Room fields
    public ?int $capacity = null;
    public ?bool $availability = null;
    
    // Ingredient fields
    public ?int $stock = null;
    public bool $is_vegetarian = false;
    public bool $is_vegan = false;
    public bool $is_spicy = false;
    public bool $is_gluten_free = false;
    public bool $is_lactose_free = false;
    public bool $is_nut_free = false;
}

class ProductOutput
{
    public int $id;
    public string $name;
    public ?string $description;
    public float $price;
    public bool $status;
    public bool $is_draft;
    public ?string $image;
    public string $productable_type;
    public array $productable_data;
    public ?CategoryOutput $category;
    public array $tags;
    public array $options;
    
    // Relations formatées
    public ?array $dishes = null; // Pour les menus
    public ?array $ingredients = null; // Pour les plats
    
    // Statistiques
    public array $stats;
    
    public static function fromProduct(Product $product): self
    {
        $output = new self();
        $output->id = $product->id;
        $output->name = $product->name;
        $output->description = $product->description;
        $output->price = $product->price;
        $output->status = $product->status;
        $output->is_draft = $product->is_draft;
        $output->image = $product->image;
        $output->productable_type = $product->productable_type;
        
        // Données productable
        if ($product->productable) {
            $output->productable_data = $product->productable->toArray();
        }
        
        // Catégorie
        if ($product->category) {
            $output->category = CategoryOutput::fromCategory($product->category);
        }
        
        // Tags
        $output->tags = $product->globalTags->map(fn($tag) => [
            'id' => $tag->id,
            'name' => $tag->name
        ])->toArray();
        
        // Options
        $output->options = $product->options->map(fn($option) => [
            'id' => $option->id,
            'name' => $option->name,
            'price' => $option->price
        ])->toArray();
        
        // Statistiques
        $output->stats = [
            'views' => $product->views ?? 0,
            'reservations_count' => $product->reservations()->count(),
            'average_rating' => round($product->reviews()->avg('rating') ?? 0, 2),
            'total_revenue' => $product->reservations()->sum('total_price') ?? 0
        ];
        
        return $output;
    }
}

class CategoryOutput
{
    public int $id;
    public string $name;
    
    public static function fromCategory($category): self
    {
        $output = new self();
        $output->id = $category->id;
        $output->name = $category->name;
        return $output;
    }
}