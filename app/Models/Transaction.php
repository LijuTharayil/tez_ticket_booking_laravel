<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaction_id',
        'type',
        'account_type',
        'token_quantity',
        'transaction_on',
        'user_id'
    ];
}
    