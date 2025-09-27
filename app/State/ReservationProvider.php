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
                'customer_name' => $reservation->customer?->name ?? 'N/A'
            ]);

            // ✅ TRANSFORMATION MANUELLE POUR ÉVITER LES IRIs
            return $this->transformReservation($reservation);
        }

        return null;
    }

    /**
     * ✅ TRANSFORME LA RÉSERVATION POUR INCLURE LES OBJETS COMPLETS
     */
    private function transformReservation($reservation)
    {
        $data = $reservation->toArray();
        
        // Forcer l'inclusion des relations complètes au lieu des IRIs
        if ($reservation->customer) {
            $data['customer'] = $reservation->customer->toArray();
        }
        
        if ($reservation->user) {
            $data['user'] = $reservation->user->toArray();
        }
        
        if ($reservation->product) {
            $data['product'] = $reservation->product->toArray();
        }

        return (object) $data;
    }
}