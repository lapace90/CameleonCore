<?php

namespace App\Observers;

use App\Models\Ingredient;
use App\Models\Tag;

class IngredientObserver
{
    /**
     * Handle the Ingredient "created" event.
     */
    public function created(Ingredient $ingredient): void
    {
        //
    }

    /**
     * Handle the Ingredient "updated" event.
     */
    public function updated(Ingredient $ingredient)
    {
        if ($ingredient->wasChanged(['is_spicy', /* autres champs */])) {
            $ingredient->dishes->each(function ($dish) {
                // Met à jour les tags spécifiques du Dish
                $tags = $dish->calculateSpecificTags();
                $tagIds = Tag::whereIn('name', $tags)->where('is_global', false)->pluck('id');
                $dish->specificTags()->sync($tagIds);
                
                // Optionnel : propager aux produits associés
                $dish->product?->touch(); // Forcer une mise à jour du cache si nécessaire
            });
        }
    }

    /**
     * Handle the Ingredient "deleted" event.
     */
    public function deleted(Ingredient $ingredient): void
    {
        //
    }

    /**
     * Handle the Ingredient "restored" event.
     */
    public function restored(Ingredient $ingredient): void
    {
        //
    }

    /**
     * Handle the Ingredient "force deleted" event.
     */
    public function forceDeleted(Ingredient $ingredient): void
    {
        //
    }
}
