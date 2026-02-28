<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'user-service',
        'status' => 'ok',
    ]);
});
