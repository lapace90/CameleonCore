<?php

// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\AuthController;

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
