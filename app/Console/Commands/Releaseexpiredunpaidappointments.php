<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Releaseexpiredunpaidappointments extends Command
{
    protected $signature = 'appointments:release-expired';

    protected $description = 'يلغي الحجوزات غير المدفوعة - أونلاين بعد 15 دقيقة من الحجز، ونقدي قبل الميعاد بـ 4 ساعات';

    public function handle(): void
    {
        $releasedOnline = $this->releaseOnlineExpired();
        $releasedCash = $this->releaseCashExpired();

        $this->info("أونلاين: اتلغى {$releasedOnline}. نقدي: اتلغى {$releasedCash}.");
    }

    /**
     * الدفع الأونلاين: لو ماكملش الدفع خلال 15 دقيقة من لحظة الحجز.
     */
    private function releaseOnlineExpired(): int
    {
        $appointments = Appointment::query()
            ->where('payment_status', 'unpaid')
            ->where('status', 'confirmed')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->whereDoesntHave('payment', fn ($q) => $q->where('method', 'cash'))
            ->with('timeSlot')
            ->get();

        $this->releaseAll($appointments);

        return $appointments->count();
    }

    /**
     * الدفع النقدي: لو لسه مادفعش (ولا اتأكد) قبل ميعاد الكشف نفسه بـ 4 ساعات.
     * هنا بنقارن بتاريخ ووقت الـ time_slot نفسه، مش وقت إنشاء الحجز.
     */
    private function releaseCashExpired(): int
    {
        $cutoff = now()->addHours(4);

        $appointments = Appointment::query()
            ->where('payment_status', 'unpaid')
            ->where('status', 'confirmed')
            ->whereHas('payment', fn ($q) => $q->where('method', 'cash'))
            ->whereHas('timeSlot', function ($q) use ($cutoff) {
                // الوقت المتبقي على الميعاد نفسه أقل من 4 ساعات
                $q->whereRaw(
                    "TIMESTAMP(date, start_time) <= ?",
                    [$cutoff->toDateTimeString()]
                );
            })
            ->with('timeSlot')
            ->get();

        $this->releaseAll($appointments);

        return $appointments->count();
    }

    private function releaseAll($appointments): void
    {
        foreach ($appointments as $appointment) {
            DB::transaction(function () use ($appointment) {
                $appointment->update(['status' => 'cancelled']);
                $appointment->timeSlot()->update(['status' => 'available']);
            });
        }
    }
}
