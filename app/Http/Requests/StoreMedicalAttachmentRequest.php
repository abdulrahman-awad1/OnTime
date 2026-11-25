<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasOfflineSyncRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalAttachmentRequest extends FormRequest
{
    use HasOfflineSyncRules;

    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    public function rules(): array
    {
        return array_merge([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'type' => ['required', Rule::in(['xray', 'lab_result'])],
        ], $this->offlineSyncRules());
    }
}
