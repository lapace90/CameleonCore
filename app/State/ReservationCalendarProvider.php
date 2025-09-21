<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReservationCalendarProvider implements ProviderInterface
{
    /**
     * Fournir les données de réservations pour FullCalendar
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $context['request'] ?? null;
        
        // Récupérer les paramètres de filtrage du calendrier
        $startDate = $request?->query->get('start');
        $endDate = $request?->query->get('end');
        $view = $request?->query->get('view', 'month');

        Log::info('📅 Requête événements calendrier', [
            'start' => $startDate,
            'end' => $endDate,
            'view' => $view
        ]);

        // Cache key basé sur les paramètres
        $cacheKey = "calendar_events_{$startDate}_{$endDate}_{$view}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($startDate, $endDate) {
            return $this->fetchReservationEvents($startDate, $endDate);
        });
    }

    /**
     * Récupérer les événements de réservation
     */
    private function fetchReservationEvents(?string $startDate, ?string $endDate): array
    {
        $query = Reservation::with(['customer', 'product'])
            ->where('status', '!=', 'cancelled');

        // Filtrage par dates si spécifiées
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('checkin', [$start, $end])
                  ->orWhereBetween('checkout', [$start, $end])
                  ->orWhere(function ($subQ) use ($start, $end) {
                      $subQ->where('checkin', '<=', $start)
                           ->where('checkout', '>=', $end);
                  });
            });
        }

        $reservations = $query->orderBy('checkin')->get();

        Log::info('📊 Réservations trouvées', [
            'count' => $reservations->count(),
            'date_range' => "{$startDate} - {$endDate}"
        ]);

        return $reservations->map(function ($reservation) {
            return $this->transformReservationToEvent($reservation);
        })->toArray();
    }

    /**
     * Transformer une réservation en événement FullCalendar
     */
    private function transformReservationToEvent(Reservation $reservation): array
    {
        $customer = $reservation->customer;
        $product = $reservation->product;

        // Déterminer la couleur selon le type de produit et statut
        $color = $this->getEventColor($reservation);

        // Construire le titre de l'événement
        $title = $this->buildEventTitle($reservation, $customer, $product);

        return [
            'id' => "reservation_{$reservation->id}",
            'title' => $title,
            'start' => $reservation->checkin?->toISOString(),
            'end' => $reservation->checkout?->toISOString(),
            'allDay' => false,
            'backgroundColor' => $color['background'],
            'borderColor' => $color['border'],
            'textColor' => $color['text'],
            'classNames' => ['reservation-event', "status-{$reservation->status}"],
            
            // Données additionnelles pour les tooltips/modals
            'extendedProps' => [
                'type' => 'reservation',
                'reservation_id' => $reservation->id,
                'customer_name' => $customer->name ?? 'Client',
                'customer_email' => $customer->email ?? '',
                'customer_phone' => $customer->phone ?? '',
                'product_type' => class_basename($reservation->product_type ?? 'Product'),
                'product_name' => $product->name ?? 'Produit',
                'guests' => $reservation->number_of_adults + $reservation->number_of_children,
                'adults' => $reservation->number_of_adults,
                'children' => $reservation->number_of_children,
                'amount' => (float) $reservation->amount,
                'payment_status' => $reservation->payment_status,
                'status' => $reservation->status,
                'comment' => $reservation->comment,
                'booking_source' => $reservation->booking_source,
                'quote_reference' => $reservation->quote_reference,
                'invoice_number' => $reservation->invoice_number,
                'created_at' => $reservation->created_at?->toISOString(),
                'payment_method' => $reservation->payment_method ?? 'unknown',
                
                // Données calculées
                'duration_days' => $reservation->checkin && $reservation->checkout 
                    ? $reservation->checkin->diffInDays($reservation->checkout) 
                    : 0,
                'amount_per_night' => $reservation->checkin && $reservation->checkout && $reservation->amount
                    ? round($reservation->amount / max(1, $reservation->checkin->diffInDays($reservation->checkout)), 2)
                    : 0,
            ]
        ];
    }

    /**
     * Déterminer la couleur de l'événement selon le statut et type
     */
    private function getEventColor(Reservation $reservation): array
    {
        // Couleurs selon le statut (priorité)
        $statusColors = [
            'confirmed' => [
                'background' => '#28a745',
                'border' => '#1e7e34',
                'text' => '#ffffff'
            ],
            'pending' => [
                'background' => '#ffc107',
                'border' => '#d39e00',
                'text' => '#212529'
            ],
            'checked_in' => [
                'background' => '#17a2b8',
                'border' => '#117a8b',
                'text' => '#ffffff'
            ],
            'checked_out' => [
                'background' => '#6c757d',
                'border' => '#545b62',
                'text' => '#ffffff'
            ],
            'cancelled' => [
                'background' => '#dc3545',
                'border' => '#bd2130',
                'text' => '#ffffff'
            ],
            'no_show' => [
                'background' => '#fd7e14',
                'border' => '#e55a00',
                'text' => '#ffffff'
            ],
        ];

        // Couleurs selon le type de produit (fallback)
        $productTypeColors = [
            'Room' => [
                'background' => '#007bff',
                'border' => '#0056b3',
                'text' => '#ffffff'
            ],
            'Activity' => [
                'background' => '#17a2b8',
                'border' => '#117a8b',
                'text' => '#ffffff'
            ],
            'Menu' => [
                'background' => '#6f42c1',
                'border' => '#5a32a3',
                'text' => '#ffffff'
            ],
        ];

        // Prioriser le statut
        if (isset($statusColors[$reservation->status])) {
            return $statusColors[$reservation->status];
        }

        // Puis le type de produit
        $productType = class_basename($reservation->product_type ?? 'Product');
        if (isset($productTypeColors[$productType])) {
            return $productTypeColors[$productType];
        }

        // Couleur par défaut
        return [
            'background' => '#6c757d',
            'border' => '#545b62',
            'text' => '#ffffff'
        ];
    }

    /**
     * Construire le titre de l'événement
     */
    private function buildEventTitle(Reservation $reservation, $customer, $product): string
    {
        $parts = [];

        // Nom du client (ou initiales si trop long)
        $customerName = $customer->name ?? 'Client';
        if (strlen($customerName) > 15) {
            $nameParts = explode(' ', $customerName);
            $customerName = $nameParts[0] . ' ' . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) . '.' : '');
        }
        $parts[] = $customerName;

        // Type de produit (emoji)
        $productType = class_basename($reservation->product_type ?? 'Product');
        $typeEmoji = [
            'Room' => '🏠',
            'Activity' => '🎯',
            'Menu' => '🍽️',
            'Dish' => '🍜'
        ];
        
        if (isset($typeEmoji[$productType])) {
            $parts[] = $typeEmoji[$productType];
        }

        // Nombre de personnes
        $guests = $reservation->number_of_adults + $reservation->number_of_children;
        if ($guests > 0) {
            $parts[] = "👥{$guests}";
        }

        // Statut si important
        if (in_array($reservation->status, ['pending', 'cancelled', 'no_show'])) {
            $statusEmoji = [
                'pending' => '⏳',
                'cancelled' => '❌',
                'no_show' => '👻'
            ];
            if (isset($statusEmoji[$reservation->status])) {
                $parts[] = $statusEmoji[$reservation->status];
            }
        }

        return implode(' ', $parts);
    }
}