<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'hosting_package_id',
        'order_number',
        'status',
        'billing_cycle',
        'price',
        'subtotal',
        'tax',
        'total',
        'next_billing_date',
        'approved_at',
        'provisioned_at',
        'activated_at',
        'cancelled_at',
        'cancellation_reason',
        'moneybird_invoice_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'next_billing_date' => 'datetime',
        'approved_at' => 'datetime',
        'provisioned_at' => 'datetime',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    /**
     * Generate a unique order number.
     */
    protected static function generateOrderNumber(): string
    {
        do {
            $number = 'HF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Get the customer that owns the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the hosting package for the order.
     */
    public function hostingPackage(): BelongsTo
    {
        return $this->belongsTo(HostingPackage::class);
    }

    /**
     * Get the domains for the order.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if order is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved orders.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
