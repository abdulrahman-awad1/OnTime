<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $timeSlot = $this->whenLoaded('timeSlot');
        $clinic = $timeSlot?->clinicLocation;

        $durationMinutes = ($timeSlot?->start_time && $timeSlot?->end_time)
            ? \Carbon\Carbon::parse($timeSlot->start_time)->diffInMinutes(\Carbon\Carbon::parse($timeSlot->end_time))
            : 30;

        $calculatedPrice = match ($this->visit_type) {
            'checkup'      => $clinic?->checkup_price,
            'consultation' => $clinic?->consultation_price,
            default        => $clinic?->checkup_price,
        };

        $price = (float) ($calculatedPrice ?? $this->amount ?? 0);

        return [
            'appointment_id'   => $this->id,
            'clinic_name'      => $clinic?->name ?? 'غير محدد',
            'visit_type'       => $this->visit_type ,
            'date'             => $timeSlot?->date ? \Carbon\Carbon::parse($timeSlot->date)->format('Y-m-d') : null,
            'start_time'       => $timeSlot?->start_time ? \Carbon\Carbon::parse($timeSlot->start_time)->format('h:i A') : null,
            'duration_minutes' => $durationMinutes,
            'price'            => $price,
            'total_amount'     => $price,
        ];
    }
}
