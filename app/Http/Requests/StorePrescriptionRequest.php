<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_name' => ['required', 'string', 'max:255'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
        ];
    }
}
