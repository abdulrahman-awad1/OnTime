<?php

namespace App\Services;

use App\Exceptions\Timeslotoverlapexception;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TimeSlotService
{

    public function list(Request $request): LengthAwarePaginator
    {
        $query = TimeSlot::with('clinicLocation')->query();

        if ($request->filled('clinic_location_id')) {
            $query->forClinic($request->integer('clinic_location_id'));
        }

        if ($request->filled('date')) {
            $query->forDate($request->date('date')->toDateString());
        }

        return $query->orderBy('date')->orderBy('start_time')->paginate(20);
    }

    public function create(array $data): TimeSlot
    {
        if ($this->hasOverlap($data)) {
            throw new TimeSlotOverlapException();
        }

        return TimeSlot::create($data);
    }

    public function delete(TimeSlot $timeSlot): void
    {
        if ($timeSlot->status === 'booked') {
            throw new \RuntimeException('الموعد ده محجوز بالفعل، لازم تلغي الحجز الأول.');
        }

        $timeSlot->delete();
    }

    private function hasOverlap(array $data): bool
    {
        return TimeSlot::forClinic($data['clinic_location_id'])
            ->forDate($data['date'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                    ->orWhere(function ($q2) use ($data) {
                        $q2->where('start_time', '<=', $data['start_time'])
                            ->where('end_time', '>=', $data['end_time']);
                    });
            })
            ->exists();
    }
}
