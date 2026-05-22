<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Mêmes règles que StoreProductRequest mais avec sometimes au lieu de required
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'boolean',
            'is_draft' => 'boolean',
            
            'productable' => 'array',
            'productable.guide' => 'string',
            'productable.duration' => 'integer|min:1',
            // ... autres règles productable
            
            'relations' => 'array',
            'relations.dishes' => 'array',
            'relations.dishes.*' => 'exists:dishes,id',
            'relations.ingredients' => 'array',
            'relations.ingredients.*' => 'exists:ingredients,id',
        ];
    }
}