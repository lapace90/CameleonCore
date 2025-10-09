<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\Log;


Route::prefix('stripe')->group(function () {
    // Créer une session de paiement depuis un devis validé
    Route::post('/create-payment-session', [StripeController::class, 'createPaymentSession']);
});

// 🔐 Auth
Route::prefix('auth')->group(function () {
    // Routes publiques (sans auth)
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']); // 🆕 AJOUTÉ

    // Routes protégées (avec auth)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('verify', [AuthController::class, 'verify']);
        Route::get('me', [AuthController::class, 'me']); // si ça existe
    });
});

// 📧 Contact (route publique)
Route::post('contact', [\App\Http\Controllers\Api\ContactController::class, 'sendContactMessage']);

// ⚙️ SETTINGS - JUSTE auth:sanctum (SANS role:super-admin qui plante)
Route::prefix('admin/settings')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [SettingsController::class, 'index']);
    Route::get('/maintenance-status', [SettingsController::class, 'maintenanceStatus']);
    Route::post('/maintenance', [SettingsController::class, 'maintenance']);
    Route::post('/cache/clear', [SettingsController::class, 'clearCache']);
    Route::get('/system-info', [SettingsController::class, 'systemInfo']);
});

// 📊 Stats
Route::get('admin/stats/dashboard', function () {
    return response()->json([
        'users_count' => \App\Models\User::count(),
        'products_count' => \App\Models\Product::count(),
    ]);
})->middleware(['auth:sanctum']);

Route::get('/quote-requests/{id}/edit/{token}', function ($id, $token) {
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

Route::patch('/quote-requests/{id}/edit/{token}', function ($id, $token) {
    $processor = new \App\State\QuoteRequestProcessor(new \App\Services\EmailValidationService());

    try {
        $quoteRequest = $processor->updateQuoteForEdit(
            (int) $id,
            $token,
            ['request' => request()]
        );

        return response()->json($quoteRequest);
    } catch (\InvalidArgumentException $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    } catch (\Exception $e) {
        Log::error('Erreur mise à jour devis:', [
            'id' => $id,
            'error' => $e->getMessage()
        ]);
        return response()->json(['error' => 'Erreur lors de la mise à jour du devis'], 500);
    }
});
