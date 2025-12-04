<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'customer_id',
        'domain_name',
        'tld',
        'status',
        'registered_at',
        'expires_at',
        'openprovider_domain_id',
        'plesk_domain_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'registered_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that owns the domain.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the customer that owns the domain.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if domain is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if domain is registered.
     */
    public function isRegistered(): bool
    {
        return $this->status === 'registered' || $this->status === 'active';
    }

    /**
     * Check if domain is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get days until expiration.
     */
    public function daysUntilExpiration(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Scope a query to only include active domains.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include expiring soon domains.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now());
    }
}
