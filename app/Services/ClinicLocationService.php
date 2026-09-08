<?php

namespace App\Services;

use App\Models\ClinicLocation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ClinicLocationService
{
    public function all(): Collection
    {
        return ClinicLocation::all();
    }

    public function availableSlots(ClinicLocation $clinicLocation, ?string $date): Collection
    {
        $query = $clinicLocation->timeSlots()->available();

        if ($date) {
            $query->forDate($date);
        } else {
            $query->whereBetween('date', [
                now()->toDateString(),
                Carbon::now()->addDays(14)->toDateString(),
            ]);
        }

        return $query->with('clinicLocation')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

}
