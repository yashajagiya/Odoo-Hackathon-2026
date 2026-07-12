<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'document_type' => $this->document_type,
            'file_path' => $this->file_path,
            'issue_date' => $this->issue_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'is_expired' => $this->is_expired,
            'days_until_expiry' => $this->days_until_expiry,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'download_url' => url('/api/vehicle-documents/' . $this->id . '/download'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
