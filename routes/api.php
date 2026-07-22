<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

Route::middleware(['cors', 'request.timing'])->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
});

Route::middleware(['auth:sanctum', 'active.user', 'cors', 'request.timing', 'throttle:60,1'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/reports/activity', [ReportController::class, 'activity']);

    Route::middleware('role:admin,responsable,user')->group(function (): void {
        Route::get('/documents', [DocumentController::class, 'index']);
        Route::get('/documents/{id}', [DocumentController::class, 'show']);
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::put('/documents/{id}', [DocumentController::class, 'update']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
            ->middleware('log.activity:document.delete');

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

        Route::apiResource('spaces', SpaceController::class);
        Route::post('/spaces/{space}/members', [SpaceController::class, 'addMember']);
        Route::put('/spaces/{space}/members/{user}', [SpaceController::class, 'updateMemberRole']);
        Route::delete('/spaces/{space}/members/{user}', [SpaceController::class, 'removeMember']);
    });

    Route::middleware('role:admin')->group(function (): void {
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    });
});