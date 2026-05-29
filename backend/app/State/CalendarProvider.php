<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Reservation;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CalendarProvider implements ProviderInterface
{
    /**
     * Fournit TOUS les événements (réservations + événements génériques)
     * pour FullCalendar
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = request();
        $filters = $context['filters'] ?? [];

        $startParam = $request->query('start', $filters['start'] ?? null);
        $endParam = $request->query('end', $filters['end'] ?? null);

        $start = $this->parseDate($startParam, Carbon::now()->startOfMonth()->subDays(7));
        $end = $this->parseDate($endParam, Carbon::now()->endOfMonth()->addDays(7));

        $startBoundary = $start->copy()->startOfDay();
        $endBoundary = $end->copy()->endOfDay();

        $cacheKey = sprintf('calendar_all:%s:%s', $startBoundary->toDateString(), $endBoundary->toDateString());

        return Cache::remember($cacheKey, 60, function () use ($startBoundary, $endBoundary) {
            $allEvents = [];

            // 1️ RÉCUPÉRER LES RÉSERVATIONS
            $reservations = Reservation::query()
                ->where(function ($q) use ($startBoundary, $endBoundary) {
                    if (config('instance.features.checkin_checkout')) {
                        // Mode séjour : chercher les ranges qui chevauchent la période
                        $q->whereBetween('checkin', [$startBoundary, $endBoundary])
                            ->orWhereBetween('checkout', [$startBoundary, $endBoundary])
                            ->orWhere(function ($sub) use ($startBoundary, $endBoundary) {
                                $sub->where('checkin', '<=', $startBoundary)
                                    ->where('checkout', '>=', $endBoundary);
                            });
                    } else {
                        // Mode prestation : chercher les dates simples dans la période
                        $q->whereBetween('checkin', [$startBoundary, $endBoundary]);
                    }
                })
                ->with(['customer', 'product'])
                ->get();

            // 2️ RÉCUPÉRER LES ÉVÉNEMENTS GÉNÉRIQUES
            $events = Event::query()
                ->inPeriod($startBoundary, $endBoundary)
                ->active()
                ->get();

            // 3️ MAPPER LES RÉSERVATIONS
            foreach ($reservations as $reservation) {
                $isRangeMode = config('instance.features.checkin_checkout');

                $eventData = [
                    'id' => 'reservation_' . $reservation->id,
                    'title' => $this->makeReservationTitle($reservation),
                    'start' => $reservation->checkin->toIso8601String(),
                    'backgroundColor' => '#28a745',
                    'borderColor' => '#1e7e34',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'reservation',
                        'source' => 'reservation',
                        'reservation_id' => $reservation->id,
                        'customer_name' => $reservation->customer->name ?? '',
                        'customer_phone' => $reservation->customer->phone ?? '',
                        'customer_email' => $reservation->customer->email ?? '',
                        'amount' => $reservation->amount,
                        'guests' => ($reservation->number_of_adults ?? 0) + ($reservation->number_of_children ?? 0),
                        'status' => $reservation->status,
                        'comment' => $reservation->comment,
                        'product_name' => $reservation->product->name ?? '',
                    ]
                ];

                if ($isRangeMode && $reservation->checkout) {
                    // Hôtel : événement multi-jours
                    $eventData['end'] = $reservation->checkout->toIso8601String();
                } else {
                    // Traiteur : événement ponctuel, pas de end
                    $eventData['allDay'] = false;
                }

                $allEvents[] = $eventData;
            }

            // 4️ MAPPER LES ÉVÉNEMENTS GÉNÉRIQUES
            foreach ($events as $event) {
                $allEvents[] = [
                    'id' => 'event_' . $event->id, // Préfixe pour différencier
                    'title' => $event->title,
                    'start' => $event->start_date->toIso8601String(),
                    'end' => $event->end_date?->toIso8601String(),
                    'backgroundColor' => $event->background_color,
                    'borderColor' => $event->background_color,
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => $event->type,
                        'source' => 'event',
                        'event_id' => $event->id,
                        'location' => $event->location,
                        'capacity' => $event->capacity,
                        'animator' => $event->animator,
                        'technician' => $event->technician,
                        'priority' => $event->priority,
                        'notes' => $event->notes
                    ]
                ];
            }

            return $allEvents;
        });
    }

    private function parseDate(?string $dateParam, Carbon $fallback): Carbon
    {
        if (empty($dateParam)) return $fallback;

        try {
            return Carbon::parse($dateParam);
        } catch (\Exception $e) {
            return $fallback;
        }
    }

    private function makeReservationTitle(Reservation $reservation): string
    {
        $customer = $reservation->customer->name ?? 'Client';
        $product = $reservation->product->name ?? '';

        // Mode prestation (traiteur, service) : nom + menu + convives
        if (!config('instance.features.checkin_checkout')) {
            $guests = ($reservation->number_of_adults ?? 0) + ($reservation->number_of_children ?? 0);
            $parts = [$customer];
            if ($product) $parts[] = $product;
            if ($guests > 0) $parts[] = "{$guests} pers.";
            if ($reservation->checkin) {
                $parts[] = $reservation->checkin->format('H:i');
            }
            return implode(' • ', $parts);
        }

        // Mode séjour (hôtel) : nom + produit
        return $product ? "{$customer} - {$product}" : $customer;
    }
}
