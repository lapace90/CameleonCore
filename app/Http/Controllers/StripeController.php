<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuoteRequest;
use Stripe\Stripe;
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

            // Vérifier que le devis est validé (email confirmé)
            if ($quote->status !== 'validated') {
                return response()->json([
                    'error' => 'Le devis doit être validé par email avant le paiement',
                    'quote_status' => $quote->status
                ], 400);
            }

            // Préparer les données pour Stripe
            $lineItems = $this->buildStripeLineItems($quote);
            
            // Créer la session Stripe Checkout
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => config('app.frontend_url') . '/payment-success?session_id={CHECKOUT_SESSION_ID}&quote_id=' . $quote->id,
                'cancel_url' => config('app.frontend_url') . '/payment-cancel?quote_id=' . $quote->id,
                'customer_email' => $quote->email,
                'metadata' => [
                    'quote_id' => $quote->id,
                    'quote_reference' => $quote->quote_reference,
                    'customer_email' => $quote->email,
                    'source' => 'campcameleonx_website'
                ],
                // Informations supplémentaires
                'payment_intent_data' => [
                    'metadata' => [
                        'quote_id' => $quote->id,
                        'customer_name' => $quote->customer?->name . ' ' . $quote->customer?->last_name,
                    ]
                ]
            ]);

            Log::info('💳 Session Stripe créée', [
                'session_id' => $session->id,
                'quote_id' => $quote->id,
                'quote_reference' => $quote->quote_reference,
                'amount' => $quote->total_amount,
                'customer' => $quote->email
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'quote_reference' => $quote->quote_reference
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Données invalides',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Erreur création session Stripe', [
                'error' => $e->getMessage(),
                'quote_id' => $request->quote_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Impossible de créer la session de paiement. Veuillez réessayer.'
            ], 500);
        }
    }

    /**
     * Construire les items pour Stripe à partir du devis
     */
    private function buildStripeLineItems(QuoteRequest $quote): array
    {
        // Version simple : un seul item avec le total
        return [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => "Séjour CampCameleonX - {$quote->quote_reference}",
                        'description' => $this->buildProductDescription($quote),
                        'images' => [
                            // Optionnel : URL de l'image de ton camping
                            // 'https://campcameleonx.com/images/hero.jpg'
                        ],
                    ],
                    'unit_amount' => intval($quote->total_amount * 100), // Convertir en centimes
                ],
                'quantity' => 1,
            ]
        ];

        // Version détaillée (si tu veux séparer chaque produit) :
        // return $this->buildDetailedLineItems($quote);
    }

    /**
     * Description du séjour pour Stripe
     */
    private function buildProductDescription(QuoteRequest $quote): string
    {
        $description = [];
        
        if ($quote->checkin_date && $quote->checkout_date) {
            $description[] = "Séjour du {$quote->checkin_date->format('d/m/Y')} au {$quote->checkout_date->format('d/m/Y')}";
        }
        
        $description[] = "Pour {$quote->guests} personne(s)";
        
        if (count($quote->selected_product_ids) > 0) {
            $description[] = count($quote->selected_product_ids) . " produit(s) sélectionné(s)";
        }

        return implode(' - ', $description);
    }

    /**
     * Gérer le retour après paiement réussi
     */
    public function handlePaymentSuccess(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $quoteId = $request->input('quote_id');

            if (!$sessionId || !$quoteId) {
                return view('payment-error', [
                    'message' => 'Paramètres manquants pour vérifier le paiement'
                ]);
            }

            // Récupérer la session Stripe pour vérifier le paiement
            $session = Session::retrieve($sessionId);
            $quote = QuoteRequest::findOrFail($quoteId);

            if ($session->payment_status === 'paid') {
                
                // Marquer le devis comme payé (tu peux ajouter un champ status_payment)
                $quote->update([
                    'status' => 'paid',
                    // Optionnel : ajouter des champs pour tracker le paiement
                    // 'stripe_session_id' => $sessionId,
                    // 'stripe_payment_intent' => $session->payment_intent,
                    // 'paid_at' => now()
                ]);

                Log::info('✅ Paiement confirmé depuis success page', [
                    'session_id' => $sessionId,
                    'quote_id' => $quoteId,
                    'quote_reference' => $quote->quote_reference,
                    'amount_paid' => $session->amount_total / 100
                ]);

                // TODO : Envoyer email de confirmation de paiement
                // TODO : Créer la réservation automatiquement
                // Mail::send('emails.payment-confirmation', $emailData, ...);

                return view('payment-success', [
                    'quote' => $quote,
                    'session' => $session,
                    'amount_paid' => $session->amount_total / 100
                ]);
            }

            return view('payment-error', [
                'message' => 'Le paiement n\'a pas été confirmé par Stripe'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur traitement paiement success', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? null,
                'quote_id' => $quoteId ?? null
            ]);

            return view('payment-error', [
                'message' => 'Erreur lors de la vérification du paiement. Contactez-nous si le problème persiste.'
            ]);
        }
    }

    /**
     * Gérer l'annulation de paiement
     */
    public function handlePaymentCancel(Request $request)
    {
        $quoteId = $request->input('quote_id');
        $quote = null;

        if ($quoteId) {
            try {
                $quote = QuoteRequest::findOrFail($quoteId);
                Log::info('💔 Paiement annulé', [
                    'quote_id' => $quoteId,
                    'quote_reference' => $quote->quote_reference
                ]);
            } catch (\Exception $e) {
                Log::warning('Quote introuvable lors annulation', ['quote_id' => $quoteId]);
            }
        }

        return view('payment-cancel', [
            'quote' => $quote
        ]);
    }
}