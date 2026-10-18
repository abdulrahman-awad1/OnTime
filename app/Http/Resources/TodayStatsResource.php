<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodayStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date'                       => $this['date'],
            'today_revenue'             => $this['today_revenue'],
            'today_appointments_count'  => $this['today_appointments_count'],
            'unpaid_appointments_count' => $this['unpaid_appointments_count'],
            'appointments_by_status'    => $this['appointments_by_status'],
        ];
    }
}
