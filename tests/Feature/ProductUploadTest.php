<?php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        // Arrange
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        // Act
        $response = $this->postJson('/api/products/upload-image', [
            'image' => $file
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'image' => [
                    'path',
                    'url',
                    'size',
                    'mime_type'
                ]
            ]);

        $this->assertTrue(Storage::disk('public')->exists($response->json('image.path')));
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
