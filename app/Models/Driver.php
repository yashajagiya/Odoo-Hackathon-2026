<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'license_number',
        'license_category',
        'license_expiry_date',
        'contact_number',
        'safety_score',
        'status',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => DriverStatus::class,
            'license_expiry_date' => 'date',
            'safety_score' => 'decimal:2',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    // ── Scopes ──

    public function scopeAvailable($query)
    {
        return $query->where('status', DriverStatus::Available);
    }

    public function scopeDispatchable($query)
    {
        return $query->where('status', DriverStatus::Available)
                     ->where('license_expiry_date', '>=', now()->toDateString());
    }

    // ── Computed Attributes ──

    /**
     * Check if the driver's license is expired.
     */
    public function getIsLicenseExpiredAttribute(): bool
    {
        if (!$this->license_expiry_date) {
            return false;
        }

        return $this->license_expiry_date->isPast();
    }

    /**
     * Get days until license expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->license_expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->license_expiry_date, false);
    }
}
