<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostingPackage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_period',
        'disk_space_mb',
        'bandwidth_gb',
        'email_accounts',
        'databases',
        'domains',
        'subdomains',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'disk_space_mb' => 'integer',
        'bandwidth_gb' => 'integer',
        'email_accounts' => 'integer',
        'databases' => 'integer',
        'domains' => 'integer',
        'subdomains' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the orders for the hosting package.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope a query to only include active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '€'.number_format($this->price, 2, ',', '.');
    }

    /**
     * Get formatted disk space.
     */
    public function getFormattedDiskSpaceAttribute(): string
    {
        if ($this->disk_space_mb >= 1024) {
            return round($this->disk_space_mb / 1024, 1).' GB';
        }

        return $this->disk_space_mb.' MB';
    }

    /**
     * Get formatted bandwidth.
     */
    public function getFormattedBandwidthAttribute(): string
    {
        return $this->bandwidth_gb.' GB';
    }
}
