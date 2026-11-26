<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $fillable = ['medical_record_id', 'patient_id', 'notes', 'client_uuid', 'recorded_at', 'created_by'];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /** User (doctor) who wrote the row; null for rows created before tracking. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
