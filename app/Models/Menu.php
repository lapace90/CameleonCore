<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ApiResource]

class Menu extends Model
{
    use HasFactory;

    public function product()
    {
        return $this->morphOne(Product::class, 'productable');
    }

    public function dishes()
    {
        return $this->belongsToMany(Dish::class);
    }

    // ✅ AJOUT : Tags spécifiques pour les menus
    public function specificTags()
    {
        return $this->morphToMany(Tag::class, 'taggable')->where('is_global', false);
    }

    // ✅ AJOUT : Méthode pour calculer les tags (utilisée par l'observer)
    public function calculateSpecificTags()
    {
        $tags = [];
        $dishes = $this->dishes;
        
        if ($dishes->isEmpty()) {
            return $tags;
        }

        // Logique de calcul des tags du menu
        $allVegetarian = true;
        $allVegan = true;
        $hasSpicy = false;
        $allGlutenFree = true;
        $allLactoseFree = true;
        $allNutFree = true;

        foreach ($dishes as $dish) {
            $ingredients = $dish->ingredients;
            
            if (!$ingredients->every('is_vegetarian', true)) {
                $allVegetarian = false;
                $allVegan = false;
            }
            
            if (!$ingredients->every('is_vegan', true)) {
                $allVegan = false;
            }
            
            if ($ingredients->contains('is_spicy', true)) {
                $hasSpicy = true;
            }
            
            if (!$ingredients->every('is_gluten_free', true)) {
                $allGlutenFree = false;
            }
            
            if (!$ingredients->every('is_lactose_free', true)) {
                $allLactoseFree = false;
            }
            
            if (!$ingredients->every('is_nut_free', true)) {
                $allNutFree = false;
            }
        }

        // Assigner les tags selon les résultats
        if ($allVegan) {
            $tags[] = 'vegan';
            $tags[] = 'vegetarian'; // Vegan implique végétarien
        } elseif ($allVegetarian) {
            $tags[] = 'vegetarian';
        }

        if ($hasSpicy) {
            $tags[] = 'spicy';
        }

        if ($allGlutenFree) {
            $tags[] = 'gluten_free';
        }

        if ($allLactoseFree) {
            $tags[] = 'lactose_free';
        }

        if ($allNutFree) {
            $tags[] = 'nut_free';
        }

        return array_unique($tags);
    }

    // ✅ AJOUT : Méthode manuelle pour forcer la mise à jour des tags
    public function updateTags()
    {
        $tags = $this->calculateSpecificTags();
        
        $this->specificTags()->sync(
            Tag::whereIn('name', $tags)->where('is_global', false)->pluck('id')
        );
    }
}