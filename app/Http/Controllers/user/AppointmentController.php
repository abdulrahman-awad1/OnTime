<?php

namespace App\Http\Controllers\User;

use App\Exceptions\SlotNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(private AppointmentService $appointmentService) {}

    public function store(StoreAppointmentRequest $request)
    {
        try {
            $appointment = $this->appointmentService->book(
                $request->user(),
                $request->validated('time_slot_id'),
                $request->validated('visit_type'),
            );

            return $this->returnData('appointment', new AppointmentResource($appointment), 'تم الحجز بنجاح', 201);

        } catch (SlotNotAvailableException $e) {
            return $this->returnError('E101', $e->getMessage(), 409);

        } catch (Throwable $e) {
            Log::error('Appointment booking failed', [
                'time_slot_id' => $request->validated('time_slot_id'),
                'patient_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->returnError('E500', 'حصل خطأ أثناء الحجز، حاول تاني.', 500);
        }
    }

    public function myAppointments(Request $request)
    {
        $appointments = $this->appointmentService->myAppointments($request->user());

        return $this->returnData('appointments', AppointmentResource::collection($appointments));
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        try {
            $cancelled = $this->appointmentService->cancel($request->user(), $appointment);

            if (! $cancelled) {
                return $this->returnError('E403', 'غير مصرح لك', 403);
            }

            return $this->successMessage('تم إلغاء الحجز');

        } catch (\RuntimeException $e) {
            return $this->returnError('E105', $e->getMessage(), 502);
        }
    }
}
