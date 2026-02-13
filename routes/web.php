<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/logout-all', [AuthController::class, 'logoutAll'])->middleware('auth:sanctum');

Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');
