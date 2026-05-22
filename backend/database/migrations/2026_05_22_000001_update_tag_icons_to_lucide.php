<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Maps old FontAwesome icon values → Lucide icon names
    private array $iconMap = [
        // Global tags
        'fas fa-dollar-sign'    => 'dollar-sign',
        'fas fa-crown'          => 'crown',
        'fas fa-star'           => 'star',
        'fas fa-sparkles'       => 'sparkles',
        // Food tags
        'fas fa-leaf'           => 'leaf',
        'fas fa-seedling'       => 'seedling',
        'fas fa-pepper-hot'     => 'flame',
        'fas fa-wheat'          => 'wheat',
        'fas fa-glass-milk'     => 'glass-water',
        // Activity tags
        'fas fa-mountain'       => 'mountain',
        'fas fa-smile'          => 'smile',
        'fas fa-tree'           => 'tree-pine',
        'fas fa-water'          => 'waves',
        'fas fa-hiking'         => 'footprints',
        'fas fa-walking'        => 'footprints',
        'fas fa-fire'           => 'flame',
        // Room tags
        'fas fa-heart'          => 'heart',
        'fas fa-users'          => 'users',
        'fas fa-user'           => 'user',
        'fas fa-user-friends'   => 'users',
        'fas fa-check-circle'   => 'circle-check',
        'fas fa-times-circle'   => 'circle-x',
        'fas fa-bed'            => 'bed',
        'fas fa-home'           => 'home',
        // Auto-generated & product type icons
        'fas fa-tag'            => 'tag',
        'fas fa-utensils'       => 'utensils',
        'fas fa-drumstick-bite' => 'utensils',
        'fas fa-box'            => 'box',
    ];

    public function up(): void
    {
        foreach ($this->iconMap as $old => $new) {
            DB::table('tags')->where('icon', $old)->update(['icon' => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->iconMap as $old => $new) {
            DB::table('tags')->where('icon', $new)->update(['icon' => $old]);
        }
    }
};
