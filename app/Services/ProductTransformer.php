<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductTransformer
{
    public static function transformForList(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'formatted_price' => number_format($product->price, 2, ',', ' ') . ' €',
                'image' => $product->image,
                'status' => $product->status,
                'is_draft' => $product->is_draft,
                'productable_type' => $product->productable_type,
                'productableData' => $product->productable ? $product->productable->toArray() : [],
                'typeConfig' => self::getTypeConfig($product->productable_type),
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name
                ] : null,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ];
        })->toArray();
    }

    public static function transformForDetail(Product $product): array
    {
        $basic = self::transformForList(collect([$product]))[0];
        
        return array_merge($basic, [
            'productableDetail' => $product->productable ? $product->productable->toArray() : [],
            'detail_fields' => [], // Vide pour éviter erreurs SQL
            'tags' => $product->globalTags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color ?? '#6b7280'
            ])->toArray(),
            'options' => $product->options->map(fn($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'price' => $option->price,
                'formatted_price' => number_format($option->price, 2, ',', ' ') . ' €'
            ])->toArray(),
            'statistics' => ['views' => 0, 'orders' => 0, 'rating' => 0]
        ]);
    }

    public static function transformForForm(Product $product = null): array
    {
        if (!$product) {
            return [
                'name' => '',
                'description' => '',
                'price' => 0,
                'status' => true,
                'is_draft' => false,
                'image' => null,
                'category_id' => null,
                'productableData' => []
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'status' => $product->status,
            'is_draft' => $product->is_draft,
            'image' => $product->image,
            'category_id' => $product->category_id,
            'productableData' => $product->productable ? $product->productable->toArray() : []
        ];
    }

    private static function getTypeConfig(?string $type): array
    {
        $configs = [
            'App\\Models\\Activity' => [
                'label' => 'Activités',
                'singular' => 'Activité',
                'icon' => 'fas fa-hiking',
                'color' => '#3b82f6',
                'hasRelation' => null
            ],
            'App\\Models\\Menu' => [
                'label' => 'Menus',
                'singular' => 'Menu',
                'icon' => 'fas fa-utensils',
                'color' => '#10b981',
                'hasRelation' => 'dishes'
            ],
            'App\\Models\\Dish' => [
                'label' => 'Plats',
                'singular' => 'Plat',
                'icon' => 'fas fa-drumstick-bite',
                'color' => '#f97316',
                'hasRelation' => 'ingredients'
            ],
            'App\\Models\\Ingredient' => [
                'label' => 'Ingrédients',
                'singular' => 'Ingrédient',
                'icon' => 'fas fa-seedling',
                'color' => '#22c55e',
                'hasRelation' => 'dishes'
            ],
            'App\\Models\\Room' => [
                'label' => 'Hébergements',
                'singular' => 'Hébergement',
                'icon' => 'fas fa-bed',
                'color' => '#f59e0b',
                'hasRelation' => null
            ]
        ];

        return $configs[$type] ?? [
            'label' => 'Produit',
            'singular' => 'Produit',
            'icon' => 'fas fa-box',
            'color' => '#6b7280',
            'hasRelation' => null
        ];
    }
}