<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'start_time' => $this->start_time ? date('h:i A', strtotime($this->start_time)) : null,
            'date'       => date('Y-m-d', strtotime($this->date)),
            'status'     => $this->status,
            ];
    }
}
