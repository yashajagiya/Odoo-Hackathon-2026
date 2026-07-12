<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:drivers,id',
            'cargo_weight_kg' => 'nullable|numeric|min:0',
            'planned_distance_km' => 'nullable|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
        ];
    }
}
