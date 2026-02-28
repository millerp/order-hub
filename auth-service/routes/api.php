<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth-register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:auth-refresh');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
