<?php

// ============================================================================
// 1. FORM REQUESTS - Compléter tes validations
// ============================================================================

// app/Http/Requests/StoreProductRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ou logic d'auth selon tes besoins
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'boolean',
            'is_draft' => 'boolean',
            'productable_type' => 'required|string|in:App\\Models\\Activity,App\\Models\\Room,App\\Models\\Menu,App\\Models\\Dish,App\\Models\\Ingredient,App\\Models\\Option',

            // Données productable dynamiques selon le type
            'productable' => 'array',
            'productable.guide' => 'required_if:productable_type,App\\Models\\Activity|string',
            'productable.duration' => 'required_if:productable_type,App\\Models\\Activity|integer|min:1',
            'productable.meeting_point' => 'required_if:productable_type,App\\Models\\Activity|string',
            'productable.max_people' => 'required_if:productable_type,App\\Models\\Activity|integer|min:1',
            'productable.difficulty_level' => 'required_if:productable_type,App\\Models\\Activity|in:easy,medium,hard',

            'productable.capacity' => 'required_if:productable_type,App\\Models\\Room|integer|min:1',
            'productable.availability' => 'nullable|boolean',

            'productable.stock' => 'nullable|integer|min:0',
            'productable.is_vegetarian' => 'boolean',
            'productable.is_vegan' => 'boolean',
            'productable.is_spicy' => 'boolean',
            'productable.is_gluten_free' => 'boolean',
            'productable.is_lactose_free' => 'boolean',
            'productable.is_nut_free' => 'boolean',

            // Relations
            'relations' => 'array',
            'relations.dishes' => 'array',
            'relations.dishes.*' => 'exists:dishes,id',
            'relations.ingredients' => 'array',
            'relations.ingredients.*' => 'exists:ingredients,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire',
            'price.required' => 'Le prix est obligatoire',
            'price.min' => 'Le prix doit être positif',
            'productable.guide.required_if' => 'Le guide est obligatoire pour les activités',
            'productable.duration.required_if' => 'La durée est obligatoire pour les activités',
            // ... autres messages
        ];
    }
}
