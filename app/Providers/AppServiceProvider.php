<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Ingredient;
use App\Observers\IngredientObserver;
use App\Models\Tag;
use App\Observers\TagObserver;
use App\Models\Dish;
use App\State\ProductCollectionProvider;
use App\State\ProductProcessor;
use App\Observers\DishObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //  $this->app->bind(ProductCollectionProvider::class, function () {
        //     return new ProductCollectionProvider();
        // });

        // $this->app->bind(ProductProcessor::class, function () {
        //     return new ProductProcessor();
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Ingredient::observe(IngredientObserver::class);
        Tag::observe(TagObserver::class);
        Dish::observe(DishObserver::class);

    }
}
