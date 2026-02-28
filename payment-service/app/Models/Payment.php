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
        'trace_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
