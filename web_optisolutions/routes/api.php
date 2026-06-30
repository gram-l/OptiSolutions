<?php
 
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiForgotPasswordController;
 
// ── Auth ──
Route::post('/login', [ApiAuthController::class, 'login']);
 
// ── User management ──
Route::get('/users',                    [UserManagementController::class, 'getUsers']);
Route::post('/users',                   [UserManagementController::class, 'store']);
Route::put('/users/{id}',               [UserManagementController::class, 'update']);
Route::patch('/users/{id}/toggle',      [UserManagementController::class, 'toggleStatus']);
Route::delete('/users/{id}',            [UserManagementController::class, 'destroy']);

// forgot pass in dart
Route::post('/forgot-password/send-otp',   [ApiForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [ApiForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/resend-otp', [ApiForgotPasswordController::class, 'resendOtp']);
Route::post('/forgot-password/reset',      [ApiForgotPasswordController::class, 'resetPassword']);