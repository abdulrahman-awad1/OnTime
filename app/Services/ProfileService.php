<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function create(User $user, array $data): Profile
    {
        return DB::transaction(function () use ($user, $data) {

            $profile = $user->profile()->create([
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'address' => $data['address'],
            ]);

            $user->update([
                'profile_completed' => true,
            ]);

            return $profile->fresh();
        });
    }

    public function updatePersonalInfo(
        User $user,
        array $data
    ): Profile {

        $profile = $user->profile;

        $profile->update([
            'date_of_birth' => $data['date_of_birth'] ?? $profile->date_of_birth,
            'gender' => $data['gender'] ?? $profile->gender,
            'address' => $data['address'] ?? $profile->address,
        ]);

        return $profile->fresh();
    }

    public function updateMedicalInfo(
        Profile $patientProfile,
        array $data
    ): Profile {

        $patientProfile->update([
            'blood_type' => $data['blood_type'] ?? null,
            'has_hypertension' => $data['has_hypertension'] ?? null,
            'has_diabetes' => $data['has_diabetes'] ?? null,
            'has_heart_disease' => $data['has_heart_disease'] ?? null,
            'has_previous_stroke' => $data['has_previous_stroke'] ?? null,
            'medical_notes' => $data['medical_notes'] ?? null,
        ]);

        return $patientProfile->fresh();
    }
}
