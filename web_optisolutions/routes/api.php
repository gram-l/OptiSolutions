<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiForgotPasswordController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientDoctorController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ClinicInfoController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\FeedbackController;

require __DIR__.'/admin_acc/api.php';
// ── Auth ──
Route::post('/login', [ApiAuthController::class, 'login']);

// ── User management ──
Route::get('/users',                    [UserManagementController::class, 'getUsers']);
Route::post('/users',                   [UserManagementController::class, 'store']);
Route::put('/users/{id}',               [UserManagementController::class, 'update']);
Route::patch('/users/{id}/toggle',      [UserManagementController::class, 'toggleStatus']);
Route::delete('/users/{id}',            [UserManagementController::class, 'destroy']);

// ── Forgot password (mobile/dart) ──
Route::post('/forgot-password/send-otp',   [ApiForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [ApiForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/resend-otp', [ApiForgotPasswordController::class, 'resendOtp']);
Route::post('/forgot-password/reset',      [ApiForgotPasswordController::class, 'resetPassword']);

// ── Doctors (admin side) ──
Route::get('/doctors', [DoctorController::class, 'apiIndex']); // ⚠️ duplicate below, see note
Route::post('/doctors', [DoctorController::class, 'store']);
Route::put('/doctors/{id}', [DoctorController::class, 'update']);
Route::patch('/doctors/{id}/toggle', [DoctorController::class, 'toggleStatus']);
Route::delete('/doctors/{id}', [DoctorController::class, 'destroy']);

// ── Patient side ──
Route::get('/doctors', [PatientDoctorController::class, 'index']); // ⚠️ conflict with line above — same URI, dalawang beses defined
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/clinic-info', [ClinicInfoController::class, 'index']);
Route::post('/schedule-visit', [AppointmentController::class, 'store']);
Route::post('/complaints', [ComplaintController::class, 'store']);
Route::post('/feedback', [FeedbackController::class, 'store']);


