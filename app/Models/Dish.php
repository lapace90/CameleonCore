<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ApiPlatform\Metadata\ApiResource;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\HasFactory;


#[ApiResource]

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

    // Exemple de méthode de calcul des tags
    public function calculateSpecificTags()
    {
        $tags = [];

        $ingredients = $this->ingredients;

        // Vegan implique automatiquement végétarien
        if ($ingredients->every('is_vegan', true)) {
            $tags[] = 'vegan';
            $tags[] = 'vegetarian';
        } elseif ($ingredients->every('is_vegetarian', true)) {
            $tags[] = 'vegetarian';
        }

        // Autres tags
        $checks = [
            'is_spicy' => 'spicy',
            'is_gluten_free' => 'gluten_free',
            'is_lactose_free' => 'lactose_free',
            'is_nut_free' => 'nut_free'
        ];

        foreach ($checks as $attribute => $tag) {
            if ($ingredients->contains($attribute, true)) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class);
    }
}
