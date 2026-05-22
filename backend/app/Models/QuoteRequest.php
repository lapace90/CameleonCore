<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\State\QuoteRequestProcessor;
use Illuminate\Support\Str;
use Carbon\Carbon;

#[ApiResource(
    uriTemplate: '/quote-requests',
    operations: [
        new Post(
            uriTemplate: '/quote-requests',
            processor: QuoteRequestProcessor::class,
        ),
        new Get(
            uriTemplate: '/quote-requests/{id}/validate/{token}',
            processor: QuoteRequestProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/admin/quote-requests',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Get(
            uriTemplate: '/admin/quote-requests/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            uriTemplate: '/admin/quote-requests/{id}',
            processor: QuoteRequestProcessor::class,
            security: "is_granted('ROLE_ADMIN')"
        ),
    ]
)]
class QuoteRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'message',
        'checkin_date',
        'checkout_date',
        'guests',
        'validation_token',
        'token_expires_at',
        'status',
        'quote_reference',
        'total_amount',
        'source',
        'ip_address',
        'user_agent',
        'email_verified_at',
        'converted_to_reservation_at',
        'main_reservation_id',
        'stripe_session_id',
        'payment_intent_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'customer_id' => 'integer',
        'checkin_date' => 'date',
        'checkout_date' => 'date',
        'total_amount' => 'decimal:2',
        'guests' => 'integer',
        'converted_to_reservation_at' => 'datetime'
    ];

    protected $appends = ['products_with_quantities', 'quantities'];

    // ===========================
    // RELATIONS
    // ===========================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteRequestItem::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'quote_request_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function mainReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'main_reservation_id');
    }

    // ===========================
    // FACTORY METHOD
    // ===========================

    public static function createFromQuoteData(array $data): self
    {
        if (!array_key_exists('customer_id', $data)) {
            throw new \InvalidArgumentException('A customer_id is required to create a quote request.');
        }

        $request = app()->bound('request') ? request() : null;

        return self::create([
            'customer_id' => $data['customer_id'],
            'message' => $data['message'] ?? null,
            'checkin_date' => $data['checkin_date'] ?? null,
            'checkout_date' => $data['checkout_date'] ?? null,
            'guests' => $data['guests'] ?? 2,
            'validation_token' => self::generateValidationToken(),
            'token_expires_at' => Carbon::now()->addHours(48),
            'status' => 'sent',
            'quote_reference' => self::generateQuoteReference(),
            'total_amount' => $data['total_amount'] ?? 0,
            'source' => $data['source'] ?? 'website',
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent()
        ]);
    }

    // ===========================
    // VALIDATION EMAIL
    // ===========================

    public function validateWithToken(string $token): bool
    {
        if ($this->email_verified_at) {
            return true;
        }

        if (!$this->validation_token || !hash_equals((string)$this->validation_token, (string)$token)) {
            return false;
        }

        if ($this->isTokenExpired()) {
            return false;
        }

        $this->forceFill([
            'email_verified_at' => now(),
            'status' => 'sent',
        ])->save();

        return true;
    }

    public function isTokenExpired(): bool
    {
        return Carbon::now()->isAfter($this->token_expires_at);
    }

    public function markAsSent(): void
    {
        $this->update(['status' => 'sent']);
    }

    // ===========================
    // ACCESSORS - PRODUITS & QUANTITÉS
    // ===========================

    public function getProductsWithQuantitiesAttribute()
    {
        return $this->items()->with('product')->get()->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->product->price,
                    'type' => class_basename($item->product->productable_type ?? 'Product')
                ] : null
            ];
        });
    }

    public function getQuantitiesAttribute(): array
    {
        return $this->items->pluck('quantity', 'product_id')->toArray();
    }

    // ===========================
    // ACCESSORS - CUSTOMER
    // ===========================

    public function getNameAttribute(): ?string
    {
        return $this->customer?->name;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->customer?->last_name;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->customer?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->customer?->phone;
    }

    // ===========================
    // ACCESSORS - BUSINESS
    // ===========================

    public function getDurationAttribute(): ?int
    {
        if (!$this->checkin_date || !$this->checkout_date) {
            return null;
        }

        return $this->checkin_date->diffInDays($this->checkout_date);
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount, 2, ',', ' ') . ' €';
    }

    public function getValidationUrlAttribute(): string
    {
        return config('app.url') . "/validate-quote/{$this->id}/{$this->validation_token}";
    }

    // ===========================
    // EMAIL DATA
    // ===========================

    public function getEmailDataAttribute(): array
    {
        $customer = $this->customer;
        return [
            'reference' => $this->quote_reference,
            'client' => [
                'name' => $customer?->name,
                'email' => $customer?->email,
                'phone' => $customer?->phone,
                'guests' => $this->guests
            ],
            'dates' => [
                'checkin' => $this->checkin_date?->format('d/m/Y'),
                'checkout' => $this->checkout_date?->format('d/m/Y'),
                'duration' => $this->duration
            ],
            'products' => $this->products_with_quantities->toArray(),
            'total' => [
                'amount' => $this->total_amount,
                'formatted' => $this->formatted_total
            ]
        ];
    }

    // ===========================
    // SCOPES
    // ===========================

    public function scopePendingValidation($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeNotExpired($query)
    {
        return $query->where('token_expires_at', '>', Carbon::now());
    }

    // ===========================
    // HELPERS PRIVÉS
    // ===========================

    private static function generateValidationToken(): string
    {
        return Str::random(64);
    }

    private static function generateQuoteReference(): string
    {
        $prefix = 'QUOTE';
        $date = Carbon::now()->format('Ymd');
        $random = Str::upper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }
}