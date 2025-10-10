<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

class ReservationProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Si c'est une collection (liste des réservations)
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            Log::info('📋 Récupération liste réservations avec relations');
            
            $reservations = Reservation::with([
                'customer:id,name,last_name,email,phone,address',
                'user:id,name,email', 
                'product' => function($query) {
                    $query->select('id', 'name', 'description', 'price', 'productable_type', 'productable_id');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

            // ✅ TRANSFORMATION MANUELLE POUR ÉVITER LES IRIs
            $reservations->getCollection()->transform(function ($reservation) {
                return $this->transformReservation($reservation);
            });

            return $reservations;
        }

        // Si c'est un item spécifique (détail réservation)
        if ($operation instanceof \ApiPlatform\Metadata\Get) {
            $id = $uriVariables['id'];
            Log::info("📋 Récupération réservation #{$id} avec toutes les relations");
            
            $reservation = Reservation::with([
                'customer' => function($query) {
                    $query->select('id', 'name', 'last_name', 'email', 'phone', 'address', 'created_at');
                },
                'user' => function($query) {
                    $query->select('id', 'name', 'email', 'created_at');
                },
                'product' => function($query) {
                    $query->select('id', 'name', 'description', 'price', 'productable_type', 'productable_id');
                },
                // ✅ AJOUTER : Charger tous les produits liés via la table pivot
                'products' => function($query) {
                    $query->select('products.id', 'name', 'price', 'productable_type', 'productable_id');
                }
            ])->find($id);

            if (!$reservation) {
                Log::warning("⚠️ Réservation #{$id} introuvable");
                return null;
            }

            Log::info("✅ Réservation #{$id} chargée avec relations", [
                'has_customer' => !is_null($reservation->customer),
                'has_user' => !is_null($reservation->user),
                'has_product' => !is_null($reservation->product),
                'products_count' => $reservation->products->count(),
                'customer_name' => $reservation->customer?->name ?? 'N/A'
            ]);

            return $this->transformReservation($reservation);
        }

        return null;
    }

    private function transformReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'user_id' => $reservation->user_id,
            'product_id' => $reservation->product_id,
            'date' => $reservation->date?->format('Y-m-d'),
            'checkin' => $reservation->checkin?->format('Y-m-d H:i:s'),
            'checkout' => $reservation->checkout?->format('Y-m-d H:i:s'),
            'actual_checkin' => $reservation->actual_checkin?->format('Y-m-d H:i:s'),
            'actual_checkout' => $reservation->actual_checkout?->format('Y-m-d H:i:s'),
            'amount' => $reservation->amount,
            'status' => $reservation->status,
            'payment_status' => $reservation->payment_status,
            'payment_method' => $reservation->payment_method,
            'booking_source' => $reservation->booking_source,
            'number_of_adults' => $reservation->number_of_adults,
            'number_of_children' => $reservation->number_of_children,
            'comment' => $reservation->comment,
            'invoice_number' => $reservation->invoice_number,
            'quote_reference' => $reservation->quote_reference,
            'stripe_session_id' => $reservation->stripe_session_id,
            'stripe_payment_intent' => $reservation->stripe_payment_intent,
            'parent_reservation_id' => $reservation->parent_reservation_id,
            'created_at' => $reservation->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $reservation->updated_at?->format('Y-m-d H:i:s'),
            
            // Relations transformées
            'customer' => $reservation->customer ? [
                'id' => $reservation->customer->id,
                'name' => $reservation->customer->name,
                'last_name' => $reservation->customer->last_name,
                'email' => $reservation->customer->email,
                'phone' => $reservation->customer->phone,
                'address' => $reservation->customer->address,
            ] : null,
            
            'user' => $reservation->user ? [
                'id' => $reservation->user->id,
                'name' => $reservation->user->name,
                'email' => $reservation->user->email,
            ] : null,
            
            'product' => $reservation->product ? [
                'id' => $reservation->product->id,
                'name' => $reservation->product->name,
                'description' => $reservation->product->description,
                'price' => $reservation->product->price,
                'productable_type' => $reservation->product->productable_type,
            ] : null,

            //  Transformer les produits multiples avec quantités
            'products' => $reservation->products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'productable_type' => $product->productable_type,
                    'quantity' => $product->pivot->quantity ?? 1,
                ];
            })->toArray(),

            // Attributs calculés
            'customer_name' => $reservation->customer_name,
            'product_name' => $reservation->product_name,
            'nights_count' => $reservation->nights_count,
            'total_guests' => $reservation->total_guests,
        ];
    }
}