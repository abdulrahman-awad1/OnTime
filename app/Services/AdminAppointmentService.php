<?php

namespace App\Services;

use App\Exceptions\SlotNotAvailableException;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAppointmentService
{
    public function __construct(private PaymobService $paymob) {}

    public function list(Request $request): LengthAwarePaginator
    {
        $query = Appointment::with(['patient:id,name,phone', 'timeSlot.clinicLocation']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('date')) {
            $query->whereHas('timeSlot', fn ($q) => $q->whereDate('date', $request->date('date')->toDateString()));
        }

        return $query->latest()->paginate(20);
    }

    public function updateStatus(Appointment $appointment, string $status): Appointment
    {
        $appointment->update(['status' => $status]);

        if ($status === 'cancelled') {
            $appointment->timeSlot()->update(['status' => 'available']);
        }

        return $appointment->fresh('timeSlot.clinicLocation');
    }

    public function confirmCashPayment(Appointment $appointment): Appointment
    {
        // ⚠️ الإصلاح: نتأكد إن الحجز لسه confirmed (أو in_progress - المريض واصل فعلاً)
        // قبل ما نقبل تأكيد أي دفع عليه
        if (! in_array($appointment->status, ['confirmed', 'in_progress'])) {
            throw new \RuntimeException('الحجز ده ملغي أو منتهي، مينفعش تأكد دفع عليه.');
        }

        $payment = $appointment->payment;

        if (! $payment || $payment->method !== 'cash') {
            throw new \RuntimeException('الحجز ده مش مسجّل عليه دفع نقدي.');
        }
        if ($payment->status === 'paid') {
            throw new \RuntimeException('الدفع ده اتأكد بالفعل.');
        }

        $payment->update(['status' => 'paid']);
        $appointment->update(['payment_status' => 'paid']);

        return $appointment->fresh(['timeSlot.clinicLocation', 'payment']);
    }

    /**
     * حجز موعد نيابة عن مريض حاضر فعليًا في العيادة (Walk-in).
     * الدفع بيتأكد فورًا (مش زي الأونلاين اللي بيستنى webhook) لأن
     * الأسيستانت واقف قدام المريض ولسه بياخد منه الفلوس مباشرة.
     */
    public function bookWalkIn(array $data): Appointment
    {
        $patient = $this->findOrCreatePatient($data);

        $appointment = DB::transaction(function () use ($patient, $data) {
            $slot = TimeSlot::where('id', $data['time_slot_id'])->lockForUpdate()->first();

            if (! $slot || $slot->status !== 'available') {
                throw new SlotNotAvailableException();
            }

            $slot->update(['status' => 'booked']);

            $appointment = Appointment::create([
                'user_id' => $patient->id,
                'time_slot_id' => $slot->id,
                'visit_type' => $data['visit_type'],
                'payment_status' => 'paid', // بيتأكد فورًا - الأسيستانت استلم الفلوس بالفعل
            ]);

            Payment::create([
                'appointment_id' => $appointment->id,
                'user_id' => $patient->id,
                'provider' => 'in_person',
                'amount' => $appointment->load('timeSlot.clinicLocation')->price(),
                'method' => $data['pay_method'],
                'status' => 'paid',
            ]);

            return $appointment;
        });

        return $appointment->load('timeSlot.clinicLocation', 'patient', 'payment');
    }

    /**
     * إلغاء أي حجز من طرف الأدمن/الأسيستانت (مش بس المريض نفسه).
     * بيشغّل نفس منطق الاسترجاع بتاع المريض بالظبط.
     */
    public function cancelByStaff(Appointment $appointment): void
    {
        $payment = $appointment->payment;

        if ($appointment->payment_status === 'paid' && $payment && $payment->method !== 'cash' && $payment->provider === 'paymob') {
            $token = $this->paymob->authenticate();
            $this->paymob->refund($token, $payment->transaction_id, (float) $payment->amount);

            DB::transaction(function () use ($appointment, $payment) {
                $payment->update(['status' => 'refunded']);
                $appointment->update(['status' => 'cancelled', 'payment_status' => 'refunded']);
                $appointment->timeSlot()->update(['status' => 'available']);
            });

            return;
        }

        if ($appointment->payment_status == 'paid' && $payment) {
            // نقدي أو حصل في العيادة - الاسترجاع بيتم يدوي من الأسيستانت نفسه
            DB::transaction(function () use ($appointment, $payment) {
                $payment->update(['status' => 'refunded']);
                $appointment->update(['status' => 'cancelled', 'payment_status' => 'refunded']);
                $appointment->timeSlot()->update(['status' => 'available']);
            });

            return;
        }

        DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => 'cancelled']);
            $appointment->timeSlot()->update(['status' => 'available']);
        });
    }

    private function findOrCreatePatient(array $data): User
    {
        if (! empty($data['patient_id'])) {
            return User::findOrFail($data['patient_id']);
        }

        // نبحث بالتليفون الأول قبل ما نعمل حساب جديد - يمنع تكرار نفس المريض
        $existing = User::where('phone', $data['patient_phone'])->where('role', 'patient')->first();
        if ($existing) {
            return $existing;
        }

        return User::create([
            'name' => $data['patient_name'],
            'phone' => $data['patient_phone'],
            'email' => $data['patient_email'] ?? Str::random(10).'@walkin.local',
            'password' => Hash::make(Str::random(20)), // المريض ممكن يعمل "نسيت كلمة المرور" لاحقًا
            'role' => 'patient',
        ]);
    }
}
