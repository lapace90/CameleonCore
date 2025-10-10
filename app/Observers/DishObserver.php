<?php

namespace App\Observers;

use App\Models\Dish;
use App\Models\Tag;

class DishObserver
{
    /**
     * Handle the Dish "created" event.
     */
    public function created(Dish $dish): void
    {
        // Calculer les tags lors de la création
        $this->updateDishTags($dish);
    }

    /**
     * Handle the Dish "updated" event.
     */
    public function updated(Dish $dish): void
    {
        // 1. Mettre à jour les tags du plat
        $this->updateDishTags($dish);
        
        // 2.  Propager aux menus qui contiennent ce plat
        $this->propagateToMenus($dish);
    }

    /**
     * Handle the Dish "deleted" event.
     */
    public function deleted(Dish $dish): void
    {
        // Propager la suppression aux menus
        $this->propagateToMenus($dish);
    }

    /**
     * Handle the Dish "restored" event.
     */
    public function restored(Dish $dish): void
    {
        $this->updateDishTags($dish);
        $this->propagateToMenus($dish);
    }

    /**
     * Handle the Dish "force deleted" event.
     */
    public function forceDeleted(Dish $dish): void
    {
        // Les relations sont automatiquement nettoyées par Laravel
    }

    /**
     * Mettre à jour les tags spécifiques du plat
     */
    private function updateDishTags(Dish $dish): void
    {
        // Votre logique existante
        $tags = $dish->calculateSpecificTags();
        $tagIds = Tag::whereIn('name', $tags)->where('is_global', false)->pluck('id');
        $dish->specificTags()->sync($tagIds);
        
        // Forcer une mise à jour du cache du produit
        $dish->product?->touch();
    }

    /**
     *  Propager les changements aux menus qui contiennent ce plat
     */
    private function propagateToMenus(Dish $dish): void
    {
        // Récupérer tous les menus qui contiennent ce plat
        $menus = $dish->menus()->get();
        
        foreach ($menus as $menu) {
            // Déclencher une mise à jour du menu (sans créer de boucle infinie)
            $this->updateMenuTagsDirectly($menu);
        }
    }

    /**
     * Mettre à jour directement les tags d'un menu (sans passer par l'observer)
     */
    private function updateMenuTagsDirectly($menu): void
    {
        // Charger les relations nécessaires
        $menu->load('dishes.ingredients');
        
        $menuTags = $this->calculateMenuTags($menu);
        
        if (!empty($menuTags)) {
            $tagIds = Tag::whereIn('name', $menuTags)
                ->where('is_global', false)
                ->pluck('id');
            
            // Synchroniser les tags du menu (si la relation existe)
            if (method_exists($menu, 'specificTags')) {
                $menu->specificTags()->sync($tagIds);
            }
            
            // Mettre à jour le produit du menu
            $menu->product?->touch();
        }
    }

    /**
     * Calculer les tags d'un menu (même logique que MenuObserver)
     */
    private function calculateMenuTags($menu): array
    {
        $menuTags = [];
        $dishes = $menu->dishes;
        
        if ($dishes->isEmpty()) {
            return $menuTags;
        }

        // Logique de calcul (même que dans MenuObserver)
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

        // Assigner les tags
        if ($allVegan) {
            $menuTags[] = 'vegan';
            $menuTags[] = 'vegetarian';
        } elseif ($allVegetarian) {
            $menuTags[] = 'vegetarian';
        }

        if ($hasSpicy) {
            $menuTags[] = 'spicy';
        }

        if ($allGlutenFree) {
            $menuTags[] = 'gluten_free';
        }

        if ($allLactoseFree) {
            $menuTags[] = 'lactose_free';
        }

        if ($allNutFree) {
            $menuTags[] = 'nut_free';
        }

        return array_unique($menuTags);
    }
}