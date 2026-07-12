<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_number' => 'required|string|max:255|unique:vehicles,registration_number',
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'max_load_capacity_kg' => 'nullable|numeric|min:0',
            'odometer_km' => 'nullable|numeric|min:0',
            'acquisition_cost' => 'nullable|numeric|min:0',
            'status' => ['nullable', Rule::enum(VehicleStatus::class)],
            'region' => 'nullable|string|max:255',
        ];
    }
}
