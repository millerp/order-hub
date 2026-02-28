<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'product-service',
        'status' => 'ok',
    ]);
});
