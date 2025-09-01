<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Models
use App\Models\{Ingredient, Tag, Dish, Menu, Activity, Room};

// Observers
use App\Observers\{
    IngredientObserver,
    TagObserver,
    DishObserver,
    MenuObserver,
    ActivityObserver,
    RoomObserver
};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 🔍 Observers pour le système de tags automatiques
        Ingredient::observe(IngredientObserver::class);
        Tag::observe(TagObserver::class);
        Dish::observe(DishObserver::class);
        Menu::observe(MenuObserver::class);
        Activity::observe(ActivityObserver::class);
        Room::observe(RoomObserver::class);
    }
}