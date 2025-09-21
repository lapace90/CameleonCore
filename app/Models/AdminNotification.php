<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\State\AdminNotificationProvider;
use App\State\AdminNotificationProcessor;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/admin/notifications',
            provider: AdminNotificationProvider::class,
            security: "is_granted('ROLE_ADMIN')",
            description: 'Liste des notifications admin'
        ),
        new Patch(
            uriTemplate: '/admin/notifications/{id}/mark-read',
            processor: AdminNotificationProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            description: 'Marquer une notification comme lue'
        ),
    ]
)]
class AdminNotification
{
    public string $id = '';
    public string $type = '';
    public string $title = '';
    public string $message = '';
    public array $data = [];
    public array $actions = [];
    public string $priority = 'normal';
    public bool $read = false;
    public string $created_at = '';
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
