<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTicket extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'ticket_id',
        'booked_on',
        'booking_reference'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
