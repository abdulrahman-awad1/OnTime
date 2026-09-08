<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'personal_information' => [
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
                'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
                'gender' => $this->gender,
                'address' => $this->address,
            ],

            'medical_information' => [
                'blood_type' => $this->blood_type,
                'has_hypertension' => $this->has_hypertension,
                'has_diabetes' => $this->has_diabetes,
                'has_heart_disease' => $this->has_heart_disease,
                'has_previous_stroke' => $this->has_previous_stroke,
                'medical_notes' => $this->medical_notes,
            ],
        ];
    }
}
