<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function updateMedicalInfo(
        User $user,
        Profile $patientProfile
    ): bool {
        return $user->role === 'doctor';
    }
}
