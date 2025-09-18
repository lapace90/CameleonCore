<?php

use Illuminate\Support\Facades\Route;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\StripeController;

// Page d'accueil simple
Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index']);

/*
|--------------------------------------------------------------------------  
| ⚠️ IMPORTANT pour API Platform
|--------------------------------------------------------------------------
|
| N'ajoutez PAS de routes API ici ! Toutes vos entités (Product, Dish, Menu, etc.)
| sont déjà exposées automatiquement par API Platform via les annotations.
|
| Les routes se trouvent à :
| - /api/products (géré par ProductCollectionProvider/ProductProcessor)
| - /api/dishes (géré par les annotations dans Dish.php) 
| - /api/menus, /api/activities, /api/rooms, etc.
|
| Pour ajouter des routes personnalisées, utilisez routes/api.php avec un préfixe
| différent (ex: /api/auth, /api/admin) pour éviter les conflits.
*/

Route::get('/validate-quote/{id}/{token}', function (int $id, string $token) {
    $quote = \App\Models\QuoteRequest::findOrFail($id);

    if ($quote->validateWithToken(trim($token))) {
        return view('quote-verification-success', ['quote' => $quote]);
    }

    return view('quote-verification-error', [
        'message' => $quote->isTokenExpired()
            ? 'Ce lien de validation a expiré.'
            : 'Lien de validation invalide.'
    ]);
})->whereNumber('id')->where('token', '[A-Za-z0-9]+');


// Route pour gérer les erreurs de validation
Route::view('/quote-validation-error', 'quote-verification-error', [
    'message' => 'Lien de validation invalide ou expiré.'
])->name('quote.validation.error');


Route::get('/test-stripe', function () {
    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

    try {
        $account = \Stripe\Account::retrieve();
        return "✅ Stripe connecté ! Compte : " . $account->id;
    } catch (\Exception $e) {
        return "❌ Erreur : " . $e->getMessage();
    }
});

// Pages de retour après paiement Stripe
Route::get('/payment-success', [StripeController::class, 'handlePaymentSuccess'])->name('stripe.success');
Route::get('/payment-cancel', [StripeController::class, 'handlePaymentCancel'])->name('stripe.cancel');

// Route temporaire pour tester Stripe (à supprimer plus tard)
Route::get('/test-stripe', function () {
    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

    try {
        $account = \Stripe\Account::retrieve();
        return "✅ Stripe connecté ! Compte : " . $account->id;
    } catch (\Exception $e) {
        return "❌ Erreur : " . $e->getMessage();
    }
});
