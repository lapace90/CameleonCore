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
use App\State\InvoiceProvider;
use App\State\InvoiceProcessor;
use Carbon\Carbon;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/admin/invoices', provider: InvoiceProvider::class, security: "is_granted('ROLE_ADMIN')"),
        new Get(uriTemplate: '/admin/invoices/{id}', provider: InvoiceProvider::class, security: "is_granted('ROLE_ADMIN')"),
        new Post(uriTemplate: '/admin/invoices', processor: InvoiceProcessor::class, security: "is_granted('ROLE_ADMIN')"),
        new Put(uriTemplate: '/admin/invoices/{id}', processor: InvoiceProcessor::class, security: "is_granted('ROLE_ADMIN')"),
        new Delete(uriTemplate: '/admin/invoices/{id}', processor: InvoiceProcessor::class, security: "is_granted('ROLE_ADMIN')"),
        new Post(uriTemplate: '/admin/invoices/{id}/mark-paid', processor: InvoiceProcessor::class, security: "is_granted('ROLE_ADMIN')"),
        new Post(uriTemplate: '/admin/invoices/{id}/send-email', processor: InvoiceProcessor::class, security: "is_granted('ROLE_ADMIN')"),
        new Get(uriTemplate: '/admin/invoices/{id}/pdf', provider: InvoiceProvider::class, security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(uriTemplate: '/admin/invoices/stats', provider: InvoiceProvider::class, security: "is_granted('ROLE_ADMIN')"),
    ]
)]
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'amount',
        'issue_date',
        'due_date',
        'status',
        'customer_id',
        'reservation_id',
        'payment_method',
        'payment_date',
        'notes',
        'pdf_path',
        'sent_at',
        'sent_count',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'sent_at' => 'datetime',
        'sent_count' => 'integer',
    ];

    public const STATUS_UNPAID = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'canceled';

    public const PAYMENT_METHOD_CARD = 'card';
    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_TRANSFER = 'transfer';
    public const PAYMENT_METHOD_CHECK = 'check';

    // Relations
    public function customer() { return $this->belongsTo(Customer::class); }
    public function reservation() { return $this->belongsTo(Reservation::class); }

    // Scopes
    public function scopeUnpaid($q) { return $q->where('status', self::STATUS_UNPAID); }
    public function scopePaid($q) { return $q->where('status', self::STATUS_PAID); }
    public function scopeOverdue($q) { return $q->where('status', self::STATUS_UNPAID)->where('due_date', '<', Carbon::now()); }
    public function scopeCancelled($q) { return $q->where('status', self::STATUS_CANCELLED); }
    public function scopeThisMonth($q) { return $q->whereYear('issue_date', Carbon::now()->year)->whereMonth('issue_date', Carbon::now()->month); }
    public function scopeBetweenDates($q, $start, $end) { return $q->whereBetween('issue_date', [$start, $end]); }
    public function scopeForCustomer($q, $id) { return $q->where('customer_id', $id); }
    public function scopeForReservation($q, $id) { return $q->where('reservation_id', $id); }

    // Accessors
    public function getFormattedAmountAttribute() { return number_format($this->amount, 2, ',', ' ') . ' €'; }
    public function getFormattedIssueDateAttribute() { return $this->issue_date->format('d/m/Y'); }
    public function getFormattedDueDateAttribute() { return $this->due_date->format('d/m/Y'); }
    public function getStatusLabelAttribute() { return match($this->status) { self::STATUS_PAID => 'Payée', self::STATUS_UNPAID => 'Non payée', self::STATUS_OVERDUE => 'En retard', self::STATUS_CANCELLED => 'Annulée', default => 'Inconnu' }; }
    public function getStatusColorAttribute() { return match($this->status) { self::STATUS_PAID => 'success', self::STATUS_UNPAID => 'warning', self::STATUS_OVERDUE => 'danger', self::STATUS_CANCELLED => 'secondary', default => 'secondary' }; }
    public function getIsOverdueAttribute() { return $this->status === self::STATUS_UNPAID && $this->due_date < Carbon::now(); }
    public function getIsPaidAttribute() { return $this->status === self::STATUS_PAID; }
    public function getDaysOverdueAttribute() { return !$this->is_overdue ? 0 : Carbon::now()->diffInDays($this->due_date); }
    public function getDaysUntilDueAttribute() { return ($this->is_paid || $this->is_overdue) ? 0 : $this->due_date->diffInDays(Carbon::now()); }
    public function getCustomerNameAttribute() { return !$this->customer ? 'Client inconnu' : trim($this->customer->name . ' ' . ($this->customer->last_name ?? '')); }
    
    public function getEmailDataAttribute() { 
        return [
            'invoice_number' => $this->invoice_number,
            'customer' => ['name' => $this->customer?->name ?? 'Client', 'last_name' => $this->customer?->last_name ?? '', 'email' => $this->customer?->email ?? '', 'phone' => $this->customer?->phone ?? ''],
            'amount' => ['raw' => $this->amount, 'formatted' => $this->formatted_amount],
            'dates' => ['issue_date' => $this->formatted_issue_date, 'due_date' => $this->formatted_due_date],
            'reservation' => $this->reservation ? ['checkin' => $this->reservation->checkin?->format('d/m/Y'), 'checkout' => $this->reservation->checkout?->format('d/m/Y'), 'product' => $this->reservation->product?->name ?? 'N/A'] : null,
            'status' => ['code' => $this->status, 'label' => $this->status_label],
        ];
    }

    // Méthodes métier
    public function canBePaid() { return in_array($this->status, [self::STATUS_UNPAID, self::STATUS_OVERDUE]); }
    public function canBeModified() { return $this->status !== self::STATUS_PAID; }
    public function canBeCancelled() { return $this->status !== self::STATUS_PAID; }
    
    public function markAsPaid(?string $method = null) { 
        $this->update(['status' => self::STATUS_PAID, 'payment_date' => Carbon::now(), 'payment_method' => $method]); 
    }
    
    public function markAsCancelled() { $this->update(['status' => self::STATUS_CANCELLED]); }
    public function updateOverdueStatus() { if ($this->is_overdue && $this->status === self::STATUS_UNPAID) $this->update(['status' => self::STATUS_OVERDUE]); }
    
    public static function generateInvoiceNumber() { 
        $year = date('Y'); $month = date('m');
        $last = self::where('invoice_number', 'like', "INV-{$year}-{$month}-%")->orderBy('id', 'desc')->first();
        $num = $last ? ((int) explode('-', $last->invoice_number)[3]) + 1 : 1;
        return sprintf('INV-%s-%s-%04d', $year, $month, $num);
    }
    
    public static function existsForReservation($id) { return self::where('reservation_id', $id)->exists(); }

    protected static function boot() {
        parent::boot();
        static::creating(function ($inv) {
            if (empty($inv->invoice_number)) $inv->invoice_number = self::generateInvoiceNumber();
            if (empty($inv->issue_date)) $inv->issue_date = Carbon::now();
            if (empty($inv->due_date)) $inv->due_date = Carbon::now()->addDays(30);
        });
        static::retrieved(function ($inv) { $inv->updateOverdueStatus(); });
    }
}