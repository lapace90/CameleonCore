<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\PermissionController;

// 🔐 Auth - ça marche
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('verify', [AuthController::class, 'verify']);
        Route::get('me', [AuthController::class, 'me']); // si ça existe
    });
});

// 👥 USERS - JUSTE auth:sanctum, AUCUN autre middleware
Route::prefix('admin/users')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::put('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
    Route::patch('/{user}/status', [UserController::class, 'updateStatus']);
});

// 🎭 ROLES - JUSTE auth:sanctum
Route::prefix('admin/roles')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('/{role}', [RoleController::class, 'show']);
    Route::put('/{role}', [RoleController::class, 'update']);
    Route::delete('/{role}', [RoleController::class, 'destroy']);
});

// 🔑 PERMISSIONS - JUSTE auth:sanctum  
Route::prefix('admin/permissions')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('/{permission}', [PermissionController::class, 'show']);
    Route::put('/{permission}', [PermissionController::class, 'update']);
    Route::delete('/{permission}', [PermissionController::class, 'destroy']);
});

// ⚙️ SETTINGS - JUSTE auth:sanctum (SANS role:super-admin qui plante)
Route::prefix('admin/settings')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [SettingsController::class, 'index']);
    Route::get('/maintenance-status', [SettingsController::class, 'maintenanceStatus']);
    Route::post('/maintenance', [SettingsController::class, 'maintenance']);
    Route::post('/cache/clear', [SettingsController::class, 'clearCache']);
    Route::get('/system-info', [SettingsController::class, 'systemInfo']);
});

// 📊 Stats
Route::get('admin/stats/dashboard', function() {
    return response()->json([
        'users_count' => \App\Models\User::count(),
        'products_count' => \App\Models\Product::count(),
    ]);
})->middleware(['auth:sanctum']);