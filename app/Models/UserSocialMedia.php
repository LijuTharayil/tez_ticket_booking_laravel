<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSocialMedia extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'type',
        'url',
        'is_approved',
        'approved_by_admin_id',
        'approved_on'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
