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
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'visit_type' => $this->visit_type,
            'payment_status' => $this->payment_status,
            'patient' => new UserResource($this->whenLoaded('patient')),
            'time_slot' => new TimeSlotResource($this->whenLoaded('timeSlot')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
