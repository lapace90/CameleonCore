<?php
// tests/Feature/ProductApiTest.php
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
use Illuminate\Http\UploadedFile;
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

    /** @test */
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
        $response = $this->getJson('/api/products');

        // Assert - CORRECTION: Structure Laravel/API Platform réelle
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
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
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'to', 'total']
            ]);
    }

    /** @test */
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
        $response = $this->getJson('/api/products');

        // Assert
        $response->assertStatus(200);
    }


    /** @test */
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
        $this->assertEquals(1, $response->json('totalItems'));
    }

    /** @test */
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
                'type_config',
                'productable_detail',
                'statistics',
                'detail_fields'
            ])
            ->assertJson([
                'id' => $product->id,
                'name' => $product->name,
                'productable_detail' => [
                    'guide' => 'John Doe',
                    'duration' => 120,
                    'max_people' => 10
                ]
            ]);
    }

    /** @test */
    public function returns_404_for_non_existent_product()
    {
        $response = $this->getJson('/api/products/999');
        $response->assertStatus(404);
    }

    /** @test */
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
            ])
            ->assertJson([
                'name' => 'Test Activity',
                'price' => 99.99,
                'productable_detail' => [
                    'guide' => 'Jane Smith',
                    'duration' => 180
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Activity',
            'price' => 99.99,
            'productable_type' => 'App\\Models\\Activity'
        ]);

        $this->assertDatabaseHas('activities', [
            'guide' => 'Jane Smith',
            'duration' => 180,
            'difficulty_level' => 'medium'
        ]);
    }

    /** @test */
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

    /** @test */
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
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    /** @test */
    public function can_create_menu_with_dishes()
    {
        // Arrange
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
            'productableType' => 'App\\Models\\Menu',
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

    /** @test */
    public function validates_required_fields()
    {
        // Act
        $response = $this->postJson('/api/products', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'productableType']);
    }

    /** @test */
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
        $this->assertEquals(1, $response->json('totalItems'));
        $this->assertStringContainsString('Mountain', $response->json('member.0.name'));
    }

    /** @test */
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
        $this->assertEquals(25, $response->json('totalItems'));
        $this->assertCount(10, $response->json('member'));
    }
}
