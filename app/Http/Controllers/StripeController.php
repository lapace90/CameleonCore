<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuoteRequest;
use App\Models\Reservation;
use Stripe\Stripe;
use Illuminate\Support\Facades\Schema;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\ReservationCreationService;
use App\Services\AdminNotificationService;

class StripeController extends Controller
{
    private ReservationCreationService $reservationService;
    private AdminNotificationService $notificationService;

    public function __construct(
        ReservationCreationService $reservationService,
        AdminNotificationService $notificationService
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->reservationService = $reservationService;
        $this->notificationService = $notificationService;
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
                'customer_email' => $quote->customer->email ?? 'N/A'
            ]);

            // ✅ Créer la session Stripe Checkout
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $quote->customer->email ?? null,
                'client_reference_id' => $quote->quote_reference,
                'metadata' => [
                    'quote_id' => $quote->id,
                    'quote_reference' => $quote->quote_reference,
                    'customer_email' => $quote->customer->email ?? '',
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
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Erreur lors de la création de la session de paiement',
                'details' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * 🆕 NOUVELLE VERSION - Gérer le retour après paiement réussi avec création automatique de réservation
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

            // Récupérer la session Stripe et le devis
            $session = Session::retrieve($sessionId);
            $quote = QuoteRequest::with('customer')->findOrFail($quoteId);

            if ($session->payment_status === 'paid') {
                Log::info('✅ Paiement confirmé - Démarrage création réservation', [
                    'session_id' => $sessionId,
                    'quote_id' => $quoteId,
                    'amount_paid' => $session->amount_total / 100
                ]);

                $reservation = null;

                // 🆕 NOUVELLE LOGIQUE : Créer automatiquement la réservation
                try {
                    // Vérifier si une réservation n'existe pas déjà pour ce devis
                    $existingReservation = Reservation::where('quote_reference', $quote->quote_reference)->first();
                    
                    if (!$existingReservation) {
                        // Préparer les données de paiement
                        $paymentData = [
                            'session_id' => $sessionId,
                            'amount' => $session->amount_total / 100,
                            'stripe_payment_intent' => $session->payment_intent,
                            'payment_method' => 'stripe_card',
                        ];

                        // Créer la réservation
                        $reservation = $this->reservationService->createReservationFromQuote($quote, $paymentData);

                        // Notifier les admins
                        $this->notificationService->notifyNewReservation($reservation);

                        Log::info('🏨 Réservation créée avec succès', [
                            'reservation_id' => $reservation->id,
                            'quote_reference' => $quote->quote_reference,
                            'customer' => $quote->customer->name ?? 'N/A'
                        ]);

                        // Optionnel : Envoyer email de confirmation au client
                        $this->sendReservationConfirmationToCustomer($reservation);

                    } else {
                        Log::info('ℹ️ Réservation existe déjà', [
                            'existing_reservation_id' => $existingReservation->id,
                            'quote_reference' => $quote->quote_reference
                        ]);
                        $reservation = $existingReservation;
                    }

                } catch (\Exception $reservationError) {
                    // En cas d'erreur lors de la création de réservation,
                    // on affiche quand même la page de succès du paiement
                    Log::error('❌ Erreur création réservation', [
                        'error' => $reservationError->getMessage(),
                        'quote_id' => $quoteId,
                        'session_id' => $sessionId,
                        'trace' => $reservationError->getTraceAsString()
                    ]);
                    
                    // Créer une notification manuelle pour les admins
                    $this->notificationService->createManualReservationAlert([
                        'quote_id' => $quote->id,
                        'quote_reference' => $quote->quote_reference,
                        'customer_name' => $quote->customer->name ?? 'N/A',
                        'amount_paid' => $session->amount_total / 100,
                        'session_id' => $sessionId,
                        'error' => $reservationError->getMessage(),
                    ]);
                }

                // Retourner la page de succès avec toutes les infos
                try {
                    return view('payment-success', [
                        'quote' => $quote,
                        'session' => $session,
                        'reservation' => $reservation,
                        'amount_paid' => $session->amount_total / 100,
                        'reservation_created' => (bool) $reservation
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
                        <p>Merci " . ($quote->customer->name ?? 'Client') . " !</p>
                        <p>Votre réservation <strong>{$quote->quote_reference}</strong> est confirmée.</p>
                        <p>Montant payé : <strong>" . number_format($session->amount_total / 100, 2) . "€</strong></p>
                        " . ($reservation ? "<p>✅ Réservation #{$reservation->id} créée automatiquement</p>" : "") . "
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
                'trace' => $e->getTraceAsString()
            ]);

            return view('payment-error', [
                'message' => 'Une erreur est survenue lors du traitement de votre paiement. Veuillez nous contacter.'
            ]);
        }
    }

    /**
     * 🆕 Envoyer l'email de confirmation de réservation au client
     */
    private function sendReservationConfirmationToCustomer(Reservation $reservation): void
    {
        try {
            $customer = $reservation->customer;
            
            // Email simple en attendant le template
            Mail::raw("
Bonjour {$customer->name},

Votre réservation est confirmée !

Réservation #{$reservation->id}
Référence: {$reservation->quote_reference}
Arrivée: {$reservation->checkin}
Départ: {$reservation->checkout}
Montant: {$reservation->amount}€

Nous avons hâte de vous accueillir !

L'équipe CampCameleonX
            ", function ($message) use ($customer, $reservation) {
                $message->to($customer->email)
                    ->subject("✅ Confirmation réservation #{$reservation->id} - CampCameleonX")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('✅ Email confirmation client envoyé', [
                'reservation_id' => $reservation->id,
                'customer_email' => $customer->email
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi email confirmation client', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
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

    /**
     * Construire les line items pour Stripe depuis un devis
     */
    private function buildStripeLineItems(QuoteRequest $quote): array
    {
        return [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $this->buildProductDescription($quote),
                        'description' => "Séjour du {$quote->checkin_date} au {$quote->checkout_date} - {$quote->guests} personne(s)",
                    ],
                    'unit_amount' => (int) ($quote->total_amount * 100), // Montant en centimes
                ],
                'quantity' => 1,
            ]
        ];
    }

    /**
     * Construire la description du produit pour Stripe
     */
    private function buildProductDescription(QuoteRequest $quote): string
    {
        $description = ['Séjour CampCameleonX'];
        
        $productsCount = count($quote->selected_product_ids ?? []);
        if ($productsCount > 0) {
            $description[] = "{$productsCount} prestation(s) sélectionnée(s)";
        }

        return implode(' - ', $description) ?: "Séjour personnalisé CampCameleonX";
    }
}