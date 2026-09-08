<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'user_id',

        // Personal Information
        'date_of_birth',
        'gender',
        'address',

        // Basic Medical Information
        'blood_type',
        'has_hypertension',
        'has_diabetes',
        'has_heart_disease',
        'has_previous_stroke',
        'medical_notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'has_hypertension' => 'boolean',
            'has_diabetes' => 'boolean',
            'has_heart_disease' => 'boolean',
            'has_previous_stroke' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
