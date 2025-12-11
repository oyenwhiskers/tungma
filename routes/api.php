<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // User info
    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // });

    // Bills API - automatically uses authenticated user's company_id
    // Only 4 endpoints: index, show, store, destroy (void)
    Route::get('bills', [BillController::class, 'index']);
    Route::get('bills/{id}', [BillController::class, 'show']);
    Route::post('bills', [BillController::class, 'store']);
    Route::delete('bills/{id}', [BillController::class, 'destroy']);
    Route::get('dashboard', [DashboardController::class,'index']);
    Route::get('dashboard/daily', [DashboardController::class, 'dailyAnalytic']);
    Route::get('dashboard/monthly', [DashboardController::class, 'monthlyAnalytic']);
});

// Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
});

// Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
});
