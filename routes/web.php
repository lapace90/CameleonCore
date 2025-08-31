<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index']);

Route::middleware(['api'])
    ->prefix('auth')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/verify', [AuthController::class, 'verify']);
        });
    });

    
Route::prefix('api/admin')
    ->middleware(['auth:sanctum'])  // Protection par authentification
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        
        // 🔧 Routes pour les utilisateurs (hors API Platform)
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{user}', [UserController::class, 'show']);
            Route::put('/{user}', [UserController::class, 'update']);
            Route::delete('/{user}', [UserController::class, 'destroy']);
            Route::patch('/{user}/status', [UserController::class, 'updateStatus']);
        });
        
        // 🔧 Endpoints de stats (optionnels - créer si nécessaires)
        Route::get('stats/users', function() {
            return response()->json(['total_users' => \App\Models\User::count()]);
        });
        
        Route::get('stats/basic', function() {
            return response()->json([
                'users_count' => \App\Models\User::count(),
                'products_count' => 0, // À adapter selon vos besoins
            ]);
        });
    });
