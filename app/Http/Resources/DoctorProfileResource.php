<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DoctorProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->user->name,
            'specialty' => $this->specialty,
            'title' => $this->title,
            'years_of_experience' => $this->years_of_experience,
            'bio' => $this->bio,
            'qualifications' => $this->qualifications ?? [],
            'experiences' => $this->experiences ?? [],
            'photo_url' => $this->photo_path
                ? Storage::disk('public')->url($this->photo_path)
                : null,
        ];
    }
}
