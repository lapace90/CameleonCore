<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Activity;
use App\Models\Category;
use App\Services\ProductTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTransformerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function transforms_product_for_list()
    {
        // Arrange
        $category = Category::factory()->create(['name' => 'Test Category']);
        $activity = Activity::factory()->create([
            'guide' => 'John Doe',
            'duration' => 120,
            'difficulty_level' => 2 // CORRECTION: Integer au lieu de string
        ]);
        
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 99.99,
            'status' => true,
            'category_id' => $category->id,
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        $product->load(['category', 'productable']);

        // Act - CORRECTION: Passer un Product, pas une Collection
        $result = ProductTransformer::transformForList($product);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Test Product', $result['name']);
        $this->assertEquals(99.99, $result['price']);
        $this->assertEquals('99,99 €', $result['formatted_price']);
        $this->assertEquals('Actif', $result['status_label']);
        // CORRECTION: Les assertions correspondent à la vraie structure
        $this->assertEquals('success', $result['status_class']); // Pas 'status-active'
        $this->assertArrayHasKey('typeConfig', $result);
        $this->assertEquals(Activity::class, $result['productable_type']);
    }

    /** @test */
    public function transforms_product_for_display()
    {
        // Arrange
        $category = Category::factory()->create(['name' => 'Test Category']);
        $activity = Activity::factory()->create([
            'guide' => 'John Doe',
            'duration' => 120,
            'max_people' => 10,
            'difficulty_level' => 2 // CORRECTION: Integer au lieu de string
        ]);
        
        $product = Product::factory()->create([
            'productable_type' => Activity::class,
            'productable_id' => $activity->id,
            'category_id' => $category->id
        ]);

        // Act - CORRECTION: Utiliser la vraie méthode transformForDisplay
        $result = ProductTransformer::transformForDisplay($product);

        // Assert - CORRECTION: Vérifier les vraies clés retournées
        $this->assertArrayHasKey('productableDetail', $result);
        $this->assertArrayHasKey('typeConfig', $result);
        $this->assertArrayHasKey('category', $result);
        
        // Vérifier la structure de productableDetail
        $this->assertEquals('John Doe', $result['productableDetail']['guide']);
        $this->assertEquals(120, $result['productableDetail']['duration']);
        $this->assertEquals(10, $result['productableDetail']['max_people']);
        
        // Vérifier la catégorie
        $this->assertEquals('Test Category', $result['category']['name']);
    }

    /** @test */
    public function transforms_product_for_form()
    {
        // Arrange
        $category = Category::factory()->create();
        $activity = Activity::factory()->create([
            'guide' => 'Jane Smith',
            'duration' => 90,
            'difficulty_level' => 1
        ]);
        
        $product = Product::factory()->create([
            'name' => 'Form Test Product',
            'price' => 45.50,
            'productable_type' => Activity::class,
            'productable_id' => $activity->id,
            'category_id' => $category->id
        ]);

        // Act
        $result = ProductTransformer::transformForForm($product);

        // Assert
        $this->assertEquals('Form Test Product', $result['name']);
        $this->assertEquals(45.50, $result['price']);
        $this->assertArrayHasKey('productableData', $result);
        $this->assertArrayHasKey('typeConfig', $result);
        $this->assertEquals('Jane Smith', $result['productableData']['guide']);
    }

    /** @test */
    public function transforms_empty_product_for_form()
    {
        // Act
        $result = ProductTransformer::transformForForm();

        // Assert
        $this->assertEquals('', $result['name']);
        $this->assertEquals(0, $result['price']);
        $this->assertTrue($result['status']);
        $this->assertFalse($result['is_draft']);
        $this->assertEmpty($result['productableData']);
    }

    /** @test */
    public function handles_product_without_category()
    {
        // Arrange
        $activity = Activity::factory()->create();
        $product = Product::factory()->create([
            'productable_type' => Activity::class,
            'productable_id' => $activity->id,
            'category_id' => null // Pas de catégorie
        ]);

        // Act
        $result = ProductTransformer::transformForDisplay($product);

        // Assert
        $this->assertNull($result['category']);
        $this->assertArrayHasKey('productableDetail', $result);
    }

    /** @test */
    public function handles_product_without_productable()
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'productable_type' => Activity::class,
            'productable_id' => 999999, // ID inexistant
            'category_id' => $category->id
        ]);

        // Act
        $result = ProductTransformer::transformForDisplay($product);

        // Assert
        $this->assertEmpty($result['productableDetail']);
        $this->assertArrayHasKey('typeConfig', $result);
    }
}