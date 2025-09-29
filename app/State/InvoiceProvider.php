<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Invoice;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InvoiceProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = app(Request::class);
        $path = $request->path();

        Log::info('📋 InvoiceProvider appelé', [
            'path' => $path,
            'operation' => get_class($operation),
            'uri_variables' => $uriVariables,
            'method' => $request->method()
        ]);

        // ===========================
        // STATISTIQUES DASHBOARD
        // ===========================
        if (str_contains($path, '/invoices/stats')) {
            return $this->getInvoiceStats();
        }

        // ===========================
        // GÉNÉRATION PDF
        // ===========================
        if (str_contains($path, '/pdf') && isset($uriVariables['id'])) {
            return $this->generatePdf((int) $uriVariables['id']);
        }

        // ===========================
        // LISTE DES FACTURES (avec filtres)
        // ===========================
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            return $this->getInvoicesList($request);
        }

        // ===========================
        // DÉTAIL D'UNE FACTURE
        // ===========================
        if ($operation instanceof \ApiPlatform\Metadata\Get) {
            return $this->getInvoiceDetail((int) $uriVariables['id']);
        }

        return null;
    }

    // ===========================
    // MÉTHODE: LISTE DES FACTURES
    // ===========================
    private function getInvoicesList(Request $request): mixed
    {
        Log::info('📋 Récupération liste factures avec filtres');

        $query = Invoice::with([
            'customer:id,name,last_name,email,phone',
            'reservation:id,checkin,checkout,invoice_number,product_id,product_type',
            'reservation.product:id,name,price'
        ]);

        // Filtres
        $this->applyFilters($query, $request);

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min((int) $request->get('per_page', 15), 100);
        $invoices = $query->paginate($perPage);

        // Transformation manuelle pour éviter les IRIs
        $invoices->getCollection()->transform(function ($invoice) {
            return $this->transformInvoice($invoice);
        });

        Log::info('✅ Factures récupérées', [
            'total' => $invoices->total(),
            'per_page' => $invoices->perPage(),
            'current_page' => $invoices->currentPage()
        ]);

        return $invoices;
    }

    // ===========================
    // MÉTHODE: DÉTAIL FACTURE
    // ===========================
    private function getInvoiceDetail(int $id): ?object
    {
        Log::info("📋 Récupération facture #{$id} avec relations");

        $invoice = Invoice::with([
            'customer' => function($query) {
                $query->select('id', 'name', 'last_name', 'email', 'phone', 'address', 'created_at');
            },
            'reservation' => function($query) {
                $query->select('id', 'checkin', 'checkout', 'invoice_number', 'amount', 
                              'number_of_adults', 'number_of_children', 'product_id', 'product_type');
            },
            'reservation.product' => function($query) {
                $query->select('id', 'name', 'description', 'price');
            }
        ])->find($id);

        if (!$invoice) {
            Log::warning("⚠️ Facture #{$id} introuvable");
            return null;
        }

        Log::info("✅ Facture #{$id} chargée", [
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer_name,
            'status' => $invoice->status
        ]);

        return $this->transformInvoice($invoice);
    }

    // ===========================
    // MÉTHODE: STATISTIQUES
    // ===========================
    private function getInvoiceStats(): array
    {
        Log::info('📊 Calcul statistiques factures');

        $now = Carbon::now();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();

        $stats = [
            // Revenus
            'revenue' => [
                'total' => Invoice::paid()->sum('amount'),
                'this_month' => Invoice::paid()->thisMonth()->sum('amount'),
                'last_month' => Invoice::paid()
                    ->whereBetween('issue_date', [$lastMonth, $thisMonth])
                    ->sum('amount'),
            ],

            // Compteurs
            'counts' => [
                'total' => Invoice::count(),
                'paid' => Invoice::paid()->count(),
                'unpaid' => Invoice::unpaid()->count(),
                'overdue' => Invoice::overdue()->count(),
                'cancelled' => Invoice::cancelled()->count(),
            ],

            // Montants
            'amounts' => [
                'total_unpaid' => Invoice::unpaid()->sum('amount'),
                'total_overdue' => Invoice::overdue()->sum('amount'),
            ],

            // Taux de paiement
            'payment_rate' => $this->calculatePaymentRate(),

            // Délai moyen de paiement
            'average_payment_delay' => $this->calculateAveragePaymentDelay(),

            // Factures récentes
            'recent_unpaid' => $this->getRecentUnpaid(),

            // Évolution mensuelle (12 derniers mois)
            'monthly_evolution' => $this->getMonthlyEvolution(),

            // Top clients (par montant)
            'top_customers' => $this->getTopCustomers(),

            'generated_at' => $now->toISOString(),
        ];

        Log::info('✅ Statistiques calculées', [
            'total_revenue' => $stats['revenue']['total'],
            'unpaid_count' => $stats['counts']['unpaid'],
            'overdue_count' => $stats['counts']['overdue']
        ]);

        return $stats;
    }

    // ===========================
    // MÉTHODE: GÉNÉRATION PDF
    // ===========================
    private function generatePdf(int $id): array
    {
        Log::info("📄 Génération PDF facture #{$id}");

        $invoice = Invoice::with(['customer', 'reservation.product'])->findOrFail($id);

        // TODO: Implémenter la génération PDF réelle (avec DomPDF ou similaire)
        // Pour l'instant, on retourne les données pour le frontend
        
        $pdfData = [
            'invoice_number' => $invoice->invoice_number,
            'issue_date' => $invoice->formatted_issue_date,
            'due_date' => $invoice->formatted_due_date,
            'customer' => [
                'name' => $invoice->customer_name,
                'email' => $invoice->customer->email,
                'phone' => $invoice->customer->phone ?? 'N/A',
                'address' => $invoice->customer->address ?? 'N/A',
            ],
            'items' => [
                [
                    'description' => $invoice->reservation?->product?->name ?? 'Séjour',
                    'quantity' => 1,
                    'unit_price' => $invoice->amount,
                    'total' => $invoice->amount,
                ]
            ],
            'subtotal' => $invoice->amount,
            'total' => $invoice->amount,
            'status' => $invoice->status_label,
            'notes' => $invoice->notes ?? '',
        ];

        Log::info("✅ Données PDF préparées pour facture #{$id}");

        return [
            'pdf_data' => $pdfData,
            'download_url' => "/admin/invoices/{$id}/download", // Future route
        ];
    }

    // ===========================
    // TRANSFORMATION DONNÉES
    // ===========================
    private function transformInvoice($invoice): object
    {
        $data = $invoice->toArray();

        // Forcer l'inclusion des relations complètes
        if ($invoice->customer) {
            $data['customer'] = $invoice->customer->toArray();
        }

        if ($invoice->reservation) {
            $reservationData = $invoice->reservation->toArray();
            
            if ($invoice->reservation->product) {
                $reservationData['product'] = $invoice->reservation->product->toArray();
            }
            
            $data['reservation'] = $reservationData;
        }

        // Ajouter les accessors calculés
        $data['formatted_amount'] = $invoice->formatted_amount;
        $data['formatted_issue_date'] = $invoice->formatted_issue_date;
        $data['formatted_due_date'] = $invoice->formatted_due_date;
        $data['status_label'] = $invoice->status_label;
        $data['status_color'] = $invoice->status_color;
        $data['is_overdue'] = $invoice->is_overdue;
        $data['is_paid'] = $invoice->is_paid;
        $data['days_overdue'] = $invoice->days_overdue;
        $data['days_until_due'] = $invoice->days_until_due;
        $data['customer_name'] = $invoice->customer_name;

        // Indicateurs d'action possibles
        $data['can_be_paid'] = $invoice->canBePaid();
        $data['can_be_modified'] = $invoice->canBeModified();
        $data['can_be_cancelled'] = $invoice->canBeCancelled();

        return (object) $data;
    }

    // ===========================
    // FILTRES
    // ===========================
    private function applyFilters($query, Request $request): void
    {
        // Filtre par statut
        if ($status = $request->get('status')) {
            if ($status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $status);
            }
        }

        // Filtre par client
        if ($customerId = $request->get('customer_id')) {
            $query->forCustomer((int) $customerId);
        }

        // Filtre par période
        if ($startDate = $request->get('start_date')) {
            $query->where('issue_date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->where('issue_date', '<=', $endDate);
        }

        // Recherche textuelle
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'ILIKE', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'ILIKE', "%{$search}%")
                                   ->orWhere('last_name', 'ILIKE', "%{$search}%")
                                   ->orWhere('email', 'ILIKE', "%{$search}%");
                  });
            });
        }
    }

    // ===========================
    // CALCULS STATISTIQUES
    // ===========================
    private function calculatePaymentRate(): float
    {
        $total = Invoice::count();
        
        if ($total === 0) {
            return 0;
        }

        $paid = Invoice::paid()->count();
        
        return round(($paid / $total) * 100, 2);
    }

    private function calculateAveragePaymentDelay(): ?float
    {
        $paidInvoices = Invoice::paid()
            ->whereNotNull('payment_date')
            ->get();

        if ($paidInvoices->isEmpty()) {
            return null;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($paidInvoices as $invoice) {
            if ($invoice->payment_date && $invoice->issue_date) {
                $days = $invoice->issue_date->diffInDays($invoice->payment_date);
                $totalDays += $days;
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : null;
    }

    private function getRecentUnpaid(): array
    {
        return Invoice::unpaid()
            ->with('customer:id,name,last_name,email')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer_name,
                    'amount' => $invoice->formatted_amount,
                    'due_date' => $invoice->formatted_due_date,
                    'days_until_due' => $invoice->days_until_due,
                    'is_overdue' => $invoice->is_overdue,
                ];
            })
            ->toArray();
    }

    private function getMonthlyEvolution(): array
    {
        $months = [];
        $now = Carbon::now();

        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $revenue = Invoice::paid()
                ->betweenDates($startOfMonth, $endOfMonth)
                ->sum('amount');

            $count = Invoice::betweenDates($startOfMonth, $endOfMonth)->count();

            $months[] = [
                'month' => $date->format('M Y'),
                'revenue' => (float) $revenue,
                'count' => $count,
            ];
        }

        return $months;
    }

    private function getTopCustomers(int $limit = 5): array
    {
        return Invoice::selectRaw('customer_id, SUM(amount) as total_revenue, COUNT(*) as invoice_count')
            ->with('customer:id,name,last_name,email')
            ->groupBy('customer_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'customer_id' => $item->customer_id,
                    'customer_name' => $item->customer ? 
                        trim($item->customer->name . ' ' . ($item->customer->last_name ?? '')) : 
                        'Client inconnu',
                    'total_revenue' => number_format($item->total_revenue, 2, ',', ' ') . ' €',
                    'invoice_count' => $item->invoice_count,
                ];
            })
            ->toArray();
    }
}