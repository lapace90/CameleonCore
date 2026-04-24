<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\DashboardStatsProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/admin/dashboard/stats',
            provider: DashboardStatsProvider::class,
            middleware: ['auth:sanctum'],
            security: "is_granted('ROLE_ADMIN')",
            description: 'Statistiques dashboard admin'
        ),
    ]
)]
class DashboardStats
{
    // Modèle virtuel - pas de table en base
    // Les données viennent du DashboardStatsProvider
    
    public array $reservations = [];
    public array $revenue = [];
    public array $occupancy = [];
    public array $quotes = [];
    public array $notifications = [];
    public array $system = [];
    public array $trends = [];
    public string $generated_at = '';
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}