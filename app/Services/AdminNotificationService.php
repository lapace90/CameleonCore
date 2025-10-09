<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class AdminNotificationService
{
    /**
     * Notifier les admins d'une nouvelle réservation
     */
    public function notifyNewReservation(Reservation $reservation): void
    {
        Log::info('📧 Notification nouvelle réservation', [
            'reservation_id' => $reservation->id,
            'customer' => $reservation->customer->name ?? 'N/A',
            'amount' => $reservation->amount,
            'quote_reference' => $reservation->quote_reference
        ]);

        // 1. Email aux admins (si configuré)
        if (config('mail.default') && config('mail.from.address')) {
            $this->sendEmailToAdmins($reservation);
        }

        // 2. Notification cache pour le dashboard
        $this->createDashboardNotification($reservation);

        // 3. Invalidation cache calendrier
        $this->triggerCalendarUpdate();
    }

    /**
     * Envoyer un email aux administrateurs
     */
    private function sendEmailToAdmins(Reservation $reservation): void
    {
        // Récupérer les admins (ajuster selon votre système de rôles)
        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'super-admin', 'manager']);
        })->get();

        // Si pas de système de rôles, utiliser tous les users pour le test
        if ($admins->isEmpty()) {
            $admins = User::take(3)->get(); // Limiter pour éviter le spam en dev
        }

        foreach ($admins as $admin) {
            try {
                // Vérifier si le template email existe
                if (view()->exists('email.admin.new-reservation')) {
                    Mail::send('email.admin.new-reservation', [
                        'reservation' => $reservation,
                        'customer' => $reservation->customer,
                        'admin' => $admin
                    ], function ($message) use ($admin, $reservation) {
                        $message->to($admin->email)
                            ->subject("🏨 Nouvelle réservation #{$reservation->id} - CampCameleonX")
                            ->from(config('mail.from.address'), config('mail.from.name'));
                    });
                } else {
                    // Email simple en cas de template manquant
                    Mail::raw("
Nouvelle réservation reçue !

Réservation #{$reservation->id}
Client: {$reservation->customer->name}
Montant: {$reservation->amount}€
Arrivée: {$reservation->checkin}
Départ: {$reservation->checkout}

Voir: " . config('app.url') . "/admin/reservations/{$reservation->id}
                    ", function ($message) use ($admin, $reservation) {
                        $message->to($admin->email)
                            ->subject("🏨 Nouvelle réservation #{$reservation->id}")
                            ->from(config('mail.from.address'), config('mail.from.name'));
                    });
                }

                Log::info('✅ Email admin envoyé', ['admin' => $admin->email]);
            } catch (\Exception $e) {
                Log::error('❌ Erreur envoi email admin', [
                    'admin' => $admin->email,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Créer une notification pour le dashboard admin
     */
    private function createDashboardNotification(Reservation $reservation): void
    {
        $notification = [
            'id' => uniqid(),
            'type' => 'new_reservation',
            'title' => 'Nouvelle réservation',
            'message' => sprintf(
                'Réservation #%d - %s - %s€',
                $reservation->id,
                $reservation->customer->name ?? 'Client',
                number_format($reservation->amount, 2)
            ),
            'data' => [
                'reservation_id' => $reservation->id,
                'customer_name' => $reservation->customer->name ?? 'N/A',
                'amount' => $reservation->amount,
                'checkin' => $reservation->checkin?->format('Y-m-d'),
                'checkout' => $reservation->checkout?->format('Y-m-d'),
                'quote_reference' => $reservation->quote_reference,
            ],
            'created_at' => now()->toISOString(),
            'read' => false,
            'priority' => 'normal',
            'actions' => [
                [
                    'label' => 'Voir la réservation',
                    'url' => "/admin/reservations/{$reservation->id}",
                    'type' => 'primary'
                ],
                [
                    'label' => 'Calendrier',
                    'url' => '/admin/agenda',
                    'type' => 'secondary'
                ]
            ]
        ];

        // Stocker dans le cache pour récupération par le frontend
        $cacheKey = 'admin_notifications';
        $notifications = Cache::get($cacheKey, []);
        array_unshift($notifications, $notification);

        // Garder seulement les 50 dernières notifications
        $notifications = array_slice($notifications, 0, 50);

        Cache::put($cacheKey, $notifications, now()->addDays(7));

        Log::info('✅ Notification dashboard créée', [
            'notification_id' => $notification['id'],
            'reservation_id' => $reservation->id
        ]);
    }

    /**
     * Déclencher la mise à jour du calendrier
     */
    private function triggerCalendarUpdate(): void
    {
        // Invalider le cache des événements du calendrier
        Cache::forget('calendar_events');
        
        // ✅ CORRECTION : Pas de tags avec le driver file
        // Cache::tags(['calendar'])->flush(); // Ne marche qu'avec Redis/Memcached
        
        // Alternative : supprimer les clés spécifiques
        $keys = ['calendar_events_month', 'calendar_events_week', 'calendar_events_day'];
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Log::info('✅ Cache calendrier invalidé pour mise à jour');
    }

    /**
     * Récupérer les notifications pour le dashboard
     */
    public function getAdminNotifications(int $limit = 20): array
    {
        $notifications = Cache::get('admin_notifications', []);
        
        return array_slice($notifications, 0, $limit);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markNotificationAsRead(string $notificationId): bool
    {
        $notifications = Cache::get('admin_notifications', []);
        
        foreach ($notifications as &$notification) {
            if ($notification['id'] === $notificationId) {
                $notification['read'] = true;
                Cache::put('admin_notifications', $notifications, now()->addDays(7));
                return true;
            }
        }

        return false;
    }

    /**
     * Obtenir les statistiques pour le dashboard
     */
    public function getDashboardStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            'reservations_today' => Reservation::whereDate('created_at', $today)->count(),
            'reservations_this_month' => Reservation::whereDate('created_at', '>=', $thisMonth)->count(),
            'total_revenue_today' => (float) Reservation::whereDate('created_at', $today)->sum('amount'),
            'total_revenue_month' => (float) Reservation::whereDate('created_at', '>=', $thisMonth)->sum('amount'),
            'pending_checkins' => Reservation::whereDate('checkin', $today)
                ->where('status', 'confirmed')
                ->count(),
            'pending_checkouts' => Reservation::whereDate('checkout', $today)
                ->where('status', 'confirmed')
                ->count(),
            'total_reservations' => Reservation::count(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Créer une alerte manuelle si la création automatique échoue
     */
    public function createManualReservationAlert(array $data): void
    {
        $alert = [
            'id' => uniqid(),
            'type' => 'manual_reservation_required',
            'title' => '⚠️ Réservation à créer manuellement',
            'message' => "Paiement reçu pour {$data['quote_reference']} mais création auto échouée",
            'priority' => 'high',
            'data' => $data,
            'created_at' => now()->toISOString(),
            'read' => false,
            'actions' => [
                [
                    'label' => 'Créer la réservation',
                    'url' => "/admin/reservations/create?quote_id={$data['quote_id']}",
                    'type' => 'primary'
                ]
            ]
        ];

        // Stocker dans le cache des notifications urgentes
        $urgentKey = 'urgent_admin_alerts';
        $alerts = Cache::get($urgentKey, []);
        array_unshift($alerts, $alert);
        Cache::put($urgentKey, $alerts, now()->addDays(3));

        Log::warning('⚠️ Alerte manuelle créée', [
            'quote_id' => $data['quote_id'],
            'alert_id' => $alert['id']
        ]);
    }
}