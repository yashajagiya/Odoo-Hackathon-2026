<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'name' => $this->name,
            'model' => $this->model,
            'type' => $this->type,
            'max_load_capacity_kg' => (float) $this->max_load_capacity_kg,
            'odometer_km' => (float) $this->odometer_km,
            'acquisition_cost' => (float) $this->acquisition_cost,
            'status' => $this->status?->value,
            'region' => $this->region,
            'operational_cost' => $this->when($request->routeIs('vehicles.show'), $this->operational_cost),
            'total_revenue' => $this->when($request->routeIs('vehicles.show'), $this->total_revenue),
            'trips_count' => $this->when($this->trips_count !== null, $this->trips_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
