<?php
// app/Models/Category.php - VERSION MISE À JOUR

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ApiResource]
class Category extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'type',
        'name',
        'photo',
        'description',
    ];

    // Constantes pour les types
    public const TYPES = [
        'Activity' => 'Activités',
        'Menu' => 'Menus', 
        'Dish' => 'Plats',
        'Room' => 'Hébergements',
        'Ingredient' => 'Ingrédients'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    public function getTypeLabelAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public static function rules()
    {
        return [
            'type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function messages()
    {
        return [
            'type.required' => 'Le type de catégorie est requis',
            'type.in' => 'Le type de catégorie doit être : ' . implode(', ', array_keys(self::TYPES)),
            'name.required' => 'Le nom de la catégorie est requis',
            'name.max' => 'Le nom ne peut pas dépasser 50 caractères',
            'description.max' => 'La description ne peut pas dépasser 500 caractères',
        ];
    }

    // Helper pour obtenir les catégories par type
    public static function getByType(string $type)
    {
        return self::where('type', $type)->orderBy('name')->get();
    }

    // Helper pour obtenir tous les types disponibles
    public static function getAvailableTypes()
    {
        return self::TYPES;
    }
}