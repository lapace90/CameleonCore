<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DishController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('dashboard')->group(function () {

      Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
        Route::get('/search', [ProductController::class, 'search'])->name('products.search');
        Route::get('/stats', [ProductController::class, 'stats'])->name('products.stats');
        Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::put('/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/{product}', [ProductController::class, 'patch'])->name('products.patch');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });
 // Activity routes
    Route::prefix('activity')->group(function () {
        Route::get('/', [ActivityController::class, 'activity'])->name('dashboard.activity');
        Route::get('/{id}', [ActivityController::class, 'showActivity'])->name('dashboard.activity.show');
                Route::put('/{id}/update', [ActivityController::class, 'updateActivity'])->name('dashboard.activities.update');
        Route::delete('/{id}', [ActivityController::class, 'deleteActivity'])->name('dashboard.activity.delete');
    });
    
    // Tags routes
    Route::prefix('tags')->group(function () {
        Route::get('/', [TagController::class, 'tags'])->name('dashboard.tags');
        Route::get('/{id}', [TagController::class, 'showTag'])->name('dashboard.tags.show');
        Route::put('/{id}/update', [TagController::class, 'updateTag'])->name('dashboard.tags.update');
        Route::delete('/{id}', [TagController::class, 'deleteTag'])->name('dashboard.tags.delete');
    });
    
    // Reservations routes   
    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'reservations'])->name('dashboard.reservations');
        Route::get('/{id}', [ReservationController::class, 'showOrder'])->name('dashboard.reservations.show');
        Route::put('/{id}/update', [ReservationController::class, 'updateOrder'])->name('dashboard.reservations.update');
        Route::delete('/{id}', [ReservationController::class, 'deleteOrder'])->name('dashboard.reservations.delete');
    });

    // Menus routes
    Route::prefix('menus')->group(function () {
        Route::get('/', [MenuController::class, 'menus'])->name('dashboard.menus');
        Route::get('/{id}', [MenuController::class, 'showProduct'])->name('dashboard.menus.show');
        Route::put('/{id}/update', [MenuController::class, 'updateProduct'])->name('dashboard.menus.update');
        Route::delete('/{id}', [MenuController::class, 'deleteProduct'])->name('dashboard.menus.delete');
    });

    // Ingredients routes
    Route::prefix('ingredients')->group(function () {
        Route::get('/', [IngredientController::class, 'ingredients'])->name('dashboard.ingredients');
        Route::get('/{id}', [IngredientController::class, 'showIngredient'])->name('dashboard.ingredients.show');
        Route::put('/{id}/update', [IngredientController::class, 'updateIngredient'])->name('dashboard.ingredients.update');
        Route::delete('/{id}', [IngredientController::class, 'deleteIngredient'])->name('dashboard.ingredients.delete');
    });
    
    // Categories routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::put('/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

    // Options routes
    Route::prefix('options')->group(function () {
        Route::get('/', [OptionController::class, 'options'])->name('dashboard.options');
        Route::get('/{id}', [OptionController::class, 'showOption'])->name('dashboard.options.show');
        Route::put('/{id}/update', [OptionController::class, 'updateOption'])->name('dashboard.options.update');
        Route::delete('/{id}', [OptionController::class, 'deleteOption'])->name('dashboard.options.delete');
    });

    // Dishes routes
    Route::prefix('dishes')->group(function () {
        Route::get('/', [DishController::class, 'dishes'])->name('dashboard.dishes');
        Route::get('/{id}', [DishController::class, 'showDish'])->name('dashboard.dishes.show');
        Route::put('/{id}/update', [DishController::class, 'updateDish'])->name('dashboard.dishes.update');
        Route::delete('/{id}', [DishController::class, 'deleteDish'])->name('dashboard.dishes.delete');
    });

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // Route::get('/statistics', [DashboardController::class, 'statistics'])->name('dashboard.statistics');
    // Products API routes
  
    // Users routes
    Route::prefix('users')->group(function () {
        Route::get('/{id}', [UserController::class, 'showUser'])->name('dashboard.user.show');
        Route::get('/{id}/edit', [UserController::class, 'editUser'])->name('dashboard.user.edit');
        Route::put('/{id}/update', [UserController::class, 'updateUser'])->name('dashboard.user.update');
        Route::get('/create', [UserController::class, 'createUser'])->name('dashboard.user.create');
        Route::post('/store', [UserController::class, 'storeUser'])->name('dashboard.user.store');
        Route::delete('/{id}', [UserController::class, 'deleteUser'])->name('dashboard.user.delete');
    });
   
    // Settings routes (si je vais l'implementer)
    // Route::prefix('settings')->group(function () {
    //     Route::get('/', [DashboardController::class, 'settings'])->name('dashboard.settings');
    //     Route::get('/{id}', [DashboardController::class, 'showSetting'])->name('dashboard.settings.show');
    //     Route::put('/{id}/update', [DashboardController::class, 'updateSetting'])->name('dashboard.settings.update');
    //     Route::delete('/{id}', [DashboardController::class, 'deleteSetting'])->name('dashboard.settings.delete');
    // });



    // Promotions routes (si je vais l'implementer)
    // Route::prefix('promotions')->group(function () {
    //     Route::get('/', [PromotionsController::class, 'promotions'])->name('dashboard.promotions');
    //     Route::get('/{id}', [PromotionsController::class, 'showPromotion'])->name('dashboard.promotions.show');
    //     Route::put('/{id}/update', [PromotionsController::class, 'updatePromotion'])->name('dashboard.promotions.update');
    //     Route::delete('/{id}', [PromotionsController::class, 'deletePromotion'])->name('dashboard.promotions.delete');
    // });

});

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');
