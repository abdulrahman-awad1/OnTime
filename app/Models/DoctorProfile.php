<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorProfile extends Model
{
    protected $fillable = [
        'specialty',
        'title',
        'years_of_experience',
        'bio',
        'qualifications',
        'experiences',
    ];
    protected $casts = [
        'qualifications' => 'array',
        'experiences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
