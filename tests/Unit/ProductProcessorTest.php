<?php

namespace Tests\Unit;

use App\State\ProductProcessor;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ReflectionClass;

class ProductProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function callNormalize(array $payload): array
    {
        $processor = new ProductProcessor();
        $reflector = new ReflectionClass($processor);
        $method = $reflector->getMethod('normalizePayloadFromFrontend');
        $method->setAccessible(true);

        return $method->invoke($processor, $payload);
    }

    /** @test */
    public function it_sets_missing_fields_to_null()
    {
        $normalized = $this->callNormalize(['name' => 'Updated']);

        $this->assertSame('Updated', $normalized['name']);
        $this->assertNull($normalized['description']);
        $this->assertNull($normalized['price']);
        $this->assertNull($normalized['status']);
        $this->assertNull($normalized['is_draft']);
        $this->assertNull($normalized['category_id']);
        $this->assertNull($normalized['image']);
        $this->assertNull($normalized['productable_type']);
        $this->assertNull($normalized['productable']);
        $this->assertNull($normalized['relations']);
        $this->assertNull($normalized['tags']);
        $this->assertNull($normalized['options']);
    }

    /** @test */
    public function patch_updates_only_provided_fields()
    {
        // CORRECTION: S'assurer qu'une catégorie existe
        $category = Category::factory()->create();
        
        $product = Product::factory()->create([
            'name' => 'Original',
            'description' => 'Original description',
            'price' => 10.0,
            'category_id' => $category->id, // CORRECTION: Ajouter category_id requis
        ]);

        $normalized = $this->callNormalize(['name' => 'Changed']);
        $updates = array_filter($normalized, fn($v) => $v !== null);
        $product->update($updates);
        $product->refresh();

        $this->assertSame('Changed', $product->name);
        $this->assertSame('Original description', $product->description);
        $this->assertEquals(10.0, $product->price);
    }
}