<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_number_verified_at' => 'datetime',
    ];
    protected $fillable = [
        'user_unique_id',
        'country_id',
        'first_name',
        'last_name',
        'mobile_number',
        'email',
        'password'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
