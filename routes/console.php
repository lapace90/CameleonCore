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
 */
Schedule::command('invoices:update-overdue')
    ->dailyAt('01:00')
    ->timezone('Africa/Casablanca')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('✅ CRON invoices:update-overdue exécuté avec succès');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('❌ CRON invoices:update-overdue a échoué');
    });

/**
 * Alternative : Exécuter toutes les heures (pour un suivi plus réactif)
 */
// Schedule::command('invoices:update-overdue')
//     ->hourly()
//     ->timezone('Africa/Casablanca');

/**
 * Note: Pour activer le scheduler, ajoutez cette ligne dans votre crontab serveur :
 * * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
 */