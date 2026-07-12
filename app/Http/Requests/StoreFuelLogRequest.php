<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|exists:vehicles,id',
            'trip_id' => 'nullable|exists:trips,id',
            'liters' => 'required|numeric|min:0.01',
            'cost_per_liter' => 'required|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'odometer_km' => 'nullable|numeric|min:0',
        ];
    }
}
