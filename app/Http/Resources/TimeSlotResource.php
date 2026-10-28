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
            'start_time' => str_replace(['AM', 'PM'], ['ص', 'م'], date('h:i A', strtotime($this->start_time))),            'date' => $this->date,
            ];
    }
}
