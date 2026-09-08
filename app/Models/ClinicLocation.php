<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ClinicLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    // بيسمحلك تعمل: $clinicLocation->appointments()->where('status', 'confirmed')->get()
    // من غير ما تعدي بـ TimeSlot يدوي - مفيد جداً في لوحة تحكم الدكتور
    public function appointments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Appointment::class,
            TimeSlot::class,
            'clinic_location_id', // FK على جدول time_slots
            'time_slot_id',       // FK على جدول appointments
            'id',                 // local key على clinic_locations
            'id'                  // local key على time_slots
        );
    }
}
