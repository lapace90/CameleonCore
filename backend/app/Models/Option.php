<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Product;
use App\Models\Reservation;

#[ApiResource]
class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',

    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'res_product_option')->withTimestamps();
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'res_product_option')->withTimestamps();
    }
}
