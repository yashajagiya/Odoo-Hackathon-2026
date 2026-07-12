<?php

namespace App\Http\Requests;

use App\Enums\DriverStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'license_number' => 'required|string|max:255|unique:drivers,license_number',
            'license_category' => 'nullable|string|max:50',
            'license_expiry_date' => 'nullable|date',
            'contact_number' => 'nullable|string|max:50',
            'safety_score' => 'nullable|numeric|min:0|max:100',
            'status' => ['nullable', Rule::enum(DriverStatus::class)],
            'user_id' => 'nullable|exists:users,id',
        ];
    }
}
