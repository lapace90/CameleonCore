<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

class ReservationObserver
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Événement déclenché APRÈS la création d'une réservation
     */
    public function created(Reservation $reservation): void
    {
        Log::info('👀 ReservationObserver::created déclenché', [
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'amount' => $reservation->amount,
            'payment_status' => $reservation->payment_status
        ]);

        // Vérifier qu'une facture n'existe pas déjà pour cette réservation
        if (Invoice::existsForReservation($reservation->id)) {
            Log::info('ℹ️ Une facture existe déjà pour cette réservation', [
                'reservation_id' => $reservation->id
            ]);
            return;
        }

        // Créer la facture automatiquement
        try {
            $invoice = $this->invoiceService->createFromReservation($reservation);

            Log::info('✅ Facture créée automatiquement depuis réservation', [
                'reservation_id' => $reservation->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount
            ]);

            // Optionnel : Envoyer l'email automatiquement si la réservation est confirmée
            if ($reservation->status === 'confirmed' || $reservation->payment_status === 'paid') {
                try {
                    $this->invoiceService->sendEmail($invoice);
                    
                    Log::info('📧 Email facture envoyé automatiquement', [
                        'invoice_id' => $invoice->id,
                        'customer_email' => $invoice->customer->email ?? 'N/A'
                    ]);
                } catch (\Throwable $e) {
                    // Ne pas bloquer si l'envoi d'email échoue
                    Log::warning('⚠️ Erreur envoi email facture automatique', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Throwable $e) {
            // Ne pas bloquer la création de la réservation si la facture échoue
            Log::error('❌ Erreur création facture automatique', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Événement déclenché APRÈS la mise à jour d'une réservation
     */
    public function updated(Reservation $reservation): void
    {
        // Vérifier si le montant a changé
        if ($reservation->isDirty('amount')) {
            Log::info('👀 Montant réservation modifié', [
                'reservation_id' => $reservation->id,
                'old_amount' => $reservation->getOriginal('amount'),
                'new_amount' => $reservation->amount
            ]);

            // Mettre à jour la facture correspondante si elle existe
            $invoice = Invoice::where('reservation_id', $reservation->id)->first();

            if ($invoice) {
                try {
                    $this->invoiceService->syncAmountFromReservation($invoice, $reservation);

                    Log::info('✅ Montant facture synchronisé', [
                        'invoice_id' => $invoice->id,
                        'new_amount' => $invoice->amount
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Erreur synchronisation montant facture', [
                        'reservation_id' => $reservation->id,
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Si le statut de paiement devient "paid", marquer la facture comme payée
        if ($reservation->isDirty('payment_status') && $reservation->payment_status === 'paid') {
            Log::info('👀 Réservation marquée comme payée', [
                'reservation_id' => $reservation->id
            ]);

            $invoice = Invoice::where('reservation_id', $reservation->id)->first();

            if ($invoice && $invoice->canBePaid()) {
                try {
                    $invoice->markAsPaid($reservation->payment_method ?? Invoice::PAYMENT_METHOD_CARD);

                    Log::info('✅ Facture marquée comme payée automatiquement', [
                        'invoice_id' => $invoice->id,
                        'payment_method' => $invoice->payment_method
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Erreur marquage facture comme payée', [
                        'reservation_id' => $reservation->id,
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Événement déclenché AVANT la suppression d'une réservation
     */
    public function deleting(Reservation $reservation): void
    {
        Log::info('👀 ReservationObserver::deleting déclenché', [
            'reservation_id' => $reservation->id
        ]);

        // Récupérer la facture liée
        $invoice = Invoice::where('reservation_id', $reservation->id)->first();

        if ($invoice) {
            Log::info('ℹ️ Facture trouvée pour réservation en cours de suppression', [
                'invoice_id' => $invoice->id,
                'invoice_status' => $invoice->status
            ]);

            // Option 1 : Supprimer la facture si elle n'est pas payée
            if ($invoice->status !== Invoice::STATUS_PAID) {
                try {
                    $invoice->delete();
                    Log::info('✅ Facture supprimée avec la réservation', [
                        'invoice_id' => $invoice->id
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Erreur suppression facture', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                // Option 2 : Garder la facture payée mais retirer le lien
                try {
                    $invoice->update(['reservation_id' => null]);
                    Log::info('✅ Lien facture-réservation retiré (facture payée conservée)', [
                        'invoice_id' => $invoice->id
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Erreur mise à jour facture', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}