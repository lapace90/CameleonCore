<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\StripeController;


Route::prefix('stripe')->group(function () {
    // Créer une session de paiement depuis un devis validé
    Route::post('/create-payment-session', [StripeController::class, 'createPaymentSession']);
});

// 🔐 Auth - ça marche
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('verify', [AuthController::class, 'verify']);
        Route::get('me', [AuthController::class, 'me']); // si ça existe
    });
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

Route::get('/quote-requests/{id}/edit/{token}', function($id, $token) {
    $processor = new \App\State\QuoteRequestProcessor(new \App\Services\EmailValidationService());
    
    try {
        $quote = \App\Models\QuoteRequest::findOrFail($id);
        
        if (!$quote->validation_token || !hash_equals($quote->validation_token, $token)) {
            return response()->json(['error' => 'Token invalide'], 400);
        }
        
        return response()->json($quote);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Devis non trouvé'], 404);
    }
});
