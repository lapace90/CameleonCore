<?php

namespace App\Observers;

use App\Models\Tag;

class TagObserver
{
    /**
     * Handle the Tag "created" event.
     */
    public function created(Tag $tag): void
    {
        //
    }

    /**
     * Handle the Tag "creating" event.
     */
    public function creating(Tag $tag)
    {
        // Empêche les doublons de noms
        if (Tag::where('name', $tag->name)->exists()) {
            abort(422, "Le tag '{$tag->name}' existe déjà");
        }
    }

    /**
     * Handle the Tag "updated" event.
     */
    public function updated(Tag $tag): void
    {
        //
    }

    /**
     * Handle the Tag "deleted" event.
     */
    public function deleted(Tag $tag): void
    {
        //
    }

    /**
     * Handle the Tag "restored" event.
     */
    public function restored(Tag $tag): void
    {
        //
    }

    /**
     * Handle the Tag "force deleted" event.
     */
    public function forceDeleted(Tag $tag): void
    {
        //
    }
}
