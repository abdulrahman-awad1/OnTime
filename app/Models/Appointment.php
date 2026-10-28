<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'time_slot_id',
        'visit_type',
        'status',
        'payment_status',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    // بيرجع سعر الزيارة دي بناءً على نوعها وسعر العيادة بتاعتها
    public function price(): float
    {
        $clinic = $this->timeSlot->clinicLocation;

        return $this->visit_type === 'consultation'
            ? (float) $clinic->consultation_price
            : (float) $clinic->checkup_price;
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }
}
