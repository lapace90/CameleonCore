<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use App\Models\User;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\State\ReservationCalendarProvider;
use App\State\DashboardStatsProvider;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        // ✅ Routes standard pour les réservations
        new GetCollection(
            uriTemplate: '/admin/reservations',
            security: "is_granted('ROLE_ADMIN')",
            description: 'Liste des réservations pour les admins'
        ),
        new Get(
            uriTemplate: '/admin/reservations/{id}',
            security: "is_granted('ROLE_ADMIN')",
            description: 'Détail d\'une réservation'
        ),
        new Post(
            uriTemplate: '/admin/reservations',
            security: "is_granted('ROLE_ADMIN')",
            description: 'Créer une nouvelle réservation'
        ),
        new Put(
            uriTemplate: '/admin/reservations/{id}',
            security: "is_granted('ROLE_ADMIN')",
            description: 'Modifier une réservation'
        ),
        new Delete(
            uriTemplate: '/admin/reservations/{id}',
            security: "is_granted('ROLE_ADMIN')",
            description: 'Supprimer une réservation'
        ),
        
        // 🆕 NOUVEAUX ENDPOINTS SPÉCIALISÉS
        new GetCollection(
            uriTemplate: '/admin/reservations/events',
            provider: ReservationCalendarProvider::class,
            security: "is_granted('ROLE_ADMIN')",
            description: 'Événements pour FullCalendar'
        ),
        new Get(
            uriTemplate: '/admin/dashboard/stats',
            provider: DashboardStatsProvider::class,
            security: "is_granted('ROLE_ADMIN')",
            description: 'Statistiques pour le dashboard admin'
        ),
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
        // ✅ NOUVEAUX CHAMPS AJOUTÉS
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
        return $this->morphTo();
    }

    // ✅ NOUVELLES RELATIONS AJOUTÉES
    public function quoteRequest()
    {
        return $this->hasOne(QuoteRequest::class, 'main_reservation_id');
    }

    public function parentReservation()
    {
        return $this->belongsTo(Reservation::class, 'parent_reservation_id');
    }

    public function childReservations()
    {
        return $this->hasMany(Reservation::class, 'parent_reservation_id');
    }

    // ✅ SCOPES UTILES
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('checkin', [$startDate, $endDate])
              ->orWhereBetween('checkout', [$startDate, $endDate])
              ->orWhere(function ($subQ) use ($startDate, $endDate) {
                  $subQ->where('checkin', '<=', $startDate)
                       ->where('checkout', '>=', $endDate);
              });
        });
    }
}