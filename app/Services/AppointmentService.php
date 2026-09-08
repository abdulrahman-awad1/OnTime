<?php

namespace App\Services;

use App\Events\TimeSlotBooked;
use App\Exceptions\SlotNotAvailableException;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    public function __construct(private PaymobService $paymob) {}

    public function book(User $patient, int $timeSlotId, string $visitType): Appointment
    {
        $appointment = DB::transaction(function () use ($patient, $timeSlotId, $visitType) {
            $slot = TimeSlot::where('id', $timeSlotId)->lockForUpdate()->first();

            if (! $slot || $slot->status !== 'available') {
                throw new SlotNotAvailableException();
            }
            if ($slot->date->isPast()) {
                throw new SlotNotAvailableException('الموعد ده فات معاده، اختار ميعاد تاني.');
            }

            $slot->update(['status' => 'booked']);

            return Appointment::create([
                'user_id' => $patient->id,
                'time_slot_id' => $slot->id,
                'visit_type' => $visitType,
            ])->load('timeSlot.clinicLocation');
        });

        TimeSlotBooked::dispatch($appointment->time_slot_id, $appointment->timeSlot->clinic_location_id);

        return $appointment;
    }

    /**
     * إلغاء الحجز - المنطق بيتفرّع حسب حالة الدفع:
     * - لسه مادفعش: إلغاء عادي، الموعد يرجع متاح.
     * - دفع أونلاين ومدفوع: استرجاع فعلي عن طريق Paymob قبل الإلغاء.
     * - دفع نقدي ومدفوع: مينفعش استرجاع أوتوماتيك، بيتعلّم "يحتاج استرجاع يدوي".
     */
    public function cancel(User $patient, Appointment $appointment): bool
    {
        if ($appointment->user_id !== $patient->id) {
            return false;
        }

        $payment = $appointment->payment;

        // الحالة 1: مدفوع أونلاين - نرجّع الفلوس فعليًا قبل الإلغاء
        if ($appointment->payment_status === 'paid' && $payment && $payment->method !== 'cash') {
            try {
                $token = $this->paymob->authenticate();
                $this->paymob->refund($token, $payment->transaction_id, (float) $payment->amount);

                DB::transaction(function () use ($appointment, $payment) {
                    $payment->update(['status' => 'refunded']);
                    $appointment->update(['status' => 'cancelled', 'payment_status' => 'refunded']);
                    $appointment->timeSlot()->update(['status' => 'available']);
                });

                return true;

            } catch (\Exception $e) {
                Log::error('Paymob refund failed', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);

                // مبنلغيش الحجز لو الاسترجاع فشل - عشان محدش ياخد الموعد
                // والمريض يفضل مدفوع من غير ما يرجعله فلوسه
                throw new \RuntimeException('حصلت مشكلة في استرجاع المبلغ، جرب تاني أو كلّم العيادة.');
            }
        }

        // الحالة 2: مدفوع نقدي - نعلّمه "يحتاج استرجاع يدوي" ونكمل الإلغاء
        if ($appointment->payment_status === 'paid' && $payment && $payment->method === 'cash') {
            DB::transaction(function () use ($appointment, $payment) {
                $payment->update(['status' => 'refunded']); // الاسترجاع هيتم يدوي في العيادة
                $appointment->update(['status' => 'cancelled', 'payment_status' => 'refunded']);
                $appointment->timeSlot()->update(['status' => 'available']);
            });

            Log::info('Cash refund needed manually', ['appointment_id' => $appointment->id]);

            return true;
        }

        // الحالة 3: لسه مادفعش - إلغاء عادي
        DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => 'cancelled']);
            $appointment->timeSlot()->update(['status' => 'available']);
        });

        return true;
    }

    public function myAppointments(User $patient): LengthAwarePaginator
    {
        return Appointment::with('timeSlot.clinicLocation')
            ->where('user_id', $patient->id)
            ->latest()
            ->paginate(10);
    }
}
