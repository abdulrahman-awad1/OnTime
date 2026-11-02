<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Services\ProfileService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $updatedUser = DB::transaction(function () use ($user, $data) {
            $user->update([
                'name'              => $data['name'],
                'profile_completed' => true,
            ]);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth' => $data['date_of_birth'],
                    'gender'        => $data['gender'],
                    'address'       => $data['address'] ?? null,
                ]
            );

            return $user->load('profile');
        });

        return response()->json([
            'status'  => true,
            'message' => 'تم استكمال الملف الشخصي بنجاح',
            'data'    => $updatedUser, // أو new UserResource($updatedUser)
        ]);
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
