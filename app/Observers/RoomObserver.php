<?php

namespace App\Observers;

use App\Models\Room;
use App\Models\Tag;
use Illuminate\Support\Str;

class RoomObserver
{
    /**
     * Handle the Room "created" event.
     */
    public function created(Room $room): void
    {
        $this->updateRoomTags($room);
    }

    /**
     * Handle the Room "updated" event.
     */
    public function updated(Room $room): void
    {
        $this->updateRoomTags($room);
    }

    /**
     * Handle the Room "deleted" event.
     */
    public function deleted(Room $room): void
    {
        // Nettoyer les tags spécifiques
        $room->specificTags()->detach();
    }

    /**
     * Handle the Room "restored" event.
     */
    public function restored(Room $room): void
    {
        $this->updateRoomTags($room);
    }

    /**
     * Mettre à jour les tags spécifiques de la chambre
     */
    private function updateRoomTags(Room $room): void
    {
        $tags = $room->calculateSpecificTags();
        
        if (!empty($tags)) {
            // Créer les tags s'ils n'existent pas
            $this->ensureTagsExist($tags);
            
            // Synchroniser avec la chambre
            $tagIds = Tag::whereIn('name', $tags)->where('is_global', false)->pluck('id');
            $room->specificTags()->sync($tagIds);
        } else {
            // Aucun tag calculé, vider les relations
            $room->specificTags()->detach();
        }
        
        // Forcer une mise à jour du cache du produit
        $room->product?->touch();
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
            'solo' => 'fas fa-user',
            'couple' => 'fas fa-heart',
            'family' => 'fas fa-users',
            'group' => 'fas fa-user-friends',
            'available' => 'fas fa-check-circle',
            'unavailable' => 'fas fa-times-circle',
            'luxury' => 'fas fa-crown',
            'standard' => 'fas fa-bed',
            'economic' => 'fas fa-dollar-sign'
        ];

        return $icons[$tagName] ?? 'fas fa-home';
    }
}