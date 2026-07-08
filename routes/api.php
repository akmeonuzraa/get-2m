<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
Route::middleware(['auth:sanctum', 'active.user', 'role:admin'])->group(function () {
    Route::get('/test-admin', function () {
        return response()->json(['message' => 'Accès admin OK']);
    });
});
Route::middleware(['auth:sanctum', 'active.user', 'role:admin'])->group(function () {
    Route::get('/test-admin', function () {
        return response()->json(['message' => 'Accès admin OK']);
    });
    
    // Users CRUD
    Route::apiResource('/users', UserController::class);
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
});



