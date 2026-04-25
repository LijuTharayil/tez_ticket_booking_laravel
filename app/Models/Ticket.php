<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_code',
        'quantity',
        'match_time',
        'name',
        'match_title',
        'match_details',
        'image',
        'added_by_admin_id',
        'last_updated_by_admin_id',
        'ticket_rate_in_coin_quantity',
        'stadium',
        'venue'
    ];

    public function getImageAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // If already full URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return asset('storage/' . $value);
    }
}
