<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SlotNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWalkinAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AdminAppointmentService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminAppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(private AdminAppointmentService $adminAppointmentService) {}

    public function index(Request $request)
    {
        $appointments = $this->adminAppointmentService->list($request);

        return $this->returnData('appointments', AppointmentResource::collection($appointments));
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate(['status' => ['required', Rule::in(['completed', 'no_show', 'cancelled'])]]);

        $updated = $this->adminAppointmentService->updateStatus($appointment, $request->string('status')->toString());

        return $this->returnData('appointment', new AppointmentResource($updated), 'تم تحديث حالة الحجز');
    }

    public function confirmCashPayment(Appointment $appointment)
    {
        try {
            $updated = $this->adminAppointmentService->confirmCashPayment($appointment);

            return $this->returnData('appointment', new AppointmentResource($updated), 'تم تأكيد استلام الدفع نقدًا');
        } catch (\RuntimeException $e) {
            return $this->returnError('E103', $e->getMessage(), 422);
        }
    }

    // POST /api/admin/appointments/walk-in
    public function bookWalkIn(StoreWalkinAppointmentRequest $request)
    {
        try {
            $appointment = $this->adminAppointmentService->bookWalkIn($request->validated());

            return $this->returnData('appointment', new AppointmentResource($appointment), 'تم الحجز بنجاح', 201);
        } catch (SlotNotAvailableException $e) {
            return $this->returnError('E101', $e->getMessage(), 409);
        }
    }

    // POST /api/admin/appointments/{appointment}/cancel
    public function cancel(Appointment $appointment)
    {
        try {
            $this->adminAppointmentService->cancelByStaff($appointment);

            return $this->successMessage('تم إلغاء الحجز');
        } catch (\Exception $e) {
            return $this->returnError('E105', 'حصلت مشكلة أثناء الإلغاء أو الاسترجاع.', 502);
        }
    }
}
