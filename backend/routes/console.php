<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Mettre à jour les factures en retard tous les jours à 1h du matin
 * 
 * Cette tâche parcourt toutes les factures avec payment_status "pending" 
 * dont la date d'échéance est dépassée, et les marque comme "overdue".
 * Une notification est générée pour les administrateurs.
 */
Schedule::command('invoices:update-overdue')
    ->dailyAt('01:00')
    ->timezone('Africa/Casablanca')
    ->onSuccess(function () {
        Log::info('✅ CRON invoices:update-overdue exécuté avec succès');
    })
    ->onFailure(function () {
        Log::error('❌ CRON invoices:update-overdue a échoué');
    });

/**
 * Nettoyer les données périmées tous les jours à 2h du matin
 * 
 * Cette tâche effectue une purge sélective :
 * - Supprime les notifications de plus de 7 jours
 * - Supprime les alertes urgentes de plus de 3 jours
 * - Nettoie le cache du calendrier obsolète (> 24h)
 * 
 * Optimise l'utilisation de Redis et maintient la base de données propre.
 */
Schedule::command('cleanup:expired-data')
    ->dailyAt('02:00')
    ->timezone('Africa/Casablanca')
    ->onSuccess(function () {
        Log::info('✅ CRON cleanup:expired-data exécuté avec succès');
    })
    ->onFailure(function () {
        Log::error('❌ CRON cleanup:expired-data a échoué');
    });

/**
 * Recalculer les tags automatiques tous les jours à 3h du matin si nécessaire
 * 
 * Cette tâche vérifie si des modifications ont eu lieu depuis le dernier
 * recalcul. Si oui, elle exécute la commande de recalcul des tags.
 */
Schedule::command('tags:recalculate --if-needed')
    ->dailyAt('03:00')
    ->timezone('Africa/Casablanca')
    ->onSuccess(function () {
        Log::info('✅ CRON tags:recalculate exécuté avec succès');
    })
    ->onFailure(function () {
        Log::error('❌ CRON tags:recalculate a échoué');
    });
