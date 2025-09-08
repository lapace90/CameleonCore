<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use Tests\TestCase;

class ProductUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function can_upload_product_image()
    {
        // CORRECTION: Vérifier GD ou mocker
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('Extension GD non disponible');
        }

        // Ou utiliser un mock à la place
        Storage::fake('public');

        // Créer un fichier texte au lieu d'une image
        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $category = Category::factory()->create();
        $activity = Activity::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        // Act
        $response = $this->postJson("/api/products/{$product->id}/upload-image", [
            'image' => $file
        ]);

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function validates_image_upload()
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        // Act
        $response = $this->postJson('/api/products/upload-image', [
            'image' => $file
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function can_update_product_image()
    {
        // Arrange
        $activity = Activity::factory()->create();
        $product = Product::factory()->create([
            'productable_type' => Activity::class,
            'productable_id' => $activity->id
        ]);

        $file = UploadedFile::fake()->image('new-product.jpg');

        // Act
        $response = $this->postJson('/api/products/upload-image', [
            'image' => $file,
            'product_id' => $product->id
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson(['image' => ['product_updated' => true]]);

        $product->fresh();
        $this->assertNotNull($product->image);
    }
}
