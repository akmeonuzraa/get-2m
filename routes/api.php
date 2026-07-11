<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FolderController;

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

