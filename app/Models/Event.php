<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        // 🟢 CRUD admin pour événements génériques
        new GetCollection(uriTemplate: '/admin/events', middleware: ['auth:sanctum'], security: "is_granted('ROLE_ADMIN')"),
        new Get(uriTemplate: '/admin/events/{id}',  middleware: ['auth:sanctum'], security: "is_granted('ROLE_ADMIN')"),
        new Post(uriTemplate: '/admin/events', middleware: ['auth:sanctum'], security: "is_granted('ROLE_ADMIN')"),
        new Put(uriTemplate: '/admin/events/{id}', middleware: ['auth:sanctum'], security: "is_granted('ROLE_ADMIN')"),
        new Delete(uriTemplate: '/admin/events/{id}', middleware: ['auth:sanctum'], security: "is_granted('ROLE_ADMIN')"),
    ]
)]
class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'type',
        'location',
        'capacity',
        'animator',
        'technician',
        'priority',
        'status',
        'background_color',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'capacity' => 'integer'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    // Accessors pour compatibilité FullCalendar
    public function getCalendarTitleAttribute(): string
    {
        return $this->title;
    }

    public function getCalendarStartAttribute(): string
    {
        return $this->start_date->toIso8601String();
    }

    public function getCalendarEndAttribute(): ?string
    {
        return $this->end_date?->toIso8601String();
    }
}
