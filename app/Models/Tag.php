<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ApiResource]
class Tag extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_global',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    // Relation avec les produits (tags globaux)
    public function products()
    {
        return $this->morphedByMany(Product::class, 'taggable')
            ->withTimestamps();
    }

    // Relation polymorphique (pour les tags spécifiques)
    public function dishes()
    {
        return $this->morphedByMany(Dish::class, 'taggable');
    }

    public function activities()
    {
        return $this->morphedByMany(Activity::class, 'taggable');
    }

    public function ingredients()
    {
        return $this->morphedByMany(Ingredient::class, 'taggable');
    }

    public function rooms()
    {
        return $this->morphedByMany(Room::class, 'taggable');
    }

    public function menus()
    {
        return $this->morphedByMany(Menu::class, 'taggable');
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }
}
