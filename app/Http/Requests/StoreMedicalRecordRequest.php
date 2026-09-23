<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    public function rules(): array
    {
        return [
            'record_type' => ['required', Rule::in(['checkup', 'consultation', 'followup'])],
            'diagnosis' => ['required', 'string', 'max:255'],
            'treatment' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
