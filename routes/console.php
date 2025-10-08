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
        Log::info('✅ CRON invoices:update-overdue exécuté avec succès');
    })
    ->onFailure(function () {
        Log::error('❌ CRON invoices:update-overdue a échoué');
    });

/**
 * 🆕 Nettoyer les données périmées tous les jours à 2h du matin
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