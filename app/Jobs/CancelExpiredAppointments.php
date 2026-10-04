<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CancelExpiredAppointments implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // جلب الحجوزات المعلقة التي مر عليها أكثر من 15 دقيقة من وقت إنشائها
        $expiredAppointments = Appointment::where('status', 'no_show')
            ->where('created_at', '<=', now()->subMinutes(10))
            ->get();

        foreach ($expiredAppointments as $appointment) {
            DB::transaction(function () use ($appointment) {
                // 1. إعادة الموعد متاح لمريض آخر
                $appointment->timeSlot()->update(['status' => 'available']);

                // 2. إلغاء الحجز المعلق
                $appointment->update([
                    'status' => 'cancelled',
                ]);
            });
        }
    }
}
