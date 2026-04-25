<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneTimePassword extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'type',
        'medium',
        'otp_number',
        'status',
        'token',
        'expired_at'
    ];
}