<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use App\State\CategoryCollectionProvider;
use App\State\CategoryItemProvider;
use App\State\CategoryProcessor;
use App\Data\CategoryOutputData;
use App\Data\CategoryData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/categories',
            provider: CategoryCollectionProvider::class,
            output: CategoryOutputData::class,
            parameters: [
                'type' => new QueryParameter(
                    filter: EqualsFilter::class,
                    description: 'Filter by category type'
                ),
                'search' => new QueryParameter(
                    description: 'Search in name and description'
                )
            ]
        ),
        new Get(
            uriTemplate: '/categories/{id}',
            provider: CategoryItemProvider::class,
            output: CategoryOutputData::class
        ),
        new Post(
            uriTemplate: '/categories',
            processor: CategoryProcessor::class,
            output: CategoryOutputData::class,
            input: CategoryData::class
        ),
        new Patch(
            uriTemplate: '/categories/{id}',
            processor: CategoryProcessor::class,
            output: CategoryOutputData::class,
            input: CategoryData::class
        ),
        new Delete(
            uriTemplate: '/categories/{id}',
            processor: CategoryProcessor::class
        )
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 50
)]
class Category extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'type',
        'name',
        'photo',
        'description',
    ];

    // Constantes pour les types
    public const TYPES = [
        'Activity' => 'Activités',
        'Menu' => 'Menus', 
        'Dish' => 'Plats',
        'Room' => 'Hébergements',
        'Ingredient' => 'Ingrédients'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    public function getTypeLabelAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getProductCountAttribute()
    {
        return $this->products()->count();
    }

    public static function rules()
    {
        return [
            'type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function messages()
    {
        return [
            'type.required' => 'Le type de catégorie est requis',
            'type.in' => 'Le type de catégorie doit être : ' . implode(', ', array_keys(self::TYPES)),
            'name.required' => 'Le nom de la catégorie est requis',
            'name.max' => 'Le nom ne peut pas dépasser 50 caractères',
            'description.max' => 'La description ne peut pas dépasser 500 caractères',
        ];
    }

    // Helper pour obtenir les catégories par type
    public static function getByType(string $type)
    {
        return self::where('type', $type)->orderBy('name')->get();
    }

    // Helper pour obtenir tous les types disponibles
    public static function getAvailableTypes()
    {
        return self::TYPES;
    }

    // Helper pour obtenir les statistiques
    public static function getStats()
    {
        $stats = [];
        foreach (self::TYPES as $type => $label) {
            $stats[$type] = [
                'label' => $label,
                'count' => self::where('type', $type)->count(),
                'products_count' => self::where('type', $type)
                    ->withCount('products')
                    ->get()
                    ->sum('products_count')
            ];
        }
        return $stats;
    }
}