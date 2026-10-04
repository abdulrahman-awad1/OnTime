<?php

namespace App\Services;

use App\Models\ClinicLocation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use App\Http\Resources\TimeSlotResource;

class ClinicLocationService
{
    public function all(): Collection
    {
        return ClinicLocation::all();
    }

    public function availableSlots(ClinicLocation $clinicLocation, ?string $date = null): AnonymousResourceCollection
    {
        $targetDate = $date ?? now()->toDateString();

        $slots = $clinicLocation->timeSlots()
            ->where('status', 'available')
            ->whereDate('date', $targetDate)
            ->when($targetDate === now()->toDateString(), function ($query) {
                $query->where('start_time', '>', now()->toTimeString());
            })
            ->orderBy('start_time')
            ->get();

        return TimeSlotResource::collection($slots);
    }

}
