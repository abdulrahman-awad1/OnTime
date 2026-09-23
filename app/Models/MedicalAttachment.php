<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MedicalAttachment extends Model
{
    protected $fillable = ['medical_record_id', 'patient_id', 'type', 'file_path', 'original_name'];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
