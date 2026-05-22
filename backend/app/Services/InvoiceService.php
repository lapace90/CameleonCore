<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /**
     * Créer une facture depuis une réservation
     */
    public function createFromReservation(Reservation $reservation): Invoice
    {
        Log::info('🧾 Création facture depuis réservation', [
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'amount' => $reservation->amount
        ]);

        // Vérifier qu'une facture complète n'existe pas déjà
        $existingComplete = Invoice::where('reservation_id', $reservation->id)
            ->where('type', Invoice::TYPE_COMPLETE)
            ->exists();

        if ($existingComplete) {
            throw new \Exception("Une facture existe déjà pour cette réservation");
        }

        // Vérifier que la réservation a un client
        if (!$reservation->customer_id) {
            throw new \Exception("La réservation doit avoir un client");
        }

        // Déterminer le statut initial en fonction du paiement
        $status = match ($reservation->payment_status) {
            'paid' => Invoice::STATUS_PAID,
            'pending' => Invoice::STATUS_UNPAID,
            'canceled' => Invoice::STATUS_CANCELLED,
            default => Invoice::STATUS_UNPAID
        };

        // Préparer les données de la facture
        $invoiceData = [
            'customer_id' => $reservation->customer_id,
            'reservation_id' => $reservation->id,
            'amount' => $reservation->amount,
            'issue_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30), // 30 jours par défaut
            'status' => $status,
            'notes' => $this->generateInvoiceNotes($reservation),
        ];

        // Si déjà payée, ajouter les infos de paiement
        if ($status === Invoice::STATUS_PAID) {
            $invoiceData['payment_date'] = Carbon::now();
            $invoiceData['payment_method'] = $reservation->payment_method ?? Invoice::PAYMENT_METHOD_CARD;
        }

        // Créer la facture
        $invoice = Invoice::create($invoiceData);

        Log::info('✅ Facture créée depuis réservation', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $invoice->amount,
            'status' => $invoice->status
        ]);

        return $invoice->load(['customer', 'reservation']);
    }

    /**
     * Créer une facture d'acompte depuis une réservation
     */
    public function createDepositInvoice(Reservation $reservation): Invoice
    {
        $depositPercentage = config('instance.features.deposit_percentage', 30);
        $depositAmount = round($reservation->amount * $depositPercentage / 100, 2);

        Log::info('🧾 Création facture acompte', [
            'reservation_id' => $reservation->id,
            'total' => $reservation->amount,
            'deposit_pct' => $depositPercentage,
            'deposit_amount' => $depositAmount,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $reservation->customer_id,
            'reservation_id' => $reservation->id,
            'amount' => $depositAmount,
            'type' => Invoice::TYPE_DEPOSIT,
            'issue_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'status' => Invoice::STATUS_PAID,
            'payment_date' => Carbon::now(),
            'payment_method' => Invoice::PAYMENT_METHOD_CARD,
            'notes' => $this->generateDepositNotes($reservation, $depositPercentage),
        ]);

        Log::info('✅ Facture acompte créée', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);

        return $invoice->load(['customer', 'reservation']);
    }

    /**
     * Créer une facture de solde liée à une facture d'acompte
     */
    public function createBalanceInvoice(Invoice $depositInvoice): Invoice
    {
        if ($depositInvoice->type !== Invoice::TYPE_DEPOSIT) {
            throw new \Exception("Seule une facture d'acompte peut générer une facture de solde");
        }

        if ($depositInvoice->linkedFrom) {
            throw new \Exception("Une facture de solde existe déjà pour cet acompte");
        }

        $reservation = $depositInvoice->reservation;
        if (!$reservation) {
            throw new \Exception("Aucune réservation liée à cette facture");
        }

        $balanceAmount = round($reservation->amount - $depositInvoice->amount, 2);

        Log::info('🧾 Création facture solde', [
            'deposit_invoice_id' => $depositInvoice->id,
            'total' => $reservation->amount,
            'deposit' => $depositInvoice->amount,
            'balance' => $balanceAmount,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $depositInvoice->customer_id,
            'reservation_id' => $reservation->id,
            'amount' => $balanceAmount,
            'type' => Invoice::TYPE_BALANCE,
            'linked_invoice_id' => $depositInvoice->id,
            'issue_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'status' => Invoice::STATUS_UNPAID,
            'notes' => $this->generateBalanceNotes($depositInvoice),
        ]);

        Log::info('✅ Facture solde créée', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'linked_to' => $depositInvoice->invoice_number,
        ]);

        return $invoice->load(['customer', 'reservation', 'linkedInvoice']);
    }

    /**
     * Synchroniser le montant de la facture avec la réservation
     */
    public function syncAmountFromReservation(Invoice $invoice, Reservation $reservation): Invoice
    {
        Log::info('🔄 Synchronisation montant facture', [
            'invoice_id' => $invoice->id,
            'old_amount' => $invoice->amount,
            'new_amount' => $reservation->amount
        ]);

        // Ne pas modifier une facture déjà payée
        if ($invoice->status === Invoice::STATUS_PAID) {
            Log::warning('⚠️ Impossible de modifier une facture payée', [
                'invoice_id' => $invoice->id
            ]);
            throw new \Exception("Impossible de modifier une facture payée");
        }

        $invoice->update([
            'amount' => $reservation->amount
        ]);

        Log::info('✅ Montant facture synchronisé', [
            'invoice_id' => $invoice->id,
            'new_amount' => $invoice->amount
        ]);

        return $invoice->fresh();
    }

    /**
     * Générer le PDF de la facture
     */
    public function generatePdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        Log::info('📄 Génération PDF facture', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number
        ]);

        // Charger les relations nécessaires
        $invoice->load(['customer', 'reservation.product']);

        // Préparer les données pour la vue
        $data = [
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'reservation' => $invoice->reservation,
            'company' => $this->getCompanyInfo(),
            'items' => $this->getInvoiceItems($invoice),
        ];

        // Générer le PDF avec DomPDF
        $pdf = Pdf::loadView('invoices.pdf', $data);

        // Options PDF
        $pdf->setPaper('a4', 'portrait');

        // Sauvegarder le PDF sur le serveur
        $pdfPath = "invoices/{$invoice->invoice_number}.pdf";
        $fullPath = storage_path("app/public/{$pdfPath}");

        // Créer le dossier si nécessaire
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0775, true);
        }

        $pdf->save($fullPath);

        // Mettre à jour le chemin dans la base
        $invoice->update(['pdf_path' => $pdfPath]);

        Log::info('✅ PDF généré et sauvegardé', [
            'invoice_id' => $invoice->id,
            'pdf_path' => $pdfPath
        ]);

        return $pdf;
    }

    /**
     * Envoyer la facture par email
     */
    public function sendEmail(Invoice $invoice): void
    {
        Log::info('📧 Envoi email facture', [
            'invoice_id' => $invoice->id,
            'customer_email' => $invoice->customer->email ?? 'N/A'
        ]);

        // Vérifier que le client a un email
        if (!$invoice->customer || !$invoice->customer->email) {
            throw new \Exception("Le client n'a pas d'adresse email");
        }

        // Charger les relations
        $invoice->load(['customer', 'reservation.product']);

        // Générer le PDF
        $pdf = $this->generatePdf($invoice);

        // Préparer les données pour l'email
        $emailData = $invoice->email_data;

        // Envoyer l'email
        Mail::send('invoices.email', $emailData, function ($message) use ($invoice, $pdf) {
            $message->to($invoice->customer->email, $invoice->customer_name)
                ->subject("Facture {$invoice->invoice_number} - CampCameleonX")
                ->attachData($pdf->output(), "{$invoice->invoice_number}.pdf", [
                    'mime' => 'application/pdf',
                ]);
        });

        // Mettre à jour les informations d'envoi
        $invoice->update([
            'sent_at' => Carbon::now(),
            'sent_count' => $invoice->sent_count + 1,
        ]);

        Log::info('✅ Email facture envoyé', [
            'invoice_id' => $invoice->id,
            'to' => $invoice->customer->email,
            'sent_count' => $invoice->sent_count
        ]);
    }

    /**
     * Générer les notes automatiques de la facture
     */
    private function generateInvoiceNotes(Reservation $reservation): string
    {
        $notes = [];

        // Dates de séjour
        if ($reservation->checkin && $reservation->checkout) {
            $notes[] = "Séjour du {$reservation->checkin->format('d/m/Y')} au {$reservation->checkout->format('d/m/Y')}";
        }

        // Produit/Hébergement
        if ($reservation->product) {
            $notes[] = "Hébergement: {$reservation->product->name}";
        }

        // Nombre de personnes
        $guests = [];
        if ($reservation->number_of_adults > 0) {
            $guests[] = "{$reservation->number_of_adults} adulte(s)";
        }
        if ($reservation->number_of_children > 0) {
            $guests[] = "{$reservation->number_of_children} enfant(s)";
        }
        if (!empty($guests)) {
            $notes[] = implode(', ', $guests);
        }

        // Commentaire de la réservation
        if ($reservation->comment) {
            $notes[] = "Note: {$reservation->comment}";
        }

        return implode("\n", $notes);
    }

    private function generateDepositNotes(Reservation $reservation, int $percentage): string
    {
        $notes = ["Acompte de {$percentage}% sur la prestation"];

        if ($reservation->product) {
            $notes[] = "Prestation : {$reservation->product->name}";
        }

        $notes[] = "Montant total : " . number_format($reservation->amount, 2, ',', ' ') . ' €';

        return implode("\n", $notes);
    }

    private function generateBalanceNotes(Invoice $depositInvoice): string
    {
        $reservation = $depositInvoice->reservation;
        $notes = ["Solde restant dû"];
        $notes[] = "Acompte versé : {$depositInvoice->formatted_amount} (facture {$depositInvoice->invoice_number})";

        if ($reservation) {
            $notes[] = "Montant total : " . number_format($reservation->amount, 2, ',', ' ') . ' €';
        }

        return implode("\n", $notes);
    }

    /**
     * Récupérer les informations de l'entreprise pour la facture
     */
    private function getCompanyInfo(): array
    {
        return [
            'name' => config('instance.name'),
            'address' => config('instance.contact.address'),
            'phone' => config('instance.contact.phone'),
            'email' => config('instance.contact.email'),
            'siret' => config('instance.contact.siret'),
        ];
    }
    /**
     * Récupérer les lignes de la facture
     */
    private function getInvoiceItems(Invoice $invoice): array
    {
        $items = [];

        if ($invoice->reservation) {
            $reservation = $invoice->reservation;

            // Charger les produits multiples
            $reservation->load('products.productable');

            // Si la réservation a plusieurs produits (via pivot)
            if ($reservation->products->isNotEmpty()) {
                foreach ($reservation->products as $product) {
                    $quantity = $product->pivot->quantity ?? 1;
                    $unitPrice = $product->price ?? 0;

                    $items[] = [
                        'description' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $unitPrice * $quantity,
                        'details' => $this->getProductDetails($product),
                    ];
                }
            }
            // Sinon, produit unique (ancien système)
            elseif ($reservation->product) {
                $items[] = [
                    'description' => $reservation->product->name ?? 'Séjour',
                    'quantity' => 1,
                    'unit_price' => $invoice->amount,
                    'total' => $invoice->amount,
                    'details' => $this->getItemDetails($reservation),
                ];
            }
            // Aucun produit trouvé
            else {
                $items[] = [
                    'description' => 'Séjour',
                    'quantity' => 1,
                    'unit_price' => $invoice->amount,
                    'total' => $invoice->amount,
                    'details' => $this->getItemDetails($reservation),
                ];
            }
        } else {
            // Facture sans réservation liée
            $items[] = [
                'description' => 'Prestation',
                'quantity' => 1,
                'unit_price' => $invoice->amount,
                'total' => $invoice->amount,
                'details' => null,
            ];
        }

        return $items;
    }

    /**
     * Récupérer les détails d'un produit
     */
    private function getProductDetails(Product $product): ?string
    {
        $details = [];

        // Type de produit
        $typeLabel = $product->typeConfig['singular'] ?? null;
        if ($typeLabel) {
            $details[] = $typeLabel;
        }

        return !empty($details) ? implode(' • ', $details) : null;
    }

    /**
     * Récupérer les détails d'une ligne de facture
     */
    private function getItemDetails(Reservation $reservation): ?string
    {
        $details = [];

        if ($reservation->checkin && $reservation->checkout) {
            $nights = $reservation->checkin->diffInDays($reservation->checkout);
            $details[] = "{$nights} nuit(s)";
        }

        if ($reservation->number_of_adults > 0) {
            $details[] = "{$reservation->number_of_adults} adulte(s)";
        }

        if ($reservation->number_of_children > 0) {
            $details[] = "{$reservation->number_of_children} enfant(s)";
        }

        return !empty($details) ? implode(' • ', $details) : null;
    }

    /**
     * Calculer le total des factures impayées d'un client
     */
    public function getCustomerUnpaidTotal(int $customerId): float
    {
        return Invoice::forCustomer($customerId)
            ->unpaid()
            ->sum('amount');
    }

    /**
     * Marquer les factures en retard automatiquement (commande CRON)
     */
    public function updateOverdueInvoices(): int
    {
        Log::info('🔄 Mise à jour automatique des factures en retard');

        $count = Invoice::where('status', Invoice::STATUS_UNPAID)
            ->where('due_date', '<', Carbon::now())
            ->update(['status' => Invoice::STATUS_OVERDUE]);

        Log::info("✅ {$count} facture(s) marquée(s) comme en retard");

        return $count;
    }
}
