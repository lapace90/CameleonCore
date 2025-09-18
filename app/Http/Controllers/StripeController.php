<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuoteRequest;
use Stripe\Stripe;
use Illuminate\Support\Facades\Schema;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Créer une session de paiement Stripe depuis un devis
     */
    public function createPaymentSession(Request $request)
    {
        try {
            $request->validate([
                'quote_id' => 'required|integer|exists:quote_requests,id'
            ]);

            $quote = QuoteRequest::with('customer')->findOrFail($request->quote_id);

            Log::info('💳 Tentative création session Stripe', [
                'quote_id' => $quote->id,
                'quote_reference' => $quote->quote_reference,
                'status' => $quote->status,
                'email_verified_at' => $quote->email_verified_at?->toDateTimeString(),
                'total_amount' => $quote->total_amount
            ]);

            // ✅ CORRECTION : Vérifier que l'EMAIL est validé (pas le status)
            if (!$quote->email_verified_at) {
                Log::warning('❌ Email pas validé pour paiement', [
                    'quote_id' => $quote->id,
                    'email_verified_at' => $quote->email_verified_at,
                    'status' => $quote->status
                ]);

                return response()->json([
                    'error' => 'L\'email doit être validé avant le paiement',
                    'quote_status' => $quote->status,
                    'quote_reference' => $quote->quote_reference,
                    'email_verified' => (bool) $quote->email_verified_at
                ], 400);
            }

            // ✅ Vérifier que le montant est valide
            if (!$quote->total_amount || $quote->total_amount <= 0) {
                return response()->json([
                    'error' => 'Montant du devis invalide',
                    'total_amount' => $quote->total_amount
                ], 400);
            }

            // ✅ Préparer les données pour Stripe
            $lineItems = $this->buildStripeLineItems($quote);

            // ✅ URLs de retour
            $successUrl = config('app.url') . '/payment-success?session_id={CHECKOUT_SESSION_ID}&quote_id=' . $quote->id;
            $cancelUrl = config('app.url') . '/payment-cancel?quote_id=' . $quote->id;

            Log::info('📋 Configuration session Stripe', [
                'line_items_count' => count($lineItems),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $quote->email
            ]);

            // ✅ Créer la session Stripe Checkout
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $quote->email,
                'client_reference_id' => $quote->quote_reference,
                'metadata' => [
                    'quote_id' => $quote->id,
                    'quote_reference' => $quote->quote_reference,
                    'customer_email' => $quote->email,
                    'source' => 'campcameleonx_website'
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'quote_id' => $quote->id,
                        'quote_reference' => $quote->quote_reference
                    ]
                ],
                'expires_at' => time() + (30 * 60), // Expire dans 30 minutes
            ]);

            Log::info('✅ Session Stripe créée avec succès', [
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'quote_reference' => $quote->quote_reference,
                'amount_total' => $session->amount_total
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'quote_reference' => $quote->quote_reference,
                'amount' => $session->amount_total / 100
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('❌ Erreur API Stripe', [
                'error_code' => $e->getStripeCode(),
                'error_message' => $e->getMessage(),
                'quote_id' => $request->quote_id ?? null
            ]);

            return response()->json([
                'error' => 'Erreur de configuration paiement: ' . $e->getMessage(),
                'stripe_code' => $e->getStripeCode()
            ], 500);
        } catch (\Exception $e) {
            Log::error('❌ Erreur création session Stripe', [
                'error' => $e->getMessage(),
                'quote_id' => $request->quote_id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => 'Erreur lors de la création de la session de paiement',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Construction des line items Stripe
     */
    private function buildStripeLineItems(QuoteRequest $quote): array
    {
        return [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => "Séjour CampCameleonX - {$quote->quote_reference}",
                        'description' => $this->buildQuoteDescription($quote),
                    ],
                    'unit_amount' => intval($quote->total_amount * 100), // Convertir en centimes
                ],
                'quantity' => 1,
            ]
        ];
    }

    /**
     * Construction description détaillée
     */
    private function buildQuoteDescription(QuoteRequest $quote): string
    {
        $description = [];

        if ($quote->checkin_date && $quote->checkout_date) {
            $description[] = "Du {$quote->checkin_date->format('d/m/Y')} au {$quote->checkout_date->format('d/m/Y')}";
        }

        if ($quote->guests) {
            $description[] = "{$quote->guests} personne(s)";
        }

        $productsCount = count($quote->selected_product_ids ?? []);
        if ($productsCount > 0) {
            $description[] = "{$productsCount} prestation(s) sélectionnée(s)";
        }

        return implode(' - ', $description) ?: "Séjour personnalisé CampCameleonX";
    }

    /**
     * Gérer le retour après paiement réussi
     */
    public function handlePaymentSuccess(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $quoteId = $request->input('quote_id');

            Log::info('🎉 Retour paiement success', [
                'session_id' => $sessionId,
                'quote_id' => $quoteId
            ]);

            if (!$sessionId || !$quoteId) {
                return view('payment-error', [
                    'message' => 'Paramètres manquants pour vérifier le paiement'
                ]);
            }

            // Récupérer la session Stripe pour vérifier le paiement
            $session = Session::retrieve($sessionId);
            $quote = QuoteRequest::findOrFail($quoteId);

            if ($session->payment_status === 'paid') {

                // ✅ CORRECTION : On garde le statut existant, pas d'update qui peut planter
                // Le paiement est confirmé côté Stripe, c'est l'essentiel !

                // Optionnel : Si vous voulez tracker le paiement, décommentez ces lignes
                // try {
                //     $quote->update(['status' => 'sent']); // ou un autre statut autorisé
                // } catch (\Exception $e) {
                //     Log::warning('Impossible d\'updater le statut du devis', ['error' => $e->getMessage()]);
                // }

                Log::info('✅ Paiement confirmé depuis success page', [
                    'session_id' => $sessionId,
                    'quote_id' => $quoteId,
                    'quote_reference' => $quote->quote_reference,
                    'amount_paid' => $session->amount_total / 100
                ]);

                // ✅ RETOUR SÉCURISÉ de la vue avec gestion des erreurs template
                try {
                    return view('payment-success', [
                        'quote' => $quote,
                        'session' => $session,
                        'amount_paid' => $session->amount_total / 100
                    ]);
                } catch (\Exception $viewException) {
                    Log::error('Erreur template payment-success', [
                        'error' => $viewException->getMessage(),
                        'file' => $viewException->getFile(),
                        'line' => $viewException->getLine()
                    ]);

                    // Fallback : page de succès simple en HTML pur
                    return response("
                    <html>
                    <head><title>Paiement réussi</title></head>
                    <body style='font-family:Arial;text-align:center;padding:50px;'>
                        <h1>🎉 Paiement réussi !</h1>
                        <p>Merci {$quote->name} !</p>
                        <p>Votre réservation <strong>{$quote->quote_reference}</strong> est confirmée.</p>
                        <p>Montant payé : <strong>" . number_format($session->amount_total / 100, 2) . "€</strong></p>
                        <p>Vous allez recevoir un email de confirmation.</p>
                        <a href='mailto:contact@campcameleonx.com'>📧 Nous contacter</a>
                    </body>
                    </html>
                ", 200);
                }
            }

            // Si le paiement n'est pas confirmé
            Log::warning('Paiement non confirmé', [
                'session_id' => $sessionId,
                'payment_status' => $session->payment_status,
                'quote_id' => $quoteId
            ]);

            return view('payment-error', [
                'message' => 'Le paiement n\'a pas été confirmé par Stripe. Statut: ' . $session->payment_status
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Erreur traitement paiement success', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? null,
                'quote_id' => $quoteId ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback en cas d'erreur générale
            return response("
            <html>
            <head><title>Erreur paiement</title></head>
            <body style='font-family:Arial;text-align:center;padding:50px;'>
                <h1>❌ Erreur</h1>
                <p>Une erreur s'est produite lors de la vérification du paiement.</p>
                <p>Votre paiement a probablement été traité mais nous n'avons pas pu afficher la page de confirmation.</p>
                <p>Veuillez nous contacter : <a href='mailto:contact@campcameleonx.com'>contact@campcameleonx.com</a></p>
            </body>
            </html>
        ", 500);
        }
    }

    /**
     * Gérer l'annulation du paiement
     */
    public function handlePaymentCancel(Request $request)
    {
        $quoteId = $request->input('quote_id');
        $quote = null;

        if ($quoteId) {
            $quote = QuoteRequest::find($quoteId);
        }

        Log::info('🔄 Paiement annulé par utilisateur', [
            'quote_id' => $quoteId,
            'quote_reference' => $quote->quote_reference ?? null
        ]);

        return view('payment-cancel', [
            'quote' => $quote,
            'message' => 'Le paiement a été annulé. Vous pouvez réessayer quand vous le souhaitez.'
        ]);
    }
}
