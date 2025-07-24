<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Category;
use App\Models\Reservation;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Metadata\QueryParameter;

#[ApiResource]
#[QueryParameter(key: 'type', filter: EqualsFilter::class, property: 'productable_type')]
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'category_id',
        'status',
        'productable',
        'is_draft'
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_draft' => 'boolean'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class)->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    public function productable()
    {
        return $this->morphTo();
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'res_product_option')->withTimestamps();
    }

    public function getImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function globalTags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag')
            ->where('is_global', true)
            ->withTimestamps();
    }

    public function getAllTagsAttribute()
    {
        return $this->globalTags
            ->merge($this->productable->specificTags ?? collect())
            ->unique('id');
    }

    
}


