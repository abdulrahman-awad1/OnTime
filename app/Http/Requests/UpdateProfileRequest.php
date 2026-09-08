<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'patient';
    }

    public function rules(): array
    {
        return [
            'date_of_birth' => [
                'sometimes',
                'date',
                'before:today',
            ],

            'gender' => [
                'sometimes',
                'in:male,female',
            ],

            'address' => [
                'sometimes',
                'string',
                'max:1000',
            ],
        ];
    }
}
