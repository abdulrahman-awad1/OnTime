<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    use HasFactory;
    protected $fillable = ['token','expires_at'];
    protected $dates = ['expires_at'];
    public function user(){
        return $this->belongsTo(User::class);
    }
}
