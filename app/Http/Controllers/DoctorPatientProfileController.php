<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePatientMedicalInfoRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Services\ProfileService;
use App\trait\ApiResponse;
use App\Policies\ProfilePolicy;

class DoctorPatientProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProfileService $ProfileService
    ) {}

    public function updateMedicalInfo(
        UpdatePatientMedicalInfoRequest $request,
        Profile $patientProfile
    ) {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'Unauthorized');
        }

        $data = $request->validated();

        $profile = $this->ProfileService->updateMedicalInfo(
            $patientProfile,
            $data
        );

        return $this->returnData(
            'profile',
            new ProfileResource($profile),
            'Patient medical information updated successfully'
        );
    }
}
