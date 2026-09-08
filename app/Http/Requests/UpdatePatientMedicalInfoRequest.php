<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientMedicalInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    public function rules(): array
    {
        return [
            'blood_type' => [
                'nullable',
                'in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            ],

            'has_hypertension' => [
                'nullable',
                'boolean',
            ],

            'has_diabetes' => [
                'nullable',
                'boolean',
            ],

            'has_heart_disease' => [
                'nullable',
                'boolean',
            ],

            'has_previous_stroke' => [
                'nullable',
                'boolean',
            ],

            'medical_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
