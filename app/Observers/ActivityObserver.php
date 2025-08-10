<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Tag;
use Illuminate\Support\Str;

class ActivityObserver
{
    /**
     * Handle the Activity "created" event.
     */
    public function created(Activity $activity): void
    {
        $this->updateActivityTags($activity);
    }

    /**
     * Handle the Activity "updated" event.
     */
    public function updated(Activity $activity): void
    {
        $this->updateActivityTags($activity);
    }

    /**
     * Handle the Activity "deleted" event.
     */
    public function deleted(Activity $activity): void
    {
        // Nettoyer les tags spécifiques
        $activity->specificTags()->detach();
    }

    /**
     * Handle the Activity "restored" event.
     */
    public function restored(Activity $activity): void
    {
        $this->updateActivityTags($activity);
    }

    /**
     * Mettre à jour les tags spécifiques de l'activité
     */
    private function updateActivityTags(Activity $activity): void
    {
        // Utiliser la méthode du modèle (renommée pour cohérence)
        $tags = $activity->calculateSpecificTags();
        
        if (!empty($tags)) {
            // Créer les tags s'ils n'existent pas
            $this->ensureTagsExist($tags);
            
            // Synchroniser avec l'activité
            $tagIds = Tag::whereIn('name', $tags)->where('is_global', false)->pluck('id');
            $activity->specificTags()->sync($tagIds);
        } else {
            // Aucun tag calculé, vider les relations
            $activity->specificTags()->detach();
        }
        
        // Forcer une mise à jour du cache du produit
        $activity->product?->touch();
    }

    /**
     * S'assurer que tous les tags existent en base
     */
    private function ensureTagsExist(array $tagNames): void
    {
        foreach ($tagNames as $tagName) {
            Tag::firstOrCreate(
                ['name' => $tagName, 'is_global' => false],
                [
                    'slug' => Str::slug($tagName),
                    'description' => "Tag auto-généré pour {$tagName}",
                    'icon' => $this->getIconForTag($tagName)
                ]
            );
        }
    }

    /**
     * Obtenir une icône par défaut selon le type de tag
     */
    private function getIconForTag(string $tagName): string
    {
        $icons = [
            'extreme' => 'fas fa-mountain',
            'easy' => 'fas fa-smile',
            'medium' => 'fas fa-walking',
            'hard' => 'fas fa-fire',
            'outdoor' => 'fas fa-tree',
            'indoor' => 'fas fa-home',
            'water' => 'fas fa-water',
            'climbing' => 'fas fa-mountain',
            'hiking' => 'fas fa-hiking'
        ];

        return $icons[$tagName] ?? 'fas fa-tag';
    }
}