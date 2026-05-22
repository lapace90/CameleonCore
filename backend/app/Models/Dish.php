<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ApiPlatform\Metadata\ApiResource;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;


#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/dishes'),
        new Get(uriTemplate: '/dishes/{id}')
    ]
)]

class Dish extends Model
{
    use HasFactory;
    // Charger toujours les ingrédients avec le plat pour les calculs
    protected $with = ['ingredients'];

    public function product()
    {
        return $this->morphOne(Product::class, 'productable');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class);
    }

    // Tags spécifiques (ex: "épicé")
    public function specificTags()
    {
        return $this->morphToMany(Tag::class, 'taggable')->where('is_global', false);
    }

    public function updateTags()
    {
        // Récupère les tags calculés dynamiquement (votre logique métier)
        $tags = $this->calculateSpecificTags();

        // Synchronise les tags en base de données
        $this->specificTags()->sync(
            Tag::whereIn('name', $tags)->pluck('id')
        );
    }

    public function calculateSpecificTags()
    {
        $tags = [];

        $ingredients = $this->ingredients;

        // Vegan => tutti gli ingredienti vegani
        if ($ingredients->every('is_vegan')) {
            $tags[] = 'vegan';
            $tags[] = 'vegetarian'; // Vegan implica vegetariano
        } elseif ($ingredients->every('is_vegetarian')) {
            $tags[] = 'vegetarian';
        }

        // Tag per almeno un ingrediente
        if ($ingredients->contains('is_spicy', true)) {
            $tags[] = 'spicy';
        }

        // Tag per tutti gli ingredienti
        $everyChecks = [
            'is_gluten_free' => 'gluten_free',
            'is_lactose_free' => 'lactose_free',
            'is_nut_free' => 'nut_free'
        ];

        foreach ($everyChecks as $attribute => $tag) {
            if ($ingredients->every($attribute)) {
                $tags[] = $tag;
            }
        }

        return array_unique($tags);
    }


    public function menus()
    {
        return $this->belongsToMany(Menu::class);
    }
}
