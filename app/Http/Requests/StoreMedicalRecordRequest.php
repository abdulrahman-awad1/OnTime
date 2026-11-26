<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasOfflineSyncRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalRecordRequest extends FormRequest
{
    use HasOfflineSyncRules;

    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    public function rules(): array
    {
        return array_merge([
            'record_type' => ['required', Rule::in(['checkup', 'consultation', 'followup'])],
            'diagnosis' => ['required', 'string', 'max:255'],
            'treatment' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], $this->offlineSyncRules());
    }
}
