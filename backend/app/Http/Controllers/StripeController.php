<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuoteRequest;
use App\Models\Reservation;
use App\Models\Invoice;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use App\Services\ReservationCreationService;
use App\Services\AdminNotificationService;
use App\Services\InvoiceService;  

class StripeController extends Controller
{
    private ReservationCreationService $reservationService;
    private AdminNotificationService $notificationService;
    private InvoiceService $invoiceService;  

    public function __construct(
        ReservationCreationService $reservationService,
        AdminNotificationService $notificationService,
        InvoiceService $invoiceService  
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->reservationService = $reservationService;
        $this->notificationService = $notificationService;
        $this->invoiceService = $invoiceService;  
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

            // Vérifier que l'EMAIL est validé
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

            // Vérifier que le montant est valide
            if (!$quote->total_amount || $quote->total_amount <= 0) {
                return response()->json([
                    'error' => 'Montant du devis invalide',
                    'total_amount' => $quote->total_amount
                ], 400);
            }

            // Préparer les données pour Stripe
            $lineItems = $this->buildStripeLineItems($quote);

            // URLs de retour
            $successUrl = config('app.url') . '/payment-success?session_id={CHECKOUT_SESSION_ID}&quote_id=' . $quote->id;
            $cancelUrl = config('app.url') . '/payment-cancel?quote_id=' . $quote->id;

            Log::info('📋 Configuration session Stripe', [
                'line_items_count' => count($lineItems),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $quote->customer->email ?? 'N/A'
            ]);

            // Créer la session Stripe Checkout
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
                'expires_at' => time() + (30 * 60),
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
     * Gérer le retour après paiement réussi avec création automatique de réservation + facture
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
                Log::info('✅ Paiement confirmé - Démarrage création réservation + facture', [
                    'session_id' => $sessionId,
                    'quote_id' => $quoteId,
                    'amount_paid' => $session->amount_total / 100
                ]);

                $reservation = null;
                $invoice = null;

                // Créer automatiquement la réservation
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

                        //  ÉTAPE 1 : Créer la réservation
                        // (L'Observer va créer automatiquement la facture)
                        $reservation = $this->reservationService->createReservationFromQuote($quote, $paymentData);

                        Log::info('🏨 Réservation créée avec succès', [
                            'reservation_id' => $reservation->id,
                            'quote_reference' => $quote->quote_reference,
                            'customer' => $quote->customer->name ?? 'N/A',
                            'payment_status' => $reservation->payment_status
                        ]);

                        //  ÉTAPE 2 : Récupérer la facture créée automatiquement par l'Observer
                        $invoice = Invoice::where('reservation_id', $reservation->id)->first();

                        if ($invoice) {
                            Log::info('🧾 Facture détectée (créée par Observer)', [
                                'invoice_id' => $invoice->id,
                                'invoice_number' => $invoice->invoice_number,
                                'status' => $invoice->status
                            ]);

                            //  ÉTAPE 3 : Envoyer la facture par email
                            try {
                                $this->invoiceService->sendEmail($invoice);
                                Log::info('📧 Facture envoyée par email', [
                                    'invoice_id' => $invoice->id,
                                    'customer_email' => $invoice->customer->email
                                ]);
                            } catch (\Exception $emailError) {
                                Log::warning('⚠️ Erreur envoi email facture', [
                                    'invoice_id' => $invoice->id,
                                    'error' => $emailError->getMessage()
                                ]);
                                // On continue même si l'email échoue
                            }
                        } else {
                            Log::warning('⚠️ Aucune facture créée automatiquement', [
                                'reservation_id' => $reservation->id
                            ]);
                        }

                        // Notifier les admins
                        $this->notificationService->notifyNewReservation($reservation);

                    } else {
                        Log::info('ℹ️ Réservation existe déjà', [
                            'existing_reservation_id' => $existingReservation->id,
                            'quote_reference' => $quote->quote_reference
                        ]);
                        $reservation = $existingReservation;
                        $invoice = Invoice::where('reservation_id', $reservation->id)->first();
                    }

                } catch (\Exception $reservationError) {
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
                        'invoice' => $invoice,  
                        'amount_paid' => $session->amount_total / 100,
                        'reservation_created' => (bool) $reservation,
                        'invoice_created' => (bool) $invoice  
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
                        " . ($reservation ? "<p>✅ Réservation #{$reservation->id} créée</p>" : "") . "
                        " . ($invoice ? "<p>✅ Facture {$invoice->invoice_number} générée</p>" : "") . "
                        <p>Vous allez recevoir un email de confirmation avec votre facture.</p>
                        <a href='mailto:contact@campcameleonx.com'><i class='fas fa-envelope'></i> Nous contacter</a>
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
     * Gérer l'annulation du paiement
     */
    public function handlePaymentCancel(Request $request)
    {
        $quoteId = $request->input('quote_id');

        Log::info('❌ Paiement annulé par l\'utilisateur', [
            'quote_id' => $quoteId
        ]);

        return view('payment-cancel', [
            'quote_id' => $quoteId
        ]);
    }

    /**
     * Construire les line items pour Stripe depuis le devis
     */
    private function buildStripeLineItems(QuoteRequest $quote): array
    {
        return [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => "Séjour CampCameleonX - {$quote->quote_reference}",
                    'description' => "Du " . $quote->checkin_date->format('d/m/Y') . " au " . $quote->checkout_date->format('d/m/Y'),
                ],
                'unit_amount' => (int) ($quote->total_amount * 100),
            ],
            'quantity' => 1,
        ]];
    }
}