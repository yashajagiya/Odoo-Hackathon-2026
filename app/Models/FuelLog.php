<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'trip_id',
        'liters',
        'cost_per_liter',
        'total_cost',
        'date',
        'odometer_km',
    ];

    protected function casts(): array
    {
        return [
            'liters' => 'decimal:2',
            'cost_per_liter' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'odometer_km' => 'decimal:2',
            'date' => 'date',
        ];
    }

    // ── Relationships ──

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
