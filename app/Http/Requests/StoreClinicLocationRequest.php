<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'latitude'  => $this->filled('latitude') ? $this->latitude : 30.0444,
            'longitude' => $this->filled('longitude') ? $this->longitude : 31.2357,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'consultation_price' => ['required', 'numeric', 'min:0'],
            'checkup_price' => ['required', 'numeric', 'min:0'],
        ];
    }

}
