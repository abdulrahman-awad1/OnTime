<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    public function rules(): array
    {
        // التخصص إجباري أول مرة بس (لما البروفايل لسه مش موجود)
        $exists = $this->user()->doctorProfile()->exists();

        return [
            'specialty' => [$exists ? 'sometimes' : 'required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:100'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'bio' => ['nullable', 'string', 'max:2000'],

            'qualifications' => ['nullable', 'array', 'max:10'],
            'qualifications.*' => ['string', 'max:255'],

            'experiences' => ['nullable', 'array', 'max:15'],
            'experiences.*.place' => ['required', 'string', 'max:255'],
            'experiences.*.position' => ['nullable', 'string', 'max:255'],
            'experiences.*.from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'experiences.*.to' => ['nullable', 'integer', 'min:1950', 'max:2100'],
        ];
    }
}
