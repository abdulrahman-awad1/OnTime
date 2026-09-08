<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Services\ProfileService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProfileService $ProfileService
    ) {}

    public function show(Request $request)
    {
        $profile = $request->user()->profile;

        return $this->returnData(
            'profile',
            new ProfileResource($profile),
            'Patient profile retrieved successfully'
        );
    }

    public function store(StoreProfileRequest $request)
    {
        $user = $request->user();

        $data = $request->validated();

        $profile = $this->ProfileService->create(
            $user,
            $data
        );

        return $this->returnData(
            'profile',
            new ProfileResource($profile),
            'Patient profile created successfully'
        );
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $data = $request->validated();

        $profile = $this->ProfileService->updatePersonalInfo(
            $user,
            $data
        );

        return $this->returnData(
            'profile',
            new ProfileResource($profile),
            'Patient profile updated successfully'
        );
    }
}
