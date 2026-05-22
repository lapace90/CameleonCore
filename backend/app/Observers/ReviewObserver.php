<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Log;

class ReviewObserver
{
    public function __construct(
        private AdminNotificationService $notificationService
    ) {}

    /**
     * Déclenché après la création d'un avis
     */
    public function created(Review $review): void
    {
        // Notifier seulement si l'avis est en attente
        if ($review->status === 'pending') {
            Log::info('👁️ Observer: Nouvel avis créé', [
                'review_id' => $review->id,
                'client' => $review->client_name
            ]);

            $this->notificationService->notifyNewReview($review);
        }
    }
}