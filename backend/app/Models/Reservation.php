<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use App\Models\User;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\State\CalendarProvider;
use App\State\ReservationProvider;
use App\State\ReservationProcessor;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;


#[ApiResource(
    operations: [
        //  Événements calendrier — collection dédiée
        new GetCollection(
            uriTemplate: '/admin/calendar/events',
            provider: CalendarProvider::class,
            security: "is_granted('ROLE_ADMIN')",
            description: 'Tous les événements FullCalendar (réservations + événements)'
        ),
        //  CRUD admin standard avec relations
        new GetCollection(
            uriTemplate: '/admin/reservations',
            provider: ReservationProvider::class,
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Get(
            uriTemplate: '/admin/reservations/{id}',
            provider: ReservationProvider::class,
            security: "is_granted('ROLE_ADMIN')",
            middleware: ['auth:sanctum']
        ),

        new Post(
            uriTemplate: '/admin/reservations',
            processor: ReservationProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            middleware: ['auth:sanctum']
        ),
        new Put(
            uriTemplate: '/admin/reservations/{id}',
            processor: ReservationProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            middleware: ['auth:sanctum']
        ),
        new Delete(
            uriTemplate: '/admin/reservations/{id}',
            processor: ReservationProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            middleware: ['auth:sanctum']
        )
    ]
)]
class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'date',
        'checkin',
        'checkout',
        'actual_checkin',
        'actual_checkout',
        'amount',
        'invoice_number',
        'booking_source',
        'payment_status',
        'number_of_children',
        'number_of_adults',
        'comment',
        'status',
        'user_id',
        'product_id',
        'product_type',
        'quote_reference',
        'stripe_session_id',
        'stripe_payment_intent',
        'parent_reservation_id',
        'payment_method',
    ];

    protected $casts = [
        'date' => 'datetime',
        'checkin' => 'datetime',
        'checkout' => 'datetime',
        'actual_checkin' => 'datetime',
        'actual_checkout' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_reservation')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    // RELATION PARENT/ENFANT POUR RÉSERVATIONS LIÉES
    public function parentReservation()
    {
        return $this->belongsTo(Reservation::class, 'parent_reservation_id');
    }

    public function childReservations()
    {
        return $this->hasMany(Reservation::class, 'parent_reservation_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('checkin', [$startDate, $endDate])
                ->orWhereBetween('checkout', [$startDate, $endDate])
                ->orWhere(function ($inner) use ($startDate, $endDate) {
                    $inner->where('checkin', '<=', $startDate)
                        ->where('checkout', '>=', $endDate);
                });
        });
    }

    public function getCustomerNameAttribute()
    {
        if (!$this->customer) {
            return 'Client inconnu';
        }

        if ($this->customer->name && $this->customer->last_name) {
            return $this->customer->name . ' ' . $this->customer->last_name;
        }

        return $this->customer->name ?: $this->customer->email ?: 'Client inconnu';
    }

    public function getProductNameAttribute()
    {
        return $this->product?->name ?? 'Produit supprimé';
    }

    public function getNightsCountAttribute()
    {
        if (!$this->checkin || !$this->checkout) {
            return 0;
        }

        $checkin = $this->checkin instanceof \Carbon\Carbon ? $this->checkin : \Carbon\Carbon::parse($this->checkin);
        $checkout = $this->checkout instanceof \Carbon\Carbon ? $this->checkout : \Carbon\Carbon::parse($this->checkout);

        return $checkin->diffInDays($checkout);
    }

    public function getTotalGuestsAttribute()
    {
        return ($this->number_of_adults ?? 0) + ($this->number_of_children ?? 0);
    }

    public function canCheckIn(): bool
    {
        return in_array($this->status, ['confirmed']) && is_null($this->actual_checkin);
    }

    public function canCheckOut(): bool
    {
        return in_array($this->status, ['checked_in']) && is_null($this->actual_checkout);
    }
}
