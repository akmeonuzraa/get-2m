<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:10,1','cors','request.timing']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(['throttle:5,1','cors','request.timing']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware(['throttle:5,1','cors','request.timing']);

Route::middleware(['auth:sanctum','cors','request.timing','throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Module Documents (GED)
Route::middleware(['role:admin,responsable,user','cors','request.timing','throttle:60,1'])->group(function () {
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{id}', [DocumentController::class, 'show']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::put('/documents/{id}', [DocumentController::class, 'update']);

    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])
        ->middleware(['role:admin,responsable,user', 'log.activity:document.delete']);
});