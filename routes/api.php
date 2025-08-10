<?php

// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Routes API Platform
|--------------------------------------------------------------------------
|
| API Platform gère automatiquement ces routes :
| - GET /api/products (collection)
| - GET /api/products/{id} (item)
| - POST /api/products (create)
| - PUT /api/products/{id} (update)
| - PATCH /api/products/{id} (partial update)
| - DELETE /api/products/{id} (delete)
|
*/

// Routes personnalisées qui ne sont pas gérées par API Platform
Route::prefix('custom')->group(function () {
    // Stats endpoint personnalisé
    Route::get('/products/stats', [ProductController::class, 'stats']);
    
    // Debug endpoint (dev only)
    if (app()->environment('local')) {
        Route::get('/products/debug', [ProductController::class, 'index']);
    }
});

// Si tu veux garder tes anciennes routes pour la compatibilité
Route::prefix('legacy')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/search', [ProductController::class, 'search']);
});

/*
|--------------------------------------------------------------------------
| Configuration API Platform
|--------------------------------------------------------------------------
|
| Dans config/api-platform.php, assure-toi d'avoir :
*/

// return [
//     'resources' => [
//         App\Models\Product::class,
//         App\Models\Category::class,
//         App\Models\Menu::class,
//         App\Models\Dish::class,
//         App\Models\Ingredient::class,
//         App\Models\Activity::class,
//         App\Models\Room::class,
//     ],
//     'formats' => [
//         'jsonld' => ['application/ld+json'],
//         'json' => ['application/json'],
//     ],
//     'patch_formats' => [
//         'json' => ['application/merge-patch+json'],
//     ],
//     'swagger' => [
//         'versions' => [3],
//     ],
// ];