<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
||--------------------------------------------------------------------------
|| API Routes
||--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/tickets', [\App\Http\Controllers\Api\TicketController::class, 'index']);
    Route::post('/tickets', [\App\Http\Controllers\Api\TicketController::class, 'store']);
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Api\TicketController::class, 'show']);
});
