<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Reservation;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReservationProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $currentUser = auth('sanctum')->user();

        if (!$currentUser) {
            throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('Bearer', 'Token d\'authentification requis');
        }

        return DB::transaction(function () use ($data, $operation, $uriVariables, $context, $currentUser) {
            try {
                switch (true) {
                    case $operation instanceof Post:
                        return $this->createReservation($context, $currentUser);

                    case $operation instanceof Put:
                    case $operation instanceof Patch:
                        return $this->updateReservation((int) $uriVariables['id'], $context, $currentUser);

                    case $operation instanceof Delete:
                        return $this->deleteReservation((int) $uriVariables['id']);

                    default:
                        throw new \InvalidArgumentException('Opération non supportée');
                }
            } catch (\Throwable $e) {
                Log::error('Erreur ReservationProcessor', [
                    'operation' => get_class($operation),
                    'message' => $e->getMessage(),
                    'uri_variables' => $uriVariables
                ]);
                throw $e;
            }
        });
    }

    private function normalizeAmount(string|float|int|null $raw): string
    {
        return number_format((float)($raw ?? 0), 2, '.', '');
    }

    /**
     * Créer une nouvelle réservation
     * @param array $context Contexte de la requête API Platform
     * @param \App\Models\User $currentUser Utilisateur authentifié via Sanctum
     * @return Reservation
     */
    private function createReservation(array $context, $currentUser): Reservation
    {
        $payload = $this->getDataFromRequest($context);
        $payload = $this->normalizeAliases($payload);

        $this->validateReservationData($payload);

        $customerId = $this->findOrCreateCustomer($payload['customer_data'] ?? []);
        $payload['amount'] = $this->normalizeAmount($payload['amount'] ?? '0');

        $reservationData = $this->prepareReservationData($payload, $customerId, $currentUser);

        $reservation = Reservation::create($reservationData);
        $reservation->invoice_number = $this->formatInvoiceNumberFromId($reservation->id);
        $reservation->save();
        Log::info("✅ Nouvelle réservation #{$reservation->id} créée", [
            'customer_id' => $customerId,
            'created_by_user_id' => $currentUser->id
        ]);
        if (isset($payload['products']) && is_array($payload['products'])) {
            $this->syncProducts($reservation, $payload['products']);
        }

        return $reservation;
    }

    /**
     * Mettre à jour une réservation existante
     * @param int $id ID de la réservation
     * @param array $context Contexte de la requête API Platform
     * @param \App\Models\User $currentUser Utilisateur authentifié via Sanctum
     * @return Reservation
     */
     private function updateReservation(int $id, array $context, $currentUser): Reservation
    {
        $payload = $this->getDataFromRequest($context);
        $payload = $this->normalizeAliases($payload);
        $reservation = Reservation::findOrFail($id);

        // === CHECK-IN/CHECK-OUT : traitement direct, pas de prepareReservationData ===
        if (isset($payload['status']) && $payload['status'] === 'checked_in') {
            if (!$reservation->canCheckIn()) {
                abort(422, 'Check-in non autorisé');
            }
            $reservation->status = 'checked_in';
            $reservation->actual_checkin = $payload['actual_checkin'] ?? now();
            $reservation->user_id = $reservation->user_id ?? $currentUser->id;
            $reservation->save();
            return $reservation; // Retourner la réservation mise à jour
        }

        if (isset($payload['status']) && $payload['status'] === 'checked_out') {
            if (!$reservation->canCheckOut()) {
                abort(422, 'Check-out non autorisé');
            }

            $checkoutTime = isset($payload['actual_checkout'])
                ? \Carbon\Carbon::parse($payload['actual_checkout'])
                : now();

            //  Valider que checkout >= checkin
            if ($checkoutTime < $reservation->actual_checkin) {
                abort(422, 'Le check-out ne peut pas être avant le check-in');
            }

            $reservation->status = 'checked_out';
            $reservation->actual_checkout = $checkoutTime;
            $reservation->user_id = $reservation->user_id ?? $currentUser->id;
            $reservation->save();
            return $reservation;
        }

        // === UPDATE NORMAL ===
        Log::info("📝 Mise à jour réservation #{$id}");

        if (isset($payload['customer_data'])) {
            $customerId = $this->findOrCreateCustomer($payload['customer_data']);
            $payload['customer_id'] = $customerId;
        }

        $cleanPayload = $this->prepareReservationData(
            $payload,
            $payload['customer_id'] ?? $reservation->customer_id,
            $currentUser
        );

        $reservation->update($cleanPayload);

        // 🆕 AJOUTER CES 3 LIGNES
        if (isset($payload['products']) && is_array($payload['products'])) {
            $this->syncProducts($reservation, $payload['products']);
        }

        Log::info("✅ Réservation #{$id} mise à jour avec succès");
        return $reservation;
    }

    /**
     * 🆕 Synchroniser les produits avec la table pivot
     * 
     * @param Reservation $reservation
     * @param array $products Format: [['product_id' => 1, 'quantity' => 2], ...]
     */
    private function syncProducts(Reservation $reservation, array $products): void
    {
        if (empty($products)) {
            Log::info('⚠️ Aucun produit à synchroniser', [
                'reservation_id' => $reservation->id
            ]);
            return;
        }

        $pivotData = [];
        foreach ($products as $productItem) {
            $productId = $productItem['product_id'] ?? null;
            $quantity = $productItem['quantity'] ?? 1;

            if ($productId) {
                // Assurer que quantity est un entier positif
                $pivotData[$productId] = ['quantity' => max(1, (int)$quantity)];
            }
        }

        if (!empty($pivotData)) {
            $reservation->products()->sync($pivotData);

            Log::info('✅ Produits synchronisés pour réservation', [
                'reservation_id' => $reservation->id,
                'products_count' => count($pivotData),
                'products' => $pivotData
            ]);
        } else {
            Log::warning('⚠️ Aucun produit valide à synchroniser', [
                'reservation_id' => $reservation->id,
                'payload_products' => $products
            ]);
        }
    }

    /**
     * Format d'un numéro de facture basé sur l'ID
     * ex: RES-YYYYMMDD-000123 (id sur 6 chiffres ; date du jour)
     * @param int $id
     * @return string
     */
    private function formatInvoiceNumberFromId(int $id): string
    {
        return 'RES-' . date('Ymd') . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Supprimer une réservation
     * @param int $id ID de la réservation
     * @return void
     */
    private function deleteReservation(int $id): void
    {
        $reservation = Reservation::findOrFail($id);

        Log::info("🗑️ Suppression réservation #{$id}");

        $reservation->delete();
    }

    // ===========================
    // MÉTHODES UTILITAIRES 
    // ===========================

    /**
     * Trouver ou créer un client à partir des données fournies
     * Gère aussi les consentements RGPD
     * @param array $customerData
     * @return int ID du client
     * @throws \InvalidArgumentException Si l'email est manquant
     * @throws \Exception Pour autres erreurs
     */
    private function findOrCreateCustomer(array $customerData): int
    {
        $email = $customerData['email'] ?? null;

        if (!$email) {
            throw new \InvalidArgumentException('Email client requis');
        }

        // Récupérer l'IP pour traçabilité RGPD
        $request = app(\Illuminate\Http\Request::class);
        $ipAddress = $request->ip();

        $customer = Customer::firstOrNew(['email' => $email]);

        $customer->fill([
            'name' => $customerData['name'] ?? $customer->name ?? 'Client',
            'last_name' => $customerData['last_name'] ?? $customer->last_name ?? '',
            'phone' => $customerData['phone'] ?? $customer->phone ?? null,
            'address' => $customerData['address'] ?? $customer->address ?? null,
            'city' => $customerData['city'] ?? $customer->city ?? null,
            'postal_code' => $customerData['postal_code'] ?? $customer->postal_code ?? null,
            'country' => $customerData['country'] ?? $customer->country ?? null,

            // ✅ AJOUT RGPD : Consentements (implicites lors d'une réservation)
            'gdpr_consent' => true, // Accepté via validation du formulaire
            'gdpr_consent_at' => now(),
            'gdpr_consent_ip' => $ipAddress,

            // Newsletter uniquement si explicitement demandé
            'newsletter_consent' => $customerData['newsletter_consent'] ?? false,
            'newsletter_consent_at' => ($customerData['newsletter_consent'] ?? false) ? now() : null,
        ]);

        if (!$customer->exists || $customer->isDirty()) {
            $customer->save();
            Log::info($customer->wasRecentlyCreated ? '✅ Nouveau customer créé avec consentements RGPD' : '✅ Customer mis à jour', [
                'id' => $customer->id,
                'email' => $customer->email,
                'gdpr_consent' => true
            ]);
        }

        return $customer->id;
    }

    /**
     * Normaliser les alias dans le payload pour uniformiser les données
     * @param array $payload
     * @return array
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    private function normalizeAliases(array $payload): array
    {
        // new_customer / customer -> customer_data
        if (!isset($payload['customer_data'])) {
            if (isset($payload['new_customer']) && is_array($payload['new_customer'])) {
                $payload['customer_data'] = $payload['new_customer'];
            } elseif (isset($payload['customer']) && is_array($payload['customer'])) {
                $payload['customer_data'] = $payload['customer'];
            }
        }

        // guests -> number_of_adults (fallback simple si tu n'as pas le split adultes/enfants)
        if (!isset($payload['number_of_adults']) && isset($payload['guests'])) {
            $payload['number_of_adults'] = max(1, (int) $payload['guests']);
        }

        // amount: string attendu par ton validateur
        if (isset($payload['amount'])) {
            $payload['amount'] = (string) $this->normalizeAmount($payload['amount']);
        }

        return $payload;
    }

    /**
     * Valider les données de la réservation
     * @param array $payload
     * @return void
     * @throws ValidationException Si la validation échoue
     */
    private function validateReservationData(array $payload): void
    {
        $validator = Validator::make($payload, [
            'checkin' => 'required|date',
            'checkout' => 'required|date|after:checkin',
            'amount' => 'required|string', // String car API Platform l'attend ainsi
            'customer_data' => 'required|array',
            'customer_data.email' => 'required|email',
            'customer_data.name' => 'required|string|max:255',
            'customer_data.last_name' => 'required|string|max:255',
            'number_of_adults' => 'integer|min:1',
            'number_of_children' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            Log::warning('❌ Validation échouée', ['errors' => $validator->errors()]);
            throw new ValidationException($validator);
        }
    }

    /**
     * Préparer les données de la réservation pour création ou mise à jour
     * @param array $payload Données brutes de la requête
     * @param int $customerId ID du client
     * @param \App\Models\User $currentUser Utilisateur authentifié via Sanctum
     * @return array Données nettoyées prêtes pour create/update
     */
    private function prepareReservationData(array $payload, int $customerId, $currentUser): array
    {
        return [
            'customer_id' => $customerId,
            'date' => $payload['checkin'], // ✅ Date de référence de la réservation = date de checkin
            'checkin' => $payload['checkin'],
            'checkout' => $payload['checkout'],
            'amount' => $payload['amount'],
            'comment' => $payload['comment'] ?? null,
            'number_of_adults' => $payload['number_of_adults'] ?? 2,
            'number_of_children' => $payload['number_of_children'] ?? 0,
            'booking_source' => $payload['booking_source'] ?? 'admin',
            'payment_status' => $payload['payment_status'] ?? 'pending',
            'payment_method' => $payload['payment_method'] ?? null,
            'status' => $payload['status'] ?? 'confirmed',
            'product_id' => $payload['product_id'] ?? null,
            'product_type'        => Product::class,
            'user_id' => $currentUser->id, // Utilisateur Sanctum authentifié
        ];
    }

    /**
     * Extraire les données de la requête depuis le contexte API Platform
     * @param array $context
     * @return array
     * @throws \InvalidArgumentException Si les données ne peuvent pas être extraites
     */
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

    /**
     * Générer un numéro de facture unique basé sur la date et un compteur quotidien
     * Format : RES-YYYYMMDD-XXXX (ex: RES-20251027-0001)
     * @return string
     */
    public function generateInvoiceNumber(): string
    {
        // Format : RES-YYYYMMDD-XXXX (ex: RES-20251027-0001)
        $prefix = 'RES-' . date('Ymd') . '-';

        // Compter les réservations du jour pour avoir un numéro séquentiel
        $today = date('Y-m-d');
        $count = Reservation::whereDate('created_at', $today)->count();

        // Incrémenter et formatter sur 4 chiffres
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $invoiceNumber = $prefix . $sequence;

        // Vérifier l'unicité (au cas où il y aurait des suppressions)
        while (Reservation::where('invoice_number', $invoiceNumber)->exists()) {
            $count++;
            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = $prefix . $sequence;
        }

        return $invoiceNumber;
    }
}
