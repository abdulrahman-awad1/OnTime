<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDoctorPhotoRequest;
use App\Http\Requests\UpdateDoctorProfileRequest;
use App\Http\Resources\DoctorProfileResource;
use App\Services\DoctorProfileService;
use App\trait\ApiResponse;

class DoctorProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private DoctorProfileService $service) {}

    public function show()
    {
        $profile = $this->service->getPublicProfile();

        if (! $profile) {
            return $this->returnData('doctor', null, 'بيانات الدكتور لسه ماتضافتش');
        }

        return $this->returnData('doctor', new DoctorProfileResource($profile));
    }

    // PUT /doctor/profile
    public function update(UpdateDoctorProfileRequest $request)
    {
        $profile = $this->service->save($request->user(), $request->validated());

        return $this->returnData('doctor', new DoctorProfileResource($profile), 'تم حفظ بيانات البروفايل');
    }

    public function updatePhoto(UpdateDoctorPhotoRequest $request)
    {
        try {
            $profile = $this->service->updatePhoto($request->user(), $request->file('photo'));

            return $this->returnData('doctor', new DoctorProfileResource($profile), 'تم تحديث الصورة');
        } catch (\RuntimeException $e) {
            return $this->returnError('E301', $e->getMessage(), 422);
        }
    }
}
