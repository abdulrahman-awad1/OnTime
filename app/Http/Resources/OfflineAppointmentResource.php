<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfflineAppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->user_id,
            'visit_type' => $this->visit_type,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'date' => $this->timeSlot->date,
            'start_time' => $this->timeSlot->start_time,
            'end_time' => $this->timeSlot->end_time,
            'clinic' => [
                'id' => $this->timeSlot->clinicLocation?->id,
                'name' => $this->timeSlot->clinicLocation?->name,
            ],
        ];
    }
}
