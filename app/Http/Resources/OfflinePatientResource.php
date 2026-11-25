<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfflinePatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->profile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'profile' => $profile ? [
                'id' => $profile->id,
                'date_of_birth' => $profile->date_of_birth?->toDateString(),
                'gender' => $profile->gender,
                'blood_type' => $profile->blood_type,
                'has_hypertension' => $profile->has_hypertension,
                'has_diabetes' => $profile->has_diabetes,
                'has_heart_disease' => $profile->has_heart_disease,
                'has_previous_stroke' => $profile->has_previous_stroke,
                'medical_notes' => $profile->medical_notes,
            ] : null,
            'recent_records' => OfflineMedicalRecordResource::collection($this->medicalRecords),
        ];
    }
}
