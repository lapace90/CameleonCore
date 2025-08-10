<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Activity;
use App\Models\Room;
use App\Models\Menu;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Option;

class ProductStatsController
{
    public function __invoke(Request $request)
    {
        $typeMap = [
            'activity' => Activity::class,
            'room' => Room::class,
            'menu' => Menu::class,
            'dish' => Dish::class,
            'ingredient' => Ingredient::class,
            'option' => Option::class,
        ];

        $request->validate([
            'type' => ['nullable', Rule::in(array_keys($typeMap))]
        ]);

        $query = Product::query();

        if ($request->filled('type')) {
            $query->where('productable_type', $typeMap[$request->type]);
        }

        return [
            'total' => $query->count(),
            'active' => (clone $query)->where('status', true)->where('is_draft', false)->count(),
            'inactive' => (clone $query)->where('status', false)->count(),
            'draft' => (clone $query)->where('is_draft', true)->count(),
            'average_price' => round((clone $query)->avg('price') ?? 0, 2),
        ];
    }
}
