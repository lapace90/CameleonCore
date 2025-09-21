<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Services\AdminNotificationService;
use App\Models\AdminNotification;

class AdminNotificationProcessor implements ProcessorInterface
{
    public function __construct(
        private AdminNotificationService $notificationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminNotification
    {
        $notificationId = $uriVariables['id'] ?? null;
        
        if (!$notificationId) {
            throw new \InvalidArgumentException('ID de notification manquant');
        }
        
        $success = $this->notificationService->markNotificationAsRead($notificationId);
        
        if (!$success) {
            throw new \RuntimeException('Impossible de marquer la notification comme lue');
        }
        
        // Retourner la notification mise à jour
        $notifications = $this->notificationService->getAdminNotifications(50);
        $updatedNotification = collect($notifications)->firstWhere('id', $notificationId);
        
        return new AdminNotification($updatedNotification ?? [
            'id' => $notificationId,
            'read' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }
}