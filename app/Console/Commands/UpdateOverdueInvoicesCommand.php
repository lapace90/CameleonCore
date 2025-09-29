<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

class UpdateOverdueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:update-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marquer automatiquement les factures en retard';

    protected $invoiceService;

    /**
     * Create a new command instance.
     */
    public function __construct(InvoiceService $invoiceService)
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Mise à jour des factures en retard...');

        try {
            $count = $this->invoiceService->updateOverdueInvoices();

            if ($count > 0) {
                $this->info("✅ {$count} facture(s) marquée(s) comme en retard");
                Log::info("CRON: {$count} facture(s) marquée(s) comme en retard");
            } else {
                $this->info('ℹ️  Aucune facture en retard trouvée');
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            Log::error('Erreur CRON update overdue invoices', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}