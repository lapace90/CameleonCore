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
        $editUrl = env('APP_FRONTEND_URL', 'http://localhost:5173') . "/edit-quote/{$quote->id}/{$quote->validation_token}";

        return view('quote-verification-success', [
            'quote' => $quote,
            'can_pay' => true,
            'api_url' => config('app.api_url'),
            'editUrl' => $editUrl
        ]);
    }

    return view('quote-verification-error', [
        'message' => $quote->isTokenExpired()
            ? 'Ce lien de validation a expiré.'
            : 'Lien de validation invalide.'
    ]);
})->whereNumber('id')->where('token', '[A-Za-z0-9]+');

// ✅ NOUVELLE ROUTE : Page de paiement dédiée (Solution 2)
Route::get('/payment/{quoteId}', function ($quoteId) {
    // Vérifier que le devis existe et est validé
    $quote = \App\Models\QuoteRequest::findOrFail($quoteId);

    if (!$quote->email_verified_at) {
        return redirect('/')->with('error', 'Ce devis n\'est pas validé pour le paiement');
    }

    // Retourner la vue frontend (page payment)
    return view('spa-entry', [
        'title' => 'Paiement - CampCameleonX',
        'route' => "/payment/{$quoteId}"
    ]);
})->whereNumber('quoteId')->name('payment.page');

// ✅ ROUTE de redirection depuis email vers page paiement
Route::get('/payment-redirect/{id}/{token}', function (int $id, string $token) {
    $quote = \App\Models\QuoteRequest::findOrFail($id);

    if ($quote->validateWithToken(trim($token))) {
        // Redirection vers la page de paiement dédiée
        return redirect()->route('payment.page', ['quoteId' => $quote->id]);
    }

    return view('quote-verification-error', [
        'message' => $quote->isTokenExpired()
            ? 'Ce lien de validation a expiré.'
            : 'Lien de validation invalide.'
    ]);
})->whereNumber('id')->where('token', '[A-Za-z0-9]+');

Route::get('/payment-success', [StripeController::class, 'handlePaymentSuccess']);
Route::get('/payment-cancel', [StripeController::class, 'handlePaymentCancel']);

// debug
Route::get('/debug-payment/{sessionId}/{quoteId}', function ($sessionId, $quoteId) {
    try {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        $quote = \App\Models\QuoteRequest::findOrFail($quoteId);

        return [
            'template_exists' => view()->exists('payment-success'),
            'session_payment_status' => $session->payment_status,
            'quote_status' => $quote->status,
            'amount_paid' => $session->amount_total / 100,
            'customer_name' => $quote->name,
            'quote_reference' => $quote->quote_reference,
        ];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
});
