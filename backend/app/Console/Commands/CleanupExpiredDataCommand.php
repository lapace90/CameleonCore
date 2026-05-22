<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupExpiredDataCommand extends Command
{
    protected $signature = 'cleanup:expired-data';
    protected $description = 'Nettoyer les données périmées (notifications, cache, logs)';

    public function handle()
    {
        $this->info('🧹 Nettoyage des données périmées...');
        
        $cleaned = 0;

        // 1. Nettoyer les notifications de plus de 7 jours
        $cleaned += $this->cleanupNotifications();

        // 2. Nettoyer les alertes urgentes de plus de 3 jours
        $cleaned += $this->cleanupUrgentAlerts();

        // 3. Nettoyer le cache du calendrier obsolète
        $this->cleanupCalendarCache();

        if ($cleaned > 0) {
            $this->info("✅ {$cleaned} élément(s) supprimé(s)");
            Log::info("CRON cleanup: {$cleaned} élément(s) nettoyé(s)");
        } else {
            $this->info('ℹ️  Aucune donnée à nettoyer');
        }

        return Command::SUCCESS;
    }

    /**
     * Nettoyer les notifications de plus de 7 jours
     */
    private function cleanupNotifications(): int
    {
        $notifications = Cache::get('admin_notifications', []);
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $initialCount = count($notifications);

        $notifications = array_filter($notifications, function ($notification) use ($sevenDaysAgo) {
            $createdAt = Carbon::parse($notification['created_at']);
            return $createdAt->isAfter($sevenDaysAgo);
        });

        // Réindexer le tableau
        $notifications = array_values($notifications);

        Cache::put('admin_notifications', $notifications, now()->addDays(7));

        $removed = $initialCount - count($notifications);
        
        if ($removed > 0) {
            $this->line("  → {$removed} notification(s) supprimée(s)");
        }

        return $removed;
    }

    /**
     * Nettoyer les alertes urgentes de plus de 3 jours
     */
    private function cleanupUrgentAlerts(): int
    {
        $alerts = Cache::get('urgent_admin_alerts', []);
        $threeDaysAgo = Carbon::now()->subDays(3);
        $initialCount = count($alerts);

        $alerts = array_filter($alerts, function ($alert) use ($threeDaysAgo) {
            $createdAt = Carbon::parse($alert['created_at']);
            return $createdAt->isAfter($threeDaysAgo);
        });

        $alerts = array_values($alerts);

        Cache::put('urgent_admin_alerts', $alerts, now()->addDays(3));

        $removed = $initialCount - count($alerts);
        
        if ($removed > 0) {
            $this->line("  → {$removed} alerte(s) urgente(s) supprimée(s)");
        }

        return $removed;
    }

    /**
     * Nettoyer le cache du calendrier obsolète (plus de 24h)
     */
    private function cleanupCalendarCache(): void
    {
        $keys = [
            'calendar_events',
            'calendar_events_month',
            'calendar_events_week',
            'calendar_events_day'
        ];

        foreach ($keys as $key) {
            if (Cache::has($key)) {
                // Vérifier la date de mise en cache
                $cached = Cache::get($key);
                if (is_array($cached) && isset($cached['cached_at'])) {
                    $cachedAt = Carbon::parse($cached['cached_at']);
                    if ($cachedAt->addDay()->isPast()) {
                        Cache::forget($key);
                        $this->line("  → Cache calendrier '{$key}' supprimé");
                    }
                }
            }
        }
    }
}