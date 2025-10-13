<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ApiPlatform\Metadata\ApiResource;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;


#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/activities'),
        new Get(uriTemplate: '/activities/{id}')
    ]
)]

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide',
        'duration',
        'meeting_point',
        'max_people',
        'difficulty_level',
    ];

    public function product()
    {
        return $this->morphOne(Product::class, 'productable');
    }

    // Relation avec les tags spécifiques (cohérente avec Dish/Menu)
    public function specificTags()
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->where('is_global', false);
    }

    // Méthode standardisée (même nom que Dish/Menu)
    public function calculateSpecificTags()
    {
        $tags = [];

        // Logique basée sur la difficulté
        switch ($this->difficulty_level) {
            case 1:
                $tags[] = 'easy';
                break;
            case 2:
                $tags[] = 'medium';
                break;
            case 3:
                $tags[] = 'hard';
                break;
            case 4:
                $tags[] = 'extreme';
                break;
        }

        // Logique basée sur la durée
        if ($this->duration <= 60) {
            $tags[] = 'short'; // Moins d'1h
        } elseif ($this->duration <= 180) {
            $tags[] = 'medium_duration'; // 1-3h
        } else {
            $tags[] = 'long'; // Plus de 3h
        }

        // Logique basée sur la capacité
        if ($this->max_people <= 5) {
            $tags[] = 'small_group';
        } elseif ($this->max_people <= 15) {
            $tags[] = 'medium_group';
        } else {
            $tags[] = 'large_group';
        }

        // Logique basée sur le type d'activité (inféré du nom/description)
        $activityName = strtolower($this->product->name ?? '');

        if (str_contains($activityName, 'escalade') || str_contains($activityName, 'climbing')) {
            $tags[] = 'climbing';
        }
        if (str_contains($activityName, 'randonnée') || str_contains($activityName, 'hiking')) {
            $tags[] = 'hiking';
        }
        if (str_contains($activityName, 'eau') || str_contains($activityName, 'water') || str_contains($activityName, 'kayak')) {
            $tags[] = 'water';
        }
        if (str_contains($activityName, 'vélo') || str_contains($activityName, 'bike')) {
            $tags[] = 'cycling';
        }

        // Tags selon le point de rendez-vous
        if (
            str_contains(strtolower($this->meeting_point), 'intérieur') ||
            str_contains(strtolower($this->meeting_point), 'indoor')
        ) {
            $tags[] = 'indoor';
        } else {
            $tags[] = 'outdoor';
        }

        return array_unique($tags);
    }

    // Méthode manuelle pour forcer la mise à jour des tags
    public function updateTags()
    {
        $tags = $this->calculateSpecificTags();

        $this->specificTags()->sync(
            Tag::whereIn('name', $tags)->where('is_global', false)->pluck('id')
        );
    }
}
