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

    private function createReservation(array $context, $currentUser): Reservation
    {
        $payload = $this->getDataFromRequest($context);
        $this->validateReservationData($payload);

        $customerId = $this->findOrCreateCustomer($payload['customer_data'] ?? []);
        // normaliser amount côté serveur au passage (voir point 2)
        $payload['amount'] = $this->normalizeAmount($payload['amount'] ?? '0');

        $reservationData = $this->prepareReservationData($payload, $customerId, $currentUser);

        // 1) on crée sans invoice_number
        $reservation = Reservation::create($reservationData);

        // 2) on dérive un numéro 100% unique depuis l'ID
        $invoiceNumber = $this->formatInvoiceNumberFromId($reservation->id);
        $reservation->invoice_number = $invoiceNumber;
        $reservation->save();

        return $reservation;
    }

    private function formatInvoiceNumberFromId(int $id): string
    {
        // ex: RES-YYYYMMDD-000123 (id sur 6 chiffres ; date du jour)
        return 'RES-' . date('Ymd') . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
    }

    private function updateReservation(int $id, array $context, $currentUser): Reservation
    {
        $payload = $this->getDataFromRequest($context);
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
            return $reservation; // ⬅️ RETURN ICI, skip prepareReservationData
        }

        if (isset($payload['status']) && $payload['status'] === 'checked_out') {
            if (!$reservation->canCheckOut()) {
                abort(422, 'Check-out non autorisé');
            }
            $reservation->status = 'checked_out';
            $reservation->actual_checkout = $payload['actual_checkout'] ?? now();
            $reservation->user_id = $reservation->user_id ?? $currentUser->id;
            $reservation->save();
            return $reservation; // ⬅️ RETURN ICI, skip prepareReservationData
        }

        // === UPDATE NORMAL : ton code existant ===
        Log::info("📝 Mise à jour réservation #{$id}");

        if (isset($payload['customer_data'])) {
            $customerId = $this->findOrCreateCustomer($payload['customer_data']);
            $payload['customer_id'] = $customerId;
        }

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
    // MÉTHODES UTILITAIRES 
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
            'product_type'        => Product::class,
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

    private function generateInvoiceNumber(): string
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
