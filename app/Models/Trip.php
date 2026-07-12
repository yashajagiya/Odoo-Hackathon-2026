<?php

namespace App\Models;

use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'destination',
        'vehicle_id',
        'driver_id',
        'cargo_weight_kg',
        'planned_distance_km',
        'actual_distance_km',
        'fuel_consumed_liters',
        'revenue',
        'status',
        'dispatched_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'cargo_weight_kg' => 'decimal:2',
            'planned_distance_km' => 'decimal:2',
            'actual_distance_km' => 'decimal:2',
            'fuel_consumed_liters' => 'decimal:2',
            'revenue' => 'decimal:2',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->whereIn('status', [TripStatus::Draft, TripStatus::Dispatched]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', TripStatus::Completed);
    }
}
