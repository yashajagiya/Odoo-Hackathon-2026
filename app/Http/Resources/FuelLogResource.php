<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuelLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'trip_id' => $this->trip_id,
            'liters' => (float) $this->liters,
            'cost_per_liter' => (float) $this->cost_per_liter,
            'total_cost' => (float) $this->total_cost,
            'date' => $this->date?->toDateString(),
            'odometer_km' => $this->odometer_km ? (float) $this->odometer_km : null,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'trip' => new TripResource($this->whenLoaded('trip')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
