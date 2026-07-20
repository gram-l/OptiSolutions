<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Staff\StaffController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ForgotPasswordController;

// --- Public ---
/*Route::post('/login', [AuthController::class, 'login']);*/

// --- Protected: requires "Authorization: Bearer <token>" ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/profile/photo', [AuthController::class, 'uploadPhoto']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard-data', [StaffController::class, 'data']);

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show']);
    Route::patch('/doctors/{doctor}', [DoctorController::class, 'update']);

    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{patient}', [PatientController::class, 'show']);
    Route::patch('/patients/{patient}', [PatientController::class, 'update']);

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);

    Route::get('/inquiries', [InquiryController::class, 'index']);
    Route::get('/inquiries/{inquiry}', [InquiryController::class, 'show']);
    Route::get('/inquiries/{inquiry}/messages', [InquiryController::class, 'messages']);
    Route::post('/inquiries/{inquiry}/messages', [InquiryController::class, 'sendMessage']);
    Route::post('/inquiries/{inquiry}/resolve', [InquiryController::class, 'resolve']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

    Route::get('/clinic', [ClinicController::class, 'show']);

    Route::prefix('password')->middleware('throttle:5,1')->group(function () {
        Route::post('/send-otp', [ForgotPasswordController::class, 'sendOtp']);
        Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
        Route::post('/reset', [ForgotPasswordController::class, 'resetPassword']);
    });
});