<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'status',
        'retry_count',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
