<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use App\State\ProductCollectionProvider;
use App\State\ProductItemProvider;
use App\State\ProductProcessor;
use App\Data\ProductOutputData;
use App\Data\ProductData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\Option;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tag;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/products',
            provider: ProductCollectionProvider::class,
            output: ProductOutputData::class
        ),
        new GetCollection(),
        new Get(
            uriTemplate: '/products/{id}',
            provider: ProductItemProvider::class,
            output: ProductOutputData::class
        ),
        new Post(
            uriTemplate: '/products',
            processor: ProductProcessor::class,
            output: ProductOutputData::class,
            input: ProductData::class,
            deserialize: false
        ),
        new Patch(
            uriTemplate: '/products/{id}',
            processor: ProductProcessor::class,
            output: ProductOutputData::class,
            input: ProductData::class,
            deserialize: false
        ),
        new Delete(
            uriTemplate: '/products/{id}'
        )
    ]
)]
#[QueryParameter(key: 'type', filter: EqualsFilter::class, property: 'productable_type')]
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'category_id',
        'status',
        'productable_id',
        'productable_type',
        'is_draft'
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_draft' => 'boolean',
        'price' => 'float'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'product_reservation')->withTimestamps();
    }

    // Empêcher la dénormalisation automatique de la relation et de son type
    #[ApiProperty(writable: false)]
    protected $productable_type;

    #[ApiProperty(writable: false)]
    public function productable()
    {
        return $this->morphTo();
    }

    public function globalTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag')->withTimestamps();
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'res_product_option')->withTimestamps();
    }

    // Méthodes helper pour les types de produits
    public function isActivity(): bool
    {
        return $this->productable_type === 'App\\Models\\Activity';
    }

    public function isRoom(): bool
    {
        return $this->productable_type === 'App\\Models\\Room';
    }

    public function isMenu(): bool
    {
        return $this->productable_type === 'App\\Models\\Menu';
    }

    public function isDish(): bool
    {
        return $this->productable_type === 'App\\Models\\Dish';
    }

    public function isIngredient(): bool
    {
        return $this->productable_type === 'App\\Models\\Ingredient';
    }

    // Scope pour filtrer par type
    public function scopeOfType($query, string $type)
    {
        return $query->where('productable_type', $type);
    }

    public function scopeActivities($query)
    {
        return $query->ofType('App\\Models\\Activity');
    }

    public function scopeRooms($query)
    {
        return $query->ofType('App\\Models\\Room');
    }

    public function scopeMenus($query)
    {
        return $query->ofType('App\\Models\\Menu');
    }

    public function scopeDishes($query)
    {
        return $query->ofType('App\\Models\\Dish');
    }

    public function scopeIngredients($query)
    {
        return $query->ofType('App\\Models\\Ingredient');
    }

    // Scope pour les produits actifs
    public function scopeActive($query)
    {
        return $query->where('status', true)->where('is_draft', false);
    }

    // Scope pour les brouillons
    public function scopeDrafts($query)
    {
        return $query->where('is_draft', true);
    }
}
