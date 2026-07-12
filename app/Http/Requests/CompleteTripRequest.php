<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_distance_km' => 'nullable|numeric|min:0',
            'fuel_consumed_liters' => 'nullable|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
        ];
    }
}
