<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Models
use App\Models\{
    Ingredient, 
    Tag, 
    Dish, 
    Menu, 
    Activity, 
    Room,
    Reservation,
    Review 
};

// Observers
use App\Observers\{
    IngredientObserver,
    TagObserver,
    DishObserver,
    MenuObserver,
    ActivityObserver,
    RoomObserver,
    ReservationObserver,
    ReviewObserver
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
 	\Illuminate\Support\Facades\URL::forceScheme('https');
	
	// 🔍 Observers existants pour le système de tags automatiques
        Ingredient::observe(IngredientObserver::class); Tag::observe(TagObserver::class); 
        Dish::observe(DishObserver::class); Menu::observe(MenuObserver::class); 
        Activity::observe(ActivityObserver::class); Room::observe(RoomObserver::class); 
        Review::observe(ReviewObserver::class);

        // 🆕 Observer pour la création automatique des factures
        Reservation::observe(ReservationObserver::class);
    }
}
