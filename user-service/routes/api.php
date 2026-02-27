<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JwtMiddleware;

Route::middleware([JwtMiddleware::class])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    // Creation is handled via Kafka event from Auth Service, but we add an admin endpoint just in case
    Route::post('/users', [UserController::class, 'store']);
});
