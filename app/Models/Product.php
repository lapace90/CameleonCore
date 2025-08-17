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
use App\State\ProductCollectionProvider;
use App\State\ProductItemProvider;
use App\State\ProductProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\Option;
use App\Models\Tag;
use Illuminate\Support\Facades\Log;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/products',
            provider: ProductCollectionProvider::class
        ),
        new Get(
            uriTemplate: '/products/{id}',
            provider: ProductItemProvider::class
        ),
        new Post(
            uriTemplate: '/products',
            processor: ProductProcessor::class
        ),
        new Patch(
            uriTemplate: '/products/{id}',
            processor: ProductProcessor::class
        ),
        new Delete(
            uriTemplate: '/products/{id}'
        )
    ]
)]
#[QueryParameter(key: 'type', filter: EqualsFilter::class, property: 'productable_type')]
class Product extends Model
{
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
        'price' => 'decimal:2'
    ];

    protected $appends = [
        'formatted_price',
        'status_label',
        'status_class',
        'type_config',
        'productable_detail',
        'relations_data'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class)->withTimestamps();
    }

    public function productable()
    {
        return $this->morphTo();
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'res_product_option')->withTimestamps();
    }

    public function globalTags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag')
            ->where('is_global', true)
            ->withTimestamps();
    }

    // Scopes utiles
    public function scopeActive($query)
    {
        return $query->where('status', true)->where('is_draft', false);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    public function scopeWithType($query, $type)
    {
        return $query->where('productable_type', $type);
    }

    // Accesseurs pour simplifier le frontend
    public function getImageAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Si c'est déjà une URL complète, la retourner telle quelle
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Sinon, construire l'URL avec asset
        return asset('storage/' . $value);
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2, ',', ' ') . ' €';
    }

    public function getStatusLabelAttribute()
    {
        if ($this->is_draft) return 'Brouillon';
        return $this->status ? 'Actif' : 'Inactif';
    }

    public function getStatusClassAttribute()
    {
        if ($this->is_draft) return 'status-draft';
        return $this->status ? 'status-active' : 'status-inactive';
    }

    public function getTypeConfigAttribute()
    {
        $configs = [
            'App\\Models\\Activity' => [
                'label' => 'Activités',
                'singular' => 'Activité',
                'icon' => 'fas fa-hiking',
                'color' => '#3b82f6'
            ],
            'App\\Models\\Menu' => [
                'label' => 'Menus',
                'singular' => 'Menu',
                'icon' => 'fas fa-utensils',
                'color' => '#10b981'
            ],
            'App\\Models\\Dish' => [
                'label' => 'Plats',
                'singular' => 'Plat',
                'icon' => 'fas fa-drumstick-bite',
                'color' => '#f97316'
            ],
            'App\\Models\\Ingredient' => [
                'label' => 'Ingrédients',
                'singular' => 'Ingrédient',
                'icon' => 'fas fa-seedling',
                'color' => '#22c55e'
            ],
            'App\\Models\\Room' => [
                'label' => 'Hébergements',
                'singular' => 'Hébergement',
                'icon' => 'fas fa-bed',
                'color' => '#f59e0b'
            ]
        ];

        return $configs[$this->productable_type] ?? $configs['App\\Models\\Activity'];
    }

    public function getProductableDetailAttribute()
    {
        if (!$this->productable) {
            return null;
        }

        $data = $this->productable->toArray();

        // Ajouter les relations selon le type
        switch ($this->productable_type) {
            case 'App\\Models\\Menu':
                if ($this->productable->relationLoaded('dishes')) {
                    $data['dishes'] = $this->productable->dishes->map(function ($dish) {
                        return [
                            'id' => $dish->id,
                            'name' => $dish->product->name ?? 'N/A',
                            'description' => $dish->product->description ?? '',
                            'price' => $dish->product->price ?? 0,
                            'formatted_price' => number_format($dish->product->price ?? 0, 2, ',', ' ') . ' €',
                            'image' => $dish->product->image ?? null,
                            'product_id' => $dish->product->id ?? null
                        ];
                    });
                }
                break;

            case 'App\\Models\\Dish':
                if ($this->productable->relationLoaded('ingredients')) {
                    $data['ingredients'] = $this->productable->ingredients->map(function ($ingredient) {
                        return [
                            'id' => $ingredient->id,
                            'name' => $ingredient->product->name ?? 'N/A',
                            'stock' => $ingredient->stock ?? 0,
                            'product_id' => $ingredient->product->id ?? null, // ✅ Maintenant ça marche !
                            'dietary_properties' => [
                                'is_vegetarian' => $ingredient->is_vegetarian ?? false,
                                'is_vegan' => $ingredient->is_vegan ?? false,
                                'is_spicy' => $ingredient->is_spicy ?? false,
                                'is_gluten_free' => $ingredient->is_gluten_free ?? false,
                                'is_lactose_free' => $ingredient->is_lactose_free ?? false,
                                'is_nut_free' => $ingredient->is_nut_free ?? false
                            ]
                        ];
                    });
                }
                break;
        }

        return $data;
    }


    public function getRelationsDataAttribute()
    {
        $relations = [];

        try {
            if (!$this->productable) {
                return $relations;
            }

            switch ($this->productable_type) {
                case 'App\\Models\\Menu':
                    if ($this->productable->relationLoaded('dishes')) {
                        $relations['dishes'] = $this->productable->dishes->map(function ($dish) {
                            return [
                                'id' => $dish->id,
                                'product_id' => $dish->product->id ?? null,
                                'name' => $dish->product->name ?? 'N/A',
                                'stock' => null,
                                'dietary_properties' => []
                            ];
                        });
                    }
                    break;

                case 'App\\Models\\Dish':
                    if ($this->productable->relationLoaded('ingredients')) {
                        $relations['ingredients'] = $this->productable->ingredients->map(function ($ingredient) {
                            return [
                                'id' => $ingredient->id,
                                'product_id' => $ingredient->product->id ?? null,
                                'name' => $ingredient->product->name ?? 'N/A',
                                'stock' => $ingredient->stock ?? 0,
                                'dietary_properties' => [
                                    'is_vegetarian' => $ingredient->is_vegetarian ?? false,
                                    'is_vegan' => $ingredient->is_vegan ?? false,
                                    'is_spicy' => $ingredient->is_spicy ?? false,
                                    'is_gluten_free' => $ingredient->is_gluten_free ?? false,
                                    'is_lactose_free' => $ingredient->is_lactose_free ?? false,
                                    'is_nut_free' => $ingredient->is_nut_free ?? false
                                ]
                            ];
                        });
                    }
                    break;

                case 'App\\Models\\Ingredient':
                    if ($this->productable->relationLoaded('dishes')) {
                        $relations['dishes'] = $this->productable->dishes->map(function ($dish) {
                            return [
                                'id' => $dish->id,
                                'product_id' => $dish->product->id ?? null,
                                'name' => $dish->product->name ?? 'N/A',
                                'stock' => null,
                                'dietary_properties' => []
                            ];
                        });
                    }
                    break;

                case 'App\\Models\\Activity':
                case 'App\\Models\\Room':
                    // Ces types n'ont pas de relations spéciales
                    break;
            }
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide plutôt que de planter
            Log::warning("Erreur dans getRelationsDataAttribute pour le produit {$this->id}: " . $e->getMessage());
            return [];
        }

        return $relations;
    }

    public function specificTags()
{
    // Créer une relation vide par défaut
    $emptyRelation = $this->morphToMany(Tag::class, 'taggable', 'taggables')
        ->where('tags.id', '=', -1); // Condition impossible = relation vide
    
    // Si pas de productable, retourner la relation vide
    if (!$this->productable || !method_exists($this->productable, 'specificTags')) {
        return $emptyRelation;
    }
    
    // Retourner la vraie relation du productable
    return $this->productable->specificTags();
}
}
