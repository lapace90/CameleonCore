<?php

namespace App\Observers;

use App\Models\Tag;
use App\Models\Menu;

class MenuObserver
{
    /**
     * Handle the Menu "created" event.
     */
    public function created(Menu $menu): void
    {
        // Calculer les tags lors de la création si des plats sont déjà associés
        $this->updateMenuTags($menu);
    }

    /**
     * Handle the Menu "updated" event.
     */
    public function updated(Menu $menu): void
    {
        // Recalculer les tags du menu quand ses relations changent
        $this->updateMenuTags($menu);
    }

    /**
     * Handle the Menu "saving" event - avant la sauvegarde
     */
    public function saving(Menu $menu): void
    {
        // Vous pouvez ajouter des validations ici
    }

    /**
     * Handle the Menu "deleted" event.
     */
    public function deleted(Menu $menu): void
    {
        // Nettoyer les relations de tags si nécessaire
        if (method_exists($menu, 'specificTags')) {
            $menu->specificTags()->detach();
        }
    }

    /**
     * Logique principale : calcul et mise à jour des tags du menu
     */
    private function updateMenuTags(Menu $menu): void
    {
        // S'assurer que les plats sont chargés
        if (!$menu->relationLoaded('dishes')) {
            $menu->load('dishes.ingredients');
        }

        $menuTags = $this->calculateMenuTags($menu);
        
        if (!empty($menuTags)) {
            // Créer ou récupérer les tags
            $tagIds = Tag::whereIn('name', $menuTags)
                ->where('is_global', false)
                ->pluck('id');
            
            // Synchroniser avec le menu (si vous avez la relation)
            if (method_exists($menu, 'specificTags')) {
                $menu->specificTags()->sync($tagIds);
            }
            
            // Mettre à jour le produit associé pour forcer le cache
            $menu->product?->touch();
        }
    }

    /**
     * Calculer les tags d'un menu basés sur ses plats
     */
    private function calculateMenuTags(Menu $menu): array
    {
        $menuTags = [];
        $dishes = $menu->dishes;
        
        if ($dishes->isEmpty()) {
            return $menuTags;
        }

        // Vérifier si TOUS les plats du menu sont végétariens/vegans
        $allVegetarian = true;
        $allVegan = true;
        $hasSpicy = false;
        $allGlutenFree = true;
        $allLactoseFree = true;
        $allNutFree = true;

        foreach ($dishes as $dish) {
            $ingredients = $dish->ingredients;
            
            // Si un plat n'est pas végétarien, le menu ne l'est pas
            if (!$ingredients->every('is_vegetarian')) {
                $allVegetarian = false;
                $allVegan = false;
            }
            
            // Si un plat n'est pas végan, le menu ne l'est pas
            if (!$ingredients->every('is_vegan')) {
                $allVegan = false;
            }
            
            // Si un plat est épicé, le menu l'est
            if ($ingredients->contains('is_spicy', true)) {
                $hasSpicy = true;
            }
            
            // Si un plat contient du gluten, le menu en contient
            if (!$ingredients->every('is_gluten_free', true)) {
                $allGlutenFree = false;
            }
            
            // Si un plat contient du lactose, le menu en contient
            if (!$ingredients->every('is_lactose_free', true)) {
                $allLactoseFree = false;
            }
            
            // Si un plat contient des noix, le menu en contient
            if (!$ingredients->every('is_nut_free', true)) {
                $allNutFree = false;
            }
        }

        // Assigner les tags selon les résultats
        if ($allVegan) {
            $menuTags[] = 'vegan';
            $menuTags[] = 'vegetarian'; // Vegan implique végétarien
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