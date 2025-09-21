<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Services\AdminNotificationService;
use App\Models\AdminNotification;

class AdminNotificationProvider implements ProviderInterface
{
    public function __construct(
        private AdminNotificationService $notificationService
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $notifications = $this->notificationService->getAdminNotifications(50);
        
        return array_map(function ($notificationData) {
            return new AdminNotification($notificationData);
        }, $notifications);
    }
}
