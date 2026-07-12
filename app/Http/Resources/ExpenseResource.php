<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'trip_id' => $this->trip_id,
            'type' => $this->type?->value,
            'amount' => (float) $this->amount,
            'date' => $this->date?->toDateString(),
            'description' => $this->description,
            'receipt_path' => $this->receipt_path,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'trip' => new TripResource($this->whenLoaded('trip')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
