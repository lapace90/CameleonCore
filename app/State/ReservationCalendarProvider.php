<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReservationCalendarProvider implements ProviderInterface
{
    /**
     * Fournit des événements FullCalendar à partir des réservations.
     * Compatible avec /reservations/calendar?start=YYYY-MM-DD&end=YYYY-MM-DD
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        // 1) Récup des bornes (FullCalendar envoie start/end en ISO date)
        $request = request();
        $filters = $context['filters'] ?? [];

        $startParam = $request->query('start', $filters['start'] ?? null);
        $endParam   = $request->query('end',   $filters['end']   ?? null);

        // Fallback: 30 jours autour d’aujourd’hui si rien fourni
        $start = $this->parseDate($startParam, Carbon::now()->startOfMonth()->subDays(7));
        $end   = $this->parseDate($endParam,   Carbon::now()->endOfMonth()->addDays(7));

        // Normaliser en début/fin de jour (FullCalendar travaille souvent en allDay)
        $startBoundary = $start->copy()->startOfDay();
        $endBoundary   = $end->copy()->endOfDay();

        // 2) Cache clé courte (optionnel)
        $cacheKey = sprintf('calendar:%s:%s', $startBoundary->toDateString(), $endBoundary->toDateString());

        return Cache::remember($cacheKey, 60, function () use ($startBoundary, $endBoundary) {
            // 3) Query avec chevauchement (checkin/checkout)
            $reservations = Reservation::query()
                ->where(function ($q) use ($startBoundary, $endBoundary) {
                    $q->whereBetween('checkin',  [$startBoundary, $endBoundary])
                      ->orWhereBetween('checkout', [$startBoundary, $endBoundary])
                      ->orWhere(function ($sub) use ($startBoundary, $endBoundary) {
                          $sub->where('checkin', '<=', $startBoundary)
                              ->where('checkout', '>=', $endBoundary);
                      });
                })
                ->orderBy('checkin')
                ->get();

            // 4) Mapping FullCalendar
            $events = $reservations->map(function (Reservation $r) {
                $title = $this->makeTitle($r);
                $color = $this->colorForStatus($r->status);

                // FullCalendar attend ISO 8601
                $start = Carbon::parse($r->checkin)->toIso8601String();
                $end   = Carbon::parse($r->checkout ?: $r->checkin)->toIso8601String();

                return [
                    'id'    => (string) $r->id,
                    'title' => $title,
                    'start' => $start,
                    'end'   => $end,
                    'allDay'=> true,               // à “true” si on gère des séjours/journées
                    'color' => $color,
                    'extendedProps' => [
                        'status'    => $r->status,
                        'customer'  => [
                            'id'    => $r->customer_id ?? null,
                            'name'  => trim(($r->customer->name ?? '').' '.($r->customer->last_name ?? '')) ?: null,
                            'email' => $r->customer->email ?? null,
                        ],
                        'reference' => $r->reference ?? null,
                        'people'    => $r->people_count ?? null,
                        'notes'     => $r->notes ?? null,
                        'raw'       => [
                            'checkin'  => $r->checkin,
                            'checkout' => $r->checkout,
                        ],
                    ],
                ];
            })->values()->all();

            return $events;
        });
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function colorForStatus(?string $status): string
    {
        // Palette lisible sur fond clair/foncé
        return match ($status) {
            'confirmed'  => '#10b981', // vert
            'checked_in' => '#0ea5e9', // bleu
            'checked_out'=> '#64748b', // gris/secondary
            'pending'    => '#f59e0b', // ambre
            'cancelled'  => '#ef4444', // rouge
            'no_show'    => '#8b5cf6', // violet
            default      => '#3b82f6', // par défaut
        };
    }

    private function makeTitle(Reservation $r): string
    {
        $parts = [];

        // Référence prioritaire
        if (!empty($r->reference)) {
            $parts[] = $r->reference;
        }

        // Nom client
        $name = trim(($r->customer->name ?? '').' '.($r->customer->last_name ?? ''));
        if ($name !== '') {
            $parts[] = '• '.$name;
        }

        // Nb personnes
        if (!empty($r->people_count)) {
            $parts[] = '('.$r->people_count.' pers.)';
        }

        // Statut (emoji léger pour les états “non verts”)
        if (in_array($r->status, ['pending', 'cancelled', 'no_show'], true)) {
            $emoji = [
                'pending'   => '⏳',
                'cancelled' => '❌',
                'no_show'   => '👻',
            ][$r->status] ?? null;

            if ($emoji) {
                $parts[] = $emoji;
            }
        }

        return implode(' ', $parts) ?: 'Réservation';
    }
}
