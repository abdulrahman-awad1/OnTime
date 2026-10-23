<?php

namespace App\Services;

use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DoctorProfileService
{
    public function getPublicProfile(): ?DoctorProfile
    {
        return DoctorProfile::with('user:id,name')->first();
    }

    public function save(User $doctor, array $data): DoctorProfile
    {
        $profile = DoctorProfile::updateOrCreate(['user_id' => $doctor->id], $data);

        return $profile->load('user:id,name');
    }

    public function updatePhoto(User $doctor, UploadedFile $photo): DoctorProfile
    {
        $profile = DoctorProfile::where('user_id', $doctor->id)->first();

        if (! $profile) {
            throw new \RuntimeException('احفظ بيانات البروفايل الأول قبل رفع الصورة.');
        }

        if ($profile->photo_path) {
            Storage::disk('public')->delete($profile->photo_path);
        }

        $profile->update(['photo_path' => $photo->store('doctor-photos', 'public')]);

        return $profile->load('user:id,name');
    }
}
