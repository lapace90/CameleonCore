<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Services\AdminNotificationService;
use App\Models\DashboardStats;
use App\Models\Reservation;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardStatsProvider implements ProviderInterface
{
    public function __construct(
        private AdminNotificationService $notificationService
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DashboardStats
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisWeek = Carbon::now()->startOfWeek();

        $statsData = [
            'reservations' => [
                'today' => Reservation::whereDate('created_at', $today)->count(),
                'this_week' => Reservation::whereBetween('created_at', [$thisWeek, $thisWeek->copy()->endOfWeek()])->count(),
                'this_month' => Reservation::whereBetween('created_at', [$thisMonth, $thisMonth->copy()->endOfMonth()])->count(),
                'total' => Reservation::count(),
                'confirmed' => Reservation::where('status', 'confirmed')->count(),
                'pending' => Reservation::where('status', 'pending')->count(),
            ],
            'revenue' => [
                'today' => (float) Reservation::whereDate('created_at', $today)->where('payment_status', 'paid')->sum('amount'),
                'this_month' => (float) Reservation::whereBetween('created_at', [$thisMonth, $thisMonth->copy()->endOfMonth()])->where('payment_status', 'paid')->sum('amount'),
                'total' => (float) Reservation::where('payment_status', 'paid')->sum('amount'),
                'average_booking' => (float) Reservation::where('payment_status', 'paid')->avg('amount'),
            ],
            'occupancy' => [
                'current_guests' => $this->getCurrentGuestsCount($today),
                'checkins_today' => Reservation::whereDate('checkin', $today)->where('status', 'confirmed')->count(),
                'checkouts_today' => Reservation::whereDate('checkout', $today)->where('status', 'confirmed')->count(),
            ],
            'quotes' => [
                'pending_validation' => QuoteRequest::where('status', 'draft')->whereNull('email_verified_at')->count(),
                'validated_unpaid' => QuoteRequest::where('status', 'sent')->whereNotNull('email_verified_at')->whereNull('converted_to_reservation_at')->count(),
                'converted_today' => QuoteRequest::whereDate('converted_to_reservation_at', $today)->count(),
            ],
            'notifications' => $this->notificationService->getAdminNotifications(10),
            'system' => [
                'total_users' => User::count(),
                'total_products' => Product::count(),
                'last_reservation_at' => Reservation::latest()->first()?->created_at?->toISOString(),
            ],
            'generated_at' => now()->toISOString(),
        ];

        return new DashboardStats($statsData);
    }

    private function getCurrentGuestsCount(Carbon $today): int
    {
        return Reservation::where('checkin', '<=', $today)
            ->where('checkout', '>', $today)
            ->where('status', 'checked_in')
            ->sum(DB::raw('number_of_adults + number_of_children'));
    }
}
