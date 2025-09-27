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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReservationProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // ✅ SÉCURITÉ SANCTUM (comme votre UserProcessor)
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

    private function createReservation(array $context, $currentUser): Reservation
    {
        $payload = $this->getDataFromRequest($context);

        Log::info('💾 Création réservation admin', [
            'customer_email' => $payload['customer_data']['email'] ?? 'N/A',
            'amount' => $payload['amount'] ?? 0,
            'checkin' => $payload['checkin'] ?? null
        ]);

        // 1. Validation de base
        $this->validateReservationData($payload);

        // 2. Gérer le customer automatiquement
        $customerId = $this->findOrCreateCustomer($payload['customer_data'] ?? []);

        // 3. Préparer les données de réservation
        $reservationData = $this->prepareReservationData($payload, $customerId, $currentUser);

        // 4. Créer la réservation
        $reservation = Reservation::create($reservationData);

        Log::info('✅ Réservation créée avec succès', [
            'id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'amount' => $reservation->amount
        ]);

        return $reservation;
    }

    private function updateReservation(int $id, array $context, $currentUser): Reservation
    {
        $payload = $this->getDataFromRequest($context);
        $reservation = Reservation::findOrFail($id);

        Log::info("📝 Mise à jour réservation #{$id}");

        // Si customer_data est fourni, créer/mettre à jour le customer
        if (isset($payload['customer_data'])) {
            $customerId = $this->findOrCreateCustomer($payload['customer_data']);
            $payload['customer_id'] = $customerId;
        }

        // Nettoyer les données (enlever customer_data du payload principal)
        $cleanPayload = $this->prepareReservationData($payload, $payload['customer_id'] ?? $reservation->customer_id, $currentUser);

        $reservation->update($cleanPayload);

        Log::info("✅ Réservation #{$id} mise à jour");

        return $reservation;
    }

    private function deleteReservation(int $id): void
    {
        $reservation = Reservation::findOrFail($id);
        
        Log::info("🗑️ Suppression réservation #{$id}");
        
        $reservation->delete();
    }

    // ===========================
    // MÉTHODES UTILITAIRES (reprises de QuoteRequestProcessor)
    // ===========================

    private function findOrCreateCustomer(array $customerData): int
    {
        $email = $customerData['email'] ?? null;

        if (!$email) {
            throw new \InvalidArgumentException('Email client requis');
        }

        $customer = Customer::firstOrNew(['email' => $email]);

        $customer->fill([
            'name' => $customerData['name'] ?? $customer->name ?? 'Client',
            'last_name' => $customerData['last_name'] ?? $customer->last_name ?? '',
            'phone' => $customerData['phone'] ?? $customer->phone ?? null,
            'address' => $customerData['address'] ?? $customer->address ?? null,
            'city' => $customerData['city'] ?? $customer->city ?? null,
            'postal_code' => $customerData['postal_code'] ?? $customer->postal_code ?? null,
            'country' => $customerData['country'] ?? $customer->country ?? null,
        ]);

        if (!$customer->exists || $customer->isDirty()) {
            $customer->save();
            Log::info($customer->wasRecentlyCreated ? '✅ Nouveau customer créé' : '✅ Customer mis à jour', [
                'id' => $customer->id,
                'email' => $customer->email
            ]);
        }

        return $customer->id;
    }

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
            'user_id' => $currentUser->id, // Utilisateur Sanctum authentifié
        ];
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