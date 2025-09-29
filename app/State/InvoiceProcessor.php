<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class InvoiceProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // ✅ SÉCURITÉ SANCTUM
        $currentUser = auth('sanctum')->user();

        if (!$currentUser) {
            throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException(
                'Bearer', 
                'Token d\'authentification requis'
            );
        }

        $request = app(Request::class);
        $path = $request->path();

        Log::info('⚙️ InvoiceProcessor appelé', [
            'path' => $path,
            'operation' => get_class($operation),
            'method' => $request->method(),
            'user_id' => $currentUser->id
        ]);

        return DB::transaction(function () use ($operation, $uriVariables, $context, $currentUser, $path, $request) {
            try {
                // ===========================
                // ACTIONS MÉTIER SPÉCIALES
                // ===========================
                
                if (str_contains($path, '/mark-paid') && isset($uriVariables['id'])) {
                    return $this->markInvoiceAsPaid((int) $uriVariables['id'], $context);
                }

                if (str_contains($path, '/send-email') && isset($uriVariables['id'])) {
                    return $this->sendInvoiceByEmail((int) $uriVariables['id']);
                }

                // ===========================
                // CRUD STANDARD
                // ===========================

                switch (true) {
                    case $operation instanceof Post:
                        return $this->createInvoice($context, $currentUser);

                    case $operation instanceof Put:
                        return $this->updateInvoice((int) $uriVariables['id'], $context, $currentUser);

                    case $operation instanceof Delete:
                        return $this->deleteInvoice((int) $uriVariables['id']);

                    default:
                        throw new \InvalidArgumentException('Opération non supportée');
                }
            } catch (\Throwable $e) {
                Log::error('Erreur InvoiceProcessor', [
                    'operation' => get_class($operation),
                    'message' => $e->getMessage(),
                    'uri_variables' => $uriVariables
                ]);
                throw $e;
            }
        });
    }

    // ===========================
    // MÉTHODE: CRÉER FACTURE
    // ===========================
    private function createInvoice(array $context, $currentUser): Invoice
    {
        $payload = $this->getDataFromRequest($context);
        
        Log::info('➕ Création nouvelle facture', [
            'customer_id' => $payload['customer_id'] ?? null,
            'reservation_id' => $payload['reservation_id'] ?? null,
            'amount' => $payload['amount'] ?? null
        ]);

        // Validation des données
        $this->validateInvoiceData($payload);

        // Vérifier qu'une facture n'existe pas déjà pour cette réservation
        if (isset($payload['reservation_id']) && Invoice::existsForReservation($payload['reservation_id'])) {
            throw new \InvalidArgumentException(
                'Une facture existe déjà pour cette réservation'
            );
        }

        // Préparer les données
        $invoiceData = $this->prepareInvoiceData($payload);

        // Créer la facture
        $invoice = Invoice::create($invoiceData);

        Log::info('✅ Facture créée', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $invoice->amount
        ]);

        // Charger les relations pour le retour
        $invoice->load(['customer', 'reservation']);

        return $invoice;
    }

    // ===========================
    // MÉTHODE: MODIFIER FACTURE
    // ===========================
    private function updateInvoice(int $id, array $context, $currentUser): Invoice
    {
        $payload = $this->getDataFromRequest($context);
        $invoice = Invoice::findOrFail($id);

        Log::info("📝 Mise à jour facture #{$id}", [
            'invoice_number' => $invoice->invoice_number,
            'current_status' => $invoice->status
        ]);

        // Vérifier si la facture peut être modifiée
        if (!$invoice->canBeModified()) {
            throw new \InvalidArgumentException(
                'Cette facture ne peut pas être modifiée (déjà payée)'
            );
        }

        // Validation des données
        $this->validateInvoiceData($payload, $invoice->id);

        // Préparer les données de mise à jour
        $updateData = $this->prepareInvoiceData($payload, true);

        // Mise à jour
        $invoice->update($updateData);

        Log::info("✅ Facture #{$id} mise à jour");

        // Recharger les relations
        $invoice->load(['customer', 'reservation']);

        return $invoice;
    }

    // ===========================
    // MÉTHODE: SUPPRIMER FACTURE
    // ===========================
    private function deleteInvoice(int $id): void
    {
        $invoice = Invoice::findOrFail($id);

        Log::info("🗑️ Suppression facture #{$id}", [
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status
        ]);

        // Vérifier qu'on peut supprimer (pas payée de préférence)
        if ($invoice->status === Invoice::STATUS_PAID) {
            Log::warning("⚠️ Tentative de suppression d'une facture payée #{$id}");
            // On permet quand même la suppression mais on log un warning
        }

        $invoice->delete();

        Log::info("✅ Facture #{$id} supprimée");
    }

    // ===========================
    // ACTION: MARQUER COMME PAYÉE
    // ===========================
    private function markInvoiceAsPaid(int $id, array $context): Invoice
    {
        $invoice = Invoice::findOrFail($id);
        $payload = $this->getDataFromRequest($context);

        Log::info("💳 Marquage facture #{$id} comme payée", [
            'invoice_number' => $invoice->invoice_number,
            'current_status' => $invoice->status
        ]);

        // Vérifier qu'on peut payer
        if (!$invoice->canBePaid()) {
            throw new \InvalidArgumentException(
                'Cette facture ne peut pas être payée (déjà payée ou annulée)'
            );
        }

        // Méthode de paiement
        $paymentMethod = $payload['payment_method'] ?? Invoice::PAYMENT_METHOD_CARD;

        // Valider la méthode de paiement
        $validMethods = [
            Invoice::PAYMENT_METHOD_CARD,
            Invoice::PAYMENT_METHOD_CASH,
            Invoice::PAYMENT_METHOD_TRANSFER,
            Invoice::PAYMENT_METHOD_CHECK,
        ];

        if (!in_array($paymentMethod, $validMethods)) {
            throw new \InvalidArgumentException(
                'Méthode de paiement invalide: ' . $paymentMethod
            );
        }

        // Marquer comme payée
        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'payment_method' => $paymentMethod,
            'payment_date' => Carbon::now(),
        ]);

        // Mettre à jour le statut de paiement de la réservation liée
        if ($invoice->reservation) {
            $invoice->reservation->update([
                'payment_status' => 'paid',
            ]);

            Log::info("✅ Réservation #{$invoice->reservation_id} marquée comme payée");
        }

        Log::info("✅ Facture #{$id} marquée comme payée", [
            'payment_method' => $paymentMethod,
            'payment_date' => $invoice->payment_date->toISOString()
        ]);

        $invoice->load(['customer', 'reservation']);

        return $invoice;
    }

    // ===========================
    // ACTION: ENVOYER PAR EMAIL
    // ===========================
    private function sendInvoiceByEmail(int $id): array
    {
        $invoice = Invoice::with(['customer', 'reservation'])->findOrFail($id);

        Log::info("📧 Envoi facture #{$id} par email", [
            'invoice_number' => $invoice->invoice_number,
            'customer_email' => $invoice->customer->email ?? 'N/A'
        ]);

        // Vérifier que le client a un email
        if (!$invoice->customer || !$invoice->customer->email) {
            throw new \InvalidArgumentException(
                'Le client n\'a pas d\'adresse email'
            );
        }

        try {
            // Préparer les données pour l'email
            $emailData = $invoice->email_data;

            // TODO: Envoyer l'email réel avec Mail facade
            // Mail::send('invoices.email', $emailData, function ($message) use ($invoice) {
            //     $message->to($invoice->customer->email)
            //         ->subject("Facture {$invoice->invoice_number} - CampCameleonX");
            // });

            // Pour l'instant, on simule l'envoi
            Log::info('📧 Email facture envoyé (simulé)', [
                'to' => $invoice->customer->email,
                'invoice_number' => $invoice->invoice_number
            ]);

            // Mettre à jour la date d'envoi et le compteur
            $invoice->update([
                'sent_at' => Carbon::now(),
                'sent_count' => $invoice->sent_count + 1,
            ]);

            return [
                'success' => true,
                'message' => 'Facture envoyée par email avec succès',
                'sent_to' => $invoice->customer->email,
                'sent_at' => $invoice->sent_at->toISOString(),
                'sent_count' => $invoice->sent_count,
            ];

        } catch (\Throwable $e) {
            Log::error('Erreur envoi email facture', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException(
                'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()
            );
        }
    }

    // ===========================
    // VALIDATION
    // ===========================
    private function validateInvoiceData(array $payload, ?int $invoiceId = null): void
    {
        $errors = [];

        // Customer requis
        if (empty($payload['customer_id'])) {
            $errors['customer_id'] = 'Le client est requis';
        } else {
            // Vérifier que le client existe
            if (!Customer::find($payload['customer_id'])) {
                $errors['customer_id'] = 'Le client spécifié n\'existe pas';
            }
        }

        // Montant requis et positif
        if (!isset($payload['amount'])) {
            $errors['amount'] = 'Le montant est requis';
        } elseif ($payload['amount'] <= 0) {
            $errors['amount'] = 'Le montant doit être positif';
        }

        // Dates valides
        if (isset($payload['issue_date']) && !strtotime($payload['issue_date'])) {
            $errors['issue_date'] = 'Date d\'émission invalide';
        }

        if (isset($payload['due_date']) && !strtotime($payload['due_date'])) {
            $errors['due_date'] = 'Date d\'échéance invalide';
        }

        // Due date après issue date
        if (isset($payload['issue_date']) && isset($payload['due_date'])) {
            $issueDate = Carbon::parse($payload['issue_date']);
            $dueDate = Carbon::parse($payload['due_date']);
            
            if ($dueDate->lessThan($issueDate)) {
                $errors['due_date'] = 'La date d\'échéance doit être après la date d\'émission';
            }
        }

        // Si une réservation est spécifiée, vérifier qu'elle existe
        if (isset($payload['reservation_id']) && $payload['reservation_id']) {
            if (!Reservation::find($payload['reservation_id'])) {
                $errors['reservation_id'] = 'La réservation spécifiée n\'existe pas';
            }
        }

        // Statut valide
        if (isset($payload['status'])) {
            $validStatuses = [
                Invoice::STATUS_PAID,
                Invoice::STATUS_UNPAID,
                Invoice::STATUS_OVERDUE,
                Invoice::STATUS_CANCELLED,
            ];

            if (!in_array($payload['status'], $validStatuses)) {
                $errors['status'] = 'Statut invalide';
            }
        }

        if (!empty($errors)) {
            Log::warning('❌ Validation facture échouée', ['errors' => $errors]);
            throw ValidationException::withMessages($errors);
        }
    }

    // ===========================
    // PRÉPARATION DES DONNÉES
    // ===========================
    private function prepareInvoiceData(array $payload, bool $isUpdate = false): array
    {
        $data = [
            'customer_id' => $payload['customer_id'],
            'amount' => $this->normalizeAmount($payload['amount']),
            'status' => $payload['status'] ?? Invoice::STATUS_UNPAID,
        ];

        // Champs optionnels
        if (isset($payload['reservation_id'])) {
            $data['reservation_id'] = $payload['reservation_id'];
        }

        if (isset($payload['issue_date'])) {
            $data['issue_date'] = Carbon::parse($payload['issue_date']);
        }

        if (isset($payload['due_date'])) {
            $data['due_date'] = Carbon::parse($payload['due_date']);
        }

        if (isset($payload['notes'])) {
            $data['notes'] = $payload['notes'];
        }

        if (isset($payload['payment_method'])) {
            $data['payment_method'] = $payload['payment_method'];
        }

        // Numéro de facture (seulement création)
        if (!$isUpdate && isset($payload['invoice_number'])) {
            $data['invoice_number'] = $payload['invoice_number'];
        }

        return $data;
    }

    // ===========================
    // UTILITAIRES
    // ===========================
    private function normalizeAmount(string|float|int|null $raw): string
    {
        return number_format((float)($raw ?? 0), 2, '.', '');
    }

    private function getDataFromRequest(array $context): array
    {
        if (isset($context['request']) && $context['request'] instanceof Request) {
            $request = $context['request'];
            $content = $request->getContent();

            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        $request = app(Request::class);
        if ($request) {
            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        throw new \InvalidArgumentException('Données de requête introuvables');
    }
}