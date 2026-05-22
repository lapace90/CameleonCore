<?php

namespace App\Data;

use ApiPlatform\Metadata\ApiProperty;
use App\Models\Category;

/**
 * DTO pour les statistiques des catégories
 */
class CategoryStatsData
{
    public function __construct(
        #[ApiProperty(description: 'Statistics by category type')]
        public readonly array $byType,
        
        #[ApiProperty(description: 'Overall statistics')]
        public readonly array $overall,
        
        #[ApiProperty(description: 'Most used categories')]
        public readonly array $mostUsed,
        
        #[ApiProperty(description: 'Recent activity')]
        public readonly array $recentActivity,
        
        #[ApiProperty(description: 'Statistics generation timestamp')]
        public readonly \DateTimeInterface $generatedAt
    ) {}

    /**
     * Générer les statistiques complètes
     */
    public static function generate(): self
    {
        $byType = Category::getTypeStats();
        
        $overall = [
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'total_products' => Category::withCount('products')->get()->sum('products_count'),
            'categories_with_products' => Category::has('products')->count(),
            'empty_categories' => Category::doesntHave('products')->count()
        ];

        $mostUsed = Category::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'type' => $cat->type,
                'products_count' => $cat->products_count
            ])
            ->toArray();

        $recentActivity = Category::latest()
            ->limit(10)
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'type' => $cat->type,
                'action' => 'created',
                'created_at' => $cat->created_at
            ])
            ->toArray();

        return new self(
            byType: $byType,
            overall: $overall,
            mostUsed: $mostUsed,
            recentActivity: $recentActivity,
            generatedAt: now()
        );
    }
}