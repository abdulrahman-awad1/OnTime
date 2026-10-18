<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'filters_applied'           => $this['filters_applied'],
            'total_patients'           => $this['total_patients'],
            'total_appointments'       => $this['total_appointments'],
            'total_revenue'            => $this['total_revenue'],
            'appointments_by_status'   => $this['appointments_by_status'],
            'revenue_by_payment_method'=> $this['revenue_by_payment_method'],
        ];
    }
}
