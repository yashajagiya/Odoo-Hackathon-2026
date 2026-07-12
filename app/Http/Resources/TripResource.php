<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'destination' => $this->destination,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'cargo_weight_kg' => (float) $this->cargo_weight_kg,
            'planned_distance_km' => (float) $this->planned_distance_km,
            'actual_distance_km' => $this->actual_distance_km ? (float) $this->actual_distance_km : null,
            'fuel_consumed_liters' => $this->fuel_consumed_liters ? (float) $this->fuel_consumed_liters : null,
            'revenue' => (float) $this->revenue,
            'status' => $this->status?->value,
            'dispatched_at' => $this->dispatched_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'driver' => new DriverResource($this->whenLoaded('driver')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
