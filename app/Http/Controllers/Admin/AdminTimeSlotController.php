<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Timeslotoverlapexception;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimeSlotRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\TimeSlot;
use App\Services\TimeSlotService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;

class AdminTimeSlotController extends Controller
{
    use ApiResponse;

    public function __construct(private TimeSlotService $timeSlotService) {}

    public function index(Request $request)
    {
        $slots = $this->timeSlotService->list($request);

        return $this->returnData('time_slots', TimeSlotResource::collection($slots));
    }

    public function store(StoreTimeSlotRequest $request)
    {
        try {
            $slot = $this->timeSlotService->create($request->validated());

            return $this->returnData('time_slot', new TimeSlotResource($slot), 'تم إضافة الموعد', 201);

        } catch (TimeSlotOverlapException $e) {
            return $this->returnError('E201', $e->getMessage(), 422);
        }
    }

    public function destroy(TimeSlot $timeSlot)
    {
        try {
            $this->timeSlotService->delete($timeSlot);

            return $this->successMessage('تم حذف الموعد');

        } catch (\RuntimeException $e) {
            return $this->returnError('E202', $e->getMessage(), 422);
        }
    }
}
