<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClinicLocationResource;
use App\Http\Resources\TimeSlotResource;
use App\Models\ClinicLocation;
use App\Services\ClinicLocationService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;

class ClinicLocationController extends Controller
{
    use ApiResponse;

    public function __construct(private ClinicLocationService $clinicLocationService) {}

    public function index()
    {
        return $this->returnData(
            'clinic_locations',
            ClinicLocationResource::collection($this->clinicLocationService->all())
        );
    }

    public function timeSlots(Request $request, ClinicLocation $clinicLocation)
    {
        $request->validate([
            'date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $slots = $this->clinicLocationService->availableSlots(
            $clinicLocation,
            $request->date('date')?->toDateString()
        );

        return $this->returnData('time_slots', TimeSlotResource::collection($slots));
    }
}
