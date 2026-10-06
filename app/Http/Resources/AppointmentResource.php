<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $timeSlot = $this->whenLoaded('timeSlot');
        $clinic   = $timeSlot?->clinicLocation;
        $patient  = $this->whenLoaded('patient');

        // حساب مدة الجلسة بالدقائق
        $durationMinutes = ($timeSlot?->start_time && $timeSlot?->end_time)
            ? Carbon::parse($timeSlot->start_time)->diffInMinutes(Carbon::parse($timeSlot->end_time))
            : 30;

        // تحديد السعر حسب نوع الزيارة (كشف / استشارة)
        $calculatedPrice = match ($this->visit_type) {
            'checkup'      => $clinic?->checkup_price,
            'consultation' => $clinic?->consultation_price,
            default        => $clinic?->checkup_price,
        };

        $price = (float) ($calculatedPrice ?? $this->amount ?? 0);

        return [
            'appointment_id'   => $this->id,
            'status'           => $this->status,          // حالة الحجز (confirmed, pending, etc.)
            'payment_status'   => $this->payment_status,  // حالة الدفع (paid, unpaid)
            'visit_type'       => $this->visit_type,      // نوع الزيارة (checkup, consultation)

            // بيانات التوقيت والعيادة
            'date'             => $timeSlot?->date ? Carbon::parse($timeSlot->date)->format('Y-m-d') : null,
            'start_time'       => $timeSlot?->start_time ? Carbon::parse($timeSlot->start_time)->format('h:i A') : null,
            'end_time'         => $timeSlot?->end_time ? Carbon::parse($timeSlot->end_time)->format('h:i A') : null,
            'duration_minutes' => $durationMinutes,
            'clinic_name'      => $clinic?->name ?? 'غير محدد',

            // بيانات المريض (صاحب الحجز)
            'patient'          => $this->when($this->relationLoaded('patient'), fn () => [
                'id'    => $patient?->id,
                'name'  => $patient?->name ?? 'غير محدد',
                'phone' => $patient?->phone ?? 'غير متوفر',
            ]),

            // الحسابات والأسعار
            'price'            => $price,
            'total_amount'     => $price,
        ];
    }
}
