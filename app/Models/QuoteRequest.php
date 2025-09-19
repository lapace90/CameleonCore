<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\State\QuoteRequestProcessor;
use App\Models\QuoteRequestItem;
use App\Models\Product;
use Illuminate\Support\Str;
use Carbon\Carbon;

#[ApiResource(
    uriTemplate: '/quote-requests',
    operations: [
        // ✅ Création devis (public) - Envoie email de validation
        new Post(
            uriTemplate: '/quote-requests',
            processor: QuoteRequestProcessor::class,
        ),
        // ✅ Validation email via token (public)
        new Get(
            uriTemplate: '/quote-requests/{id}/validate/{token}',
            processor: QuoteRequestProcessor::class,
        ),
        // ✅ Administration - Consultation devis validés
        new GetCollection(
            uriTemplate: '/admin/quote-requests',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Get(
            uriTemplate: '/admin/quote-requests/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        // ✅ Mise à jour statut (admin)
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
        'selected_product_ids',
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
        'user_agent'
    ];

    protected $casts = [
        'selected_product_ids' => 'array',
        'email_verified_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'customer_id' => 'integer',
        'checkin_date' => 'date',
        'checkout_date' => 'date',
        'total_amount' => 'decimal:2',
        'guests' => 'integer'
    ];

    // ===========================
    // MÉTHODES STATIQUES (Factory)
    // ===========================

    /**
     * Créer une nouvelle demande de devis avec token
     */
    public static function createFromQuoteData(array $data): self
    {
        if (!array_key_exists('customer_id', $data)) {
            throw new \InvalidArgumentException('A customer_id is required to create a quote request.');
        }

        $request = app()->bound('request') ? request() : null;

        return self::create([
            'selected_product_ids' => $data['product_ids'] ?? [],
            'customer_id' => $data['customer_id'],
            'message' => $data['message'] ?? null,
            'checkin_date' => $data['checkin_date'] ?? null,
            'checkout_date' => $data['checkout_date'] ?? null,
            'guests' => $data['guests'] ?? 2,
            'validation_token' => self::generateValidationToken(),
            'token_expires_at' => Carbon::now()->addHours(48), // 48h pour valider
            'status' => 'draft',
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

    /**
     * Valider l'email avec le token
     */
    public function validateWithToken(string $token): bool
    {
        if ($this->email_verified_at) {
            return true; // déjà validé: OK
        }

        if (!$this->validation_token || !hash_equals((string)$this->validation_token, (string)$token)) {
            return false;
        }

        if ($this->isTokenExpired()) {
            return false;
        }

        $this->forceFill([
            'email_verified_at' => now(),
            // Hotfix: garder un statut permis par la contrainte actuelle
            // 'status' => 'validated', // ❌ provoque 23514
            'status' => 'sent',        // ✅ permis ('draft','sent','expired')
        ])->save();

        return true;
    }


    /**
     * Vérifier si le token a expiré
     */
    public function isTokenExpired(): bool
    {
        return Carbon::now()->isAfter($this->token_expires_at);
    }

    /**
     * Marquer le devis comme envoyé
     */
    public function markAsSent(): void
    {
        $this->update(['status' => 'sent']);
    }

    // ===========================
    // RELATION VERS PRODUITS - SIMPLE !
    // ===========================

    /**
     * Récupérer les produits sélectionnés - UNE SEULE REQUÊTE !
     */
    public function getSelectedProductsAttribute()
    {
        if (empty($this->selected_product_ids)) {
            return collect([]);
        }

        return Product::whereIn('id', $this->selected_product_ids)
            ->select('id', 'name', 'price', 'image', 'productable_type')
            ->get();
    }

    // ===========================
    // CALCULS BUSINESS
    // ===========================

    /**
     * Durée du séjour en jours
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->checkin_date || !$this->checkout_date) {
            return null;
        }

        return $this->checkin_date->diffInDays($this->checkout_date);
    }

    /**
     * Prix total formaté
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount, 2, ',', ' ') . ' €';
    }

    // ===========================
    // DONNÉES POUR EMAILS - SIMPLE !
    // ===========================

    /**
     * Données formatées pour les emails
     */
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
            'products' => $this->selected_products->map(function ($product) {
                return [
                    'name' => $product->name,
                    'price' => $product->price,
                    'formatted_price' => number_format($product->price, 2, ',', ' ') . ' €',
                    'type' => class_basename($product->productable_type)
                ];
            })->toArray(),
            'total' => [
                'amount' => $this->total_amount,
                'formatted' => $this->formatted_total
            ]
        ];
    }

    // ===========================
    // SCOPES & ACCESSORS
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

    /**
     * URL de validation complète
     */
    public function getValidationUrlAttribute(): string
    {
        return config('app.url') . "/validate-quote/{$this->id}/{$this->validation_token}";
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getNameAttribute(): ?string
    {
        return optional($this->customer)->name;
    }

    public function getLastNameAttribute(): ?string
    {
        return optional($this->customer)->last_name;
    }

    public function getEmailAttribute(): ?string
    {
        return optional($this->customer)->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return optional($this->customer)->phone;
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
    /**
     * Retourne les produits avec leurs quantités pour l'API
     * Format enrichi pour l'interface admin
     */
    public function getProductsWithQuantities()
    {
        if (!$this->pivot_migrated) {
            // Mode legacy : calculer depuis selected_product_ids
            $quantities = array_count_values($this->selected_product_ids ?? []);

            return collect($quantities)->map(function ($quantity, $productId) {
                $product = Product::find($productId);

                return [
                    'product_id' => (int) $productId,
                    'quantity' => $quantity,
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'type' => class_basename($product->productable_type ?? 'Product')
                    ] : null
                ];
            })->values();
        }

        // Mode nouveau : utiliser la table pivot
        return $this->items->load('product')->map(function ($item) {
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

    // Ajouter aussi cet accessor pour l'API
    protected $appends = ['products_with_quantities'];

    public function getProductsWithQuantitiesAttribute()
    {
        return $this->getProductsWithQuantities();
    }
    /**
     * Relation avec les items (table pivot avec quantités)
     */
    public function items()
    {
        return $this->hasMany(QuoteRequestItem::class);
    }

    /**
     * Relation many-to-many avec les produits via la table pivot
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'quote_request_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

/**
 * Synchronise la table pivot à partir de selected_product_ids
 * Cette méthode convertit l'ancien format vers le nouveau
 */
public function syncItemsFromProductIds(): void
{
    if ($this->pivot_migrated || empty($this->selected_product_ids)) {
        return;
    }

    // Compter les occurrences de chaque product_id
    $quantities = array_count_values($this->selected_product_ids);
    
    // Synchroniser la table pivot
    $pivotData = [];
    foreach ($quantities as $productId => $quantity) {
        $pivotData[$productId] = ['quantity' => $quantity];
    }
    
    $this->products()->sync($pivotData);
    
    // ✅ FIX: Utiliser updateQuietly pour éviter les events
    $this->updateQuietly(['pivot_migrated' => true]);
}

/**
 * Met à jour selected_product_ids à partir de la table pivot
 * Pour maintenir la compatibilité avec le frontend
 */
public function updateProductIdsFromItems(): void
{
    if (!$this->pivot_migrated) {
        return;
    }

    $productIds = [];
    foreach ($this->items as $item) {
        for ($i = 0; $i < $item->quantity; $i++) {
            $productIds[] = $item->product_id;
        }
    }
    
    // ✅ FIX: Utiliser updateQuietly pour éviter les events
    $this->updateQuietly(['selected_product_ids' => $productIds]);
}

    /**
     * Obtient les quantités sous forme de tableau associatif
     * Utile pour l'API ou les vues
     */
    public function getQuantitiesAttribute(): array
    {
        if (!$this->pivot_migrated) {
            return array_count_values($this->selected_product_ids ?? []);
        }

        return $this->items->pluck('quantity', 'product_id')->toArray();
    }

    /**
     * Boot method pour synchroniser automatiquement
     */
    protected static function boot()
    {
        parent::boot();

        // ✅ FIX: Seulement après création, pas après update pour éviter boucle infinie
        static::created(function ($quoteRequest) {
            // Synchroniser uniquement si on a des product_ids et pas encore migré
            if (!$quoteRequest->pivot_migrated && !empty($quoteRequest->selected_product_ids)) {
                $quoteRequest->syncItemsFromProductIds();
            }
        });
    }
}
