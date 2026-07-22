<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DashboardController;
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
    // Users CRUD
    Route::apiResource('/users', UserController::class);
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
});

Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
    // Accessible à tous les users connectés
    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::get('/spaces/{space}', [SpaceController::class, 'show']);

    // Réservé admin + responsable
    Route::middleware('role:admin,responsable')->group(function () {
        Route::post('/spaces', [SpaceController::class, 'store']);
        Route::put('/spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
        Route::post('/spaces/{space}/members', [SpaceController::class, 'addMember']);
        Route::delete('/spaces/{space}/members/{userId}', [SpaceController::class, 'removeMember']);
    });
});


Route::middleware(['auth:sanctum', 'active.user'])->group(function () {

    // Folders
    Route::get('/folders', [FolderController::class, 'index']);
    Route::post('/folders', [FolderController::class, 'store']);
    Route::put('/folders/{folder}', [FolderController::class, 'update']);
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy']);

    // Documents
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/trashed', [DocumentController::class, 'trashed']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::patch('/documents/{document}/trash', [DocumentController::class, 'trash']);
    Route::patch('/documents/{document}/restore', [DocumentController::class, 'restore']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'active.user'])->group(function () {

    // News — lecture pour tous
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{news}', [NewsController::class, 'show']);

    // News — écriture pour admin + responsable
    Route::middleware('role:admin,responsable')->group(function () {
        Route::post('/news', [NewsController::class, 'store']);
        Route::put('/news/{news}', [NewsController::class, 'update']);
        Route::patch('/news/{news}/publish', [NewsController::class, 'publish']);
        Route::patch('/news/{news}/archive', [NewsController::class, 'archive']);
        Route::delete('/news/{news}', [NewsController::class, 'destroy']);
    });

    // Comments — tous les users connectés
    Route::get('/news/{news}/comments', [CommentController::class, 'index']);
    Route::post('/news/{news}/comments', [CommentController::class, 'store']);
    Route::delete('/news/{news}/comments/{comment}', [CommentController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
});



Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
});