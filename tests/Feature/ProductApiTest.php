<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\Activity;
use App\Models\Room;
use App\Models\Menu;
use App\Models\Dish;
use App\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_get_products_collection()
    {
        // Arrange
        $category = Category::factory()->create();
        $activity = Activity::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'productable_type' => Activity::class,
            'productable_id' => $activity->id,
            'status' => true
        ]);

        // Act
        $response = $this->get('/api/products', ['Accept' => 'application/ld+json']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'hydra:member' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'formatted_price',
                        'status',
                        'productable_type',
                        'typeConfig'
                    ]
                ],
                'hydra:totalItems'
            ]);
        $this->assertSame(1, $response->json('hydra:totalItems'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function products_collection_handles_models_without_specific_tags()
    {
        // Arrange
        $ingredient = Ingredient::factory()->create();
        Product::factory()->create([
            'productable_type' => Ingredient::class,
            'productable_id' => $ingredient->id,
            'status' => true,
        ]);

        // Act
       $response = $this->get('/api/products', ['Accept' => 'application/ld+json']);

        // Assert
        $response->assertStatus(200);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function can_filter_products_by_type()
    {
        // Arrange
        $activity = Activity::factory()->create();
        $room = Room::factory()->create();

        Product::factory()->create([
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        Product::factory()->create([
            'productable_type' => Room::class,
            'productable_id' => $room->id
        ]);

        // Act
        $response = $this->getJson('/api/products?type=App\\Models\\Activity');

        // Assert
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('hydra:totalItems'));

        $members = $response->json('hydra:member');
        $this->assertCount(1, $members);
        $this->assertSame(Activity::class, $members[0]['productable_type']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_get_single_product()
    {
        // Arrange
        $activity = Activity::factory()->create([
            'guide' => 'John Doe',
            'duration' => 120,
            'max_people' => 10
        ]);

        $product = Product::factory()->create([
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        // Act
        $response = $this->getJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'price',
                'formatted_price',
                'status',
                'status_label',
                'typeConfig',
                'productable_detail',
                'statistics',
                'detail_fields'
            ]);
        $data = $response->json();
        $this->assertSame($product->id, $data['id']);
        $this->assertSame($product->name, $data['name']);
        $this->assertSame('John Doe', $data['productable_detail']['guide']);
        $this->assertSame(120, $data['productable_detail']['duration']);
        $this->assertSame(10, $data['productable_detail']['max_people']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function returns_404_for_non_existent_product()
    {
        $response = $this->getJson('/api/products/999');
        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_create_activity_product()
    {
        // Arrange
        $category = Category::factory()->create();

        $productData = [
            'name' => 'Mountain Adventure',
            'description' => 'An exciting mountain hiking experience',
            'price' => 99.99,
            'productableType' => 'App\\Models\\Activity',
            'productable' => [
                'guide' => 'Jane Smith',
                'duration' => 180,
                'meeting_point' => 'Base Camp',
                'max_people' => 12,
                'difficulty_level' => 3 // CORRECTION: Integer au lieu de 'medium'
            ],
            'categoryId' => $category->id,
            'status' => true,
            'is_draft' => false
        ];

        // Act
        $response = $this->postJson('/api/products', $productData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'price',
                'productable_detail'
            ]);
        $data = $response->json();

        $this->assertSame('Mountain Adventure', $data['name']);
        $this->assertSame(99.99, $data['price']);
        $this->assertSame('Jane Smith', $data['productable_detail']['guide']);
        $this->assertSame(180, $data['productable_detail']['duration']);

        $this->assertDatabaseHas('products', [
            'name' => 'Mountain Adventure',
            'price' => 99.99,
            'productable_type' => Activity::class
        ]);

        $this->assertDatabaseHas('activities', [
            'guide' => 'Jane Smith',
            'duration' => 180,
            'difficulty_level' => 3
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_update_product()
    {
        // Arrange
        $activity = Activity::factory()->create();
        $product = Product::factory()->create([
            'productable_type' => Activity::class,
            'productable_id' => $activity->id,
            'name' => 'Original Name'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'price' => 149.99,
            'productable' => [
                'guide' => 'Updated Guide'
            ]
        ];

        // Act
        $response = $this->patchJson("/api/products/{$product->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Name',
                'price' => 149.99
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'price' => 149.99
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_delete_product()
    {
        // Arrange
        $activity = Activity::factory()->create();
        $product = Product::factory()->create([
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        // Act
        $response = $this->deleteJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('activities', ['id' => $activity->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_create_menu_with_dishes()
    {
        // Arrange
        $category = Category::factory()->create();
        $dish1 = Dish::factory()->create();
        $dish2 = Dish::factory()->create();

        Product::factory()->create([
            'productable_type' => Dish::class,
            'productable_id' => $dish1->id
        ]);

        Product::factory()->create([
            'productable_type' => Dish::class,
            'productable_id' => $dish2->id
        ]);

        $menuData = [
            'name' => 'Test Menu',
            'price' => 29.99,
            'productableType' => Menu::class,
            'categoryId' => $category->id,
            'productable' => [],
            'relations' => [
                'dishes' => [$dish1->id, $dish2->id]
            ]
        ];

        // Act
        $response = $this->postJson('/api/products', $menuData);

        // Assert
        $response->assertStatus(201);

        $menu = Menu::find($response->json('productable_detail.id'));
        $this->assertCount(2, $menu->dishes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validates_required_fields()
    {
        // Act
        $response = $this->postJson('/api/products', [
            'name' => 'Invalid Product',
            'price' => 99.99,
            'productableType' => 'Invalid\\Type',
            'productable' => []
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['productableType']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_search_products()
    {
        // Arrange
        $activity = Activity::factory()->create();
        Product::factory()->create([
            'name' => 'Mountain Hiking',
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        Product::factory()->create([
            'name' => 'Beach Volleyball',
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        // Act
        $response = $this->getJson('/api/products?search=mountain');

        // Assert
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('hydra:totalItems'));
        $this->assertStringContainsString('Mountain', $response->json('hydra:member.0.name'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_paginate_products()
    {
        // Arrange
        $activity = Activity::factory()->create();
        Product::factory()->count(25)->create([
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        // Act
        $response = $this->getJson('/api/products?per_page=10&page=2');

        // Assert
        $response->assertStatus(200);
        $this->assertSame(25, $response->json('hydra:totalItems'));
        $this->assertCount(10, $response->json('hydra:member'));
    }
}
