<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ApiResource]
class Room extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'capacity',
        'availability',
    ];

    protected $casts = [
        'availability' => 'boolean',
    ];

    public function product()
    {
        return $this->morphOne(Product::class, 'productable');
    }

    public function options()
    {
        return $this->morphMany(Option::class, 'productable');
    }

    // ✅ AJOUT : Relation avec les tags spécifiques
    public function specificTags()
    {
        return $this->morphToMany(Tag::class, 'taggable')->where('is_global', false);
    }

    // ✅ AJOUT : Calcul automatique des tags spécifiques
    public function calculateSpecificTags()
    {
        $tags = [];

        // Tags basés sur la capacité
        switch ($this->capacity) {
            case 1:
                $tags[] = 'solo';
                break;
            case 2:
                $tags[] = 'couple';
                break;
            case 3:
            case 4:
                $tags[] = 'family';
                break;
            default:
                if ($this->capacity >= 5) {
                    $tags[] = 'group';
                }
                break;
        }

        // Tags basés sur la disponibilité
        if ($this->availability) {
            $tags[] = 'available';
        } else {
            $tags[] = 'unavailable';
        }

        // Tags basés sur le prix du produit associé
        if ($this->product) {
            $price = $this->product->price;
            
            if ($price <= 50) {
                $tags[] = 'economic';
            } elseif ($price <= 150) {
                $tags[] = 'standard';
            } else {
                $tags[] = 'luxury';
            }
        }

        // Tags basés sur le nom de la chambre (si applicable)
        if ($this->product) {
            $roomName = strtolower($this->product->name);
            
            if (str_contains($roomName, 'suite') || str_contains($roomName, 'deluxe')) {
                $tags[] = 'luxury';
            }
            if (str_contains($roomName, 'vue mer') || str_contains($roomName, 'ocean view')) {
                $tags[] = 'ocean_view';
            }
            if (str_contains($roomName, 'vue montagne') || str_contains($roomName, 'mountain view')) {
                $tags[] = 'mountain_view';
            }
            if (str_contains($roomName, 'balcon') || str_contains($roomName, 'terrasse')) {
                $tags[] = 'outdoor_space';
            }
        }

        return array_unique($tags);
    }

    // ✅ AJOUT : Méthode manuelle pour forcer la mise à jour des tags
    public function updateTags()
    {
        $tags = $this->calculateSpecificTags();
        
        $this->specificTags()->sync(
            Tag::whereIn('name', $tags)->where('is_global', false)->pluck('id')
        );
    }
}