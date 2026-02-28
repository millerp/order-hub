<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'payment_id',
        'order_id',
        'event_id',
        'occurred_at',
        'trace_id',
        'type',
        'status',
        'error_message',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];
}
