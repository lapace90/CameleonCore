<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiProperty;
use App\State\ReviewProvider;
use App\State\ReviewProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/reviews',
            provider: ReviewProvider::class
        ),
        new Get(
            uriTemplate: '/reviews/{id}',
            provider: ReviewProvider::class
        ),
        new Post(
            uriTemplate: '/reviews',
            processor: ReviewProcessor::class,
            security: "true"
        ),
        new Put(
            uriTemplate: '/reviews/{id}',
            processor: ReviewProcessor::class,
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            uriTemplate: '/reviews/{id}',
            processor: ReviewProcessor::class,
            security: "is_granted('ROLE_ADMIN')"
        ),
    ],
    normalizationContext: ['groups' => ['review:read']],
    denormalizationContext: ['groups' => ['review:write']],
    paginationItemsPerPage: 10
)]
class Review extends Model
{
    use HasFactory, SoftDeletes;

    #[ApiProperty(readable: true, writable: false)]
    protected $id;

    protected $fillable = [
        'client_name',
        'location',
        'email',
        'testimonial_text',
        'rating',
        'category',
        'featured',
        'is_published',
        'photos',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'photos' => 'array',
        'featured' => 'boolean',
        'is_published' => 'boolean',
        'rating' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_published' => false,
        'featured' => false,
        'rating' => 5,
        'category' => 'all',
    ];

    // Scopes pour filtrage
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeByCategory($query, $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function scopeByRating($query, $rating)
    {
        if ($rating) {
            return $query->where('rating', $rating);
        }
        return $query;
    }

    // Méthodes utilitaires
    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'is_published' => true,
        ]);
    }

    public function reject($notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'is_published' => false,
            'admin_notes' => $notes,
        ]);
    }

    public function markAsFeatured()
    {
        $this->update(['featured' => true]);
    }
}