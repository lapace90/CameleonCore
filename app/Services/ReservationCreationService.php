<?php

namespace App\Services;

use App\Models\QuoteRequest;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReservationCreationService
{
    /**
     * Créer une réservation depuis un devis payé
     */
    public function createReservationFromQuote(QuoteRequest $quote, array $paymentData = []): Reservation
    {
        Log::info('🏨 Création réservation depuis devis', [
            'quote_id' => $quote->id,
            'quote_reference' => $quote->quote_reference,
            'payment_amount' => $paymentData['amount'] ?? $quote->total_amount,
        ]);

        return DB::transaction(function () use ($quote, $paymentData) {
            // Trouver le produit principal (hébergement) dans les produits sélectionnés
            $mainProduct = $this->findMainProduct($quote);

            if (!$mainProduct) {
                Log::warning('⚠️ Aucun hébergement trouvé');
                // Prendre le premier produit des items
                $mainProduct = $quote->items->first()?->product;
            }

            if (!$mainProduct) {
                throw new \Exception("Aucun produit trouvé dans le devis");
            }

            // Créer la réservation principale
            $reservation = $this->createMainReservation($quote, $mainProduct, $paymentData);

            // Synchroniser TOUS les produits
            $this->syncProductsFromQuote($reservation, $quote);

            // Marquer le devis comme converti
            $quote->update([
                'converted_to_reservation_at' => now(),
                'main_reservation_id' => $reservation->id,
                'stripe_session_id' => $paymentData['session_id'] ?? null,
                'payment_intent_id' => $paymentData['stripe_payment_intent'] ?? null
            ]);

            Log::info('✅ Réservation créée avec succès', [
                'reservation_id' => $reservation->id,
                'products_count' => $reservation->products()->count()
            ]);

            return $reservation;
        });
    }

    /**
     * 🆕 Synchroniser les produits du devis vers la table pivot
     */
    private function syncProductsFromQuote(Reservation $reservation, QuoteRequest $quote): void
    {
        // Charger les items si pas déjà chargés
        if (!$quote->relationLoaded('items')) {
            $quote->load('items');
        }

        if ($quote->items->isEmpty()) {
            Log::warning('⚠️ Aucun produit à synchroniser', [
                'quote_id' => $quote->id,
                'quote_reference' => $quote->quote_reference
            ]);
            return;
        }

        // Utiliser directement les items avec leurs quantités
        $pivotData = [];
        foreach ($quote->items as $item) {
            $pivotData[$item->product_id] = ['quantity' => $item->quantity];
        }

        $reservation->products()->sync($pivotData);

        Log::info('✅ Produits synchronisés vers product_reservation', [
            'reservation_id' => $reservation->id,
            'quote_id' => $quote->id,
            'products_count' => count($pivotData),
            'products' => $pivotData
        ]);
    }
    /**
     * Créer la réservation principale
     */
    private function createMainReservation(QuoteRequest $quote, Product $mainProduct, array $paymentData): Reservation
    {
        return Reservation::create([
            'customer_id' => $quote->customer_id,
            'product_id' => $mainProduct->id,
            'product_type' => $mainProduct->productable_type ?? 'App\\Models\\Product', // Type polymorphe
            'date' => now(),
            'checkin' => $quote->checkin_date,
            'checkout' => $quote->checkout_date,
            'amount' => $paymentData['amount'] ?? $quote->total_amount,
            'booking_source' => 'website',
            'payment_status' => 'paid',
            'number_of_adults' => $quote->guests ?? 2,
            'number_of_children' => 0, // Par défaut, à venir du devis
            'comment' => $quote->message,
            'status' => 'confirmed',
            'invoice_number' => $this->generateInvoiceNumber(),
            'quote_reference' => $quote->quote_reference,
            'stripe_session_id' => $paymentData['session_id'] ?? null,
            'stripe_payment_intent' => $paymentData['stripe_payment_intent'] ?? null,
            'payment_method' => 'stripe_card',
        ]);
    }

    /**
     * Trouver le produit principal (hébergement) dans les produits sélectionnés
     */
    private function findMainProduct(QuoteRequest $quote): ?Product
    {
        // Chercher le premier Room dans les items
        $roomItem = $quote->items()
            ->whereHas('product', function ($q) {
                $q->where('productable_type', 'App\\Models\\Room');
            })
            ->first();

        $product = $roomItem?->product;

        Log::info('🔍 Recherche produit principal', [
            'quote_id' => $quote->id,
            'room_found' => $product ? $product->id : null,
            'room_name' => $product ? $product->name : null
        ]);

        return $product;
    }

    /**
     * Générer un numéro de facture unique
     */
    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        // Format: INV-YYYY-MM-XXXX
        $lastInvoice = Reservation::where('invoice_number', 'like', "INV-{$year}-{$month}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extraire le numéro de la fin
            $parts = explode('-', $lastInvoice->invoice_number);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('INV-%s-%s-%04d', $year, $month, $newNumber);
    }

    /**
     * Obtenir les détails des produits sélectionnés pour analyse
     */
    public function getQuoteProductsDetails(QuoteRequest $quote): array
    {
        if ($quote->items->isEmpty()) {
            return [];
        }

        return $quote->items->map(function ($item) {
            $product = $item->product;
            return [
                'id' => $product->id,
                'name' => $product->name,
                'type' => class_basename($product->productable_type ?? 'Unknown'),
                'full_type' => $product->productable_type,
                'price' => $product->price,
                'quantity' => $item->quantity,
                'is_room' => $product->productable_type === 'App\\Models\\Room',
                'is_activity' => $product->productable_type === 'App\\Models\\Activity',
                'is_menu' => $product->productable_type === 'App\\Models\\Menu',
            ];
        })->toArray();
    }

    /**
     * Debug : analyser les produits d'un devis
     */
    public function analyzeQuoteProducts(QuoteRequest $quote): array
    {
        $details = $this->getQuoteProductsDetails($quote);

        $analysis = [
            'total_products' => count($details),
            'rooms' => array_filter($details, fn($p) => $p['is_room']),
            'activities' => array_filter($details, fn($p) => $p['is_activity']),
            'menus' => array_filter($details, fn($p) => $p['is_menu']),
            'others' => array_filter($details, fn($p) => !$p['is_room'] && !$p['is_activity'] && !$p['is_menu']),
        ];

        $analysis['summary'] = [
            'rooms_count' => count($analysis['rooms']),
            'activities_count' => count($analysis['activities']),
            'menus_count' => count($analysis['menus']),
            'others_count' => count($analysis['others']),
        ];

        return $analysis;
    }
}
