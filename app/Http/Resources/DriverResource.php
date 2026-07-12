<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'license_number' => $this->license_number,
            'license_category' => $this->license_category,
            'license_expiry_date' => $this->license_expiry_date?->toDateString(),
            'contact_number' => $this->contact_number,
            'safety_score' => (float) $this->safety_score,
            'status' => $this->status?->value,
            'user_id' => $this->user_id,
            'is_license_expired' => $this->is_license_expired,
            'days_until_expiry' => $this->days_until_expiry,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
