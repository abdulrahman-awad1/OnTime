<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SlotNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewPatientWalkinRequest;
use App\Http\Requests\StoreWalkinAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\FilterAppointmentRsource;
use App\Models\Appointment;
use App\Models\User;
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

        $resourceCollection = FilterAppointmentRsource::collection($appointments);

        return response()->json([
            'status' => true,
            'errNum' => '0000',
            'msg'    => 'success',
            'data'   => $resourceCollection->items(), // قائمة الحجوزات فقط
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'last_page'    => $appointments->lastPage(),
                'per_page'     => $appointments->perPage(),
                'total'        => $appointments->total(),
                'has_more'     => $appointments->hasMorePages(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate(['status' => ['required', Rule::in(['completed', 'no_show'])]]);

        $updated = $this->adminAppointmentService->updateStatus($appointment, $request->string('status')->toString());

        return $this->returnData('appointment', new AppointmentResource($updated), 'تم تحديث حالة الحجز');
    }

    public function setCurrentAppointment(Appointment $appointment)
    {
        try {
            $updated = $this->adminAppointmentService->setCurrentAppointment($appointment);

            return $this->returnData('appointment', new AppointmentResource($updated), 'تم تفعيل الموعد كحالي');
        } catch (\RuntimeException $e) {
            return $this->returnError('E107', $e->getMessage(), 422);
        }
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
    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return $this->returnData('patients', []);
        }

        $patients = User::where('role', 'patient') // أو حسب تصميم الجدول عندك للـ Patients
        ->where(function ($q) use ($query) {
            $q->where('phone', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%");
        })
            ->limit(10)
            ->get(['id', 'name', 'phone']);

        return $this->returnData('patients', $patients);
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

    public function bookWalkInNewPatient(StoreNewPatientWalkinRequest $request)
    {
        try {
            $appointment = $this->adminAppointmentService->bookWalkInForNewPatient($request->validated());

            return $this->returnData('appointment', new AppointmentResource($appointment), 'تم تسجيل المريض وحجز الموعد بنجاح', 201);
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
