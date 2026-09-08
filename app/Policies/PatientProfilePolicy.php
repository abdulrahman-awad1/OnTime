<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

class PatientProfilePolicy
{
    public function updateMedicalInfo(
        User $user,
        Profile $patientProfile
    ): bool {
        return $user->role === 'doctor';
    }
}
