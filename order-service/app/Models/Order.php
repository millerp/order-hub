<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'quantity' => 'integer',
        'user_id' => 'integer',
        'product_id' => 'integer'
    ];
}
