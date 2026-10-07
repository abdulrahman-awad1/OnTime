<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetAllClinicResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'checkup_price' => $this->checkup_price,
            'consultation_price' => $this->consultation_price,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

        ];
    }
}
