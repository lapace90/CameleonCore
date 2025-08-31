<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\SettingsController;

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

// Paramètres système (pour super admin seulement)
Route::prefix('settings')->middleware(['role:super-admin'])->group(function () {
    Route::get('/', [SettingsController::class, 'index']);
    Route::get('/maintenance-status', [SettingsController::class, 'maintenanceStatus']);
    Route::post('/maintenance', [SettingsController::class, 'maintenance']);
    Route::post('/cache/clear', [SettingsController::class, 'clearCache']);
    Route::get('/system-info', [SettingsController::class, 'systemInfo']);
});
