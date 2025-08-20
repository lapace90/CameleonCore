<?php
// tests/Unit/ProductTransformerTest.php
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
            'duration' => 120
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

        // Act
        $result = ProductTransformer::transformForList(collect([$product]));

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        
        $transformed = $result[0];
        $this->assertEquals('Test Product', $transformed['name']);
        $this->assertEquals(99.99, $transformed['price']);
        $this->assertEquals('99,99 €', $transformed['formatted_price']);
        $this->assertEquals('Actif', $transformed['status_label']);
        $this->assertEquals('status-active', $transformed['status_class']);
        $this->assertEquals('Test Category', $transformed['category']['name']);
        $this->assertArrayHasKey('typeConfig', $transformed);
    }

    /** @test */
    public function transforms_product_for_detail()
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

        $product->load(['category', 'productable', 'globalTags', 'options']);

        // Act
        $result = ProductTransformer::transformForDetail($product);

        // Assert
        $this->assertArrayHasKey('productable_detail', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('detail_fields', $result);
        
        $this->assertEquals('John Doe', $result['productable_detail']['guide']);
        $this->assertEquals(120, $result['productable_detail']['duration']);
    }

    /** @test */
    public function formats_field_values_correctly()
    {
        // Arrange
        $activity = Activity::factory()->create([
            'duration' => 90,
            'max_people' => 8,
            'difficulty_level' => 'easy'
        ]);

        // Act
        $formatted = ProductTransformer::formatFieldValue($activity, 'duration', 'duration');

        // Assert
        $this->assertEquals('90 min', $formatted);
    }
}