<?php

namespace Database\Factories;

use App\Models\ClinicLocation;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    public function definition(): array
    {
        return [
            // لو محدّدتش clinic_location_id وقت الاستخدام، هيعمل عيادة وهمية جديدة تلقائي
            'clinic_location_id' => ClinicLocation::factory(),
            'date' => now()->addDay()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '17:30',
            'status' => 'available',
        ];
    }

    // استخدام: TimeSlot::factory()->booked()->create()
    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'booked',
        ]);
    }
}
