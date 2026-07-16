<?php

//admin_acc/api.php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin_acc\UserManagementController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiForgotPasswordController;
use App\Http\Controllers\admin_acc\DoctorController;
use App\Http\Controllers\admin_acc\AdminDashboardController;
use App\Http\Controllers\admin_acc\ApiProfileController;
use App\Http\Controllers\admin_acc\FeedbackController;
use App\Http\Controllers\admin_acc\VisitController;
use App\Http\Controllers\admin_acc\PatientListController;
use App\Http\Controllers\admin_acc\NotificationController;

Route::prefix('admin')->group(function () {

 
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

//doctors
Route::get('/doctors', [DoctorController::class, 'apiIndex']);
Route::post('/doctors', [DoctorController::class, 'store']);
Route::put('/doctors/{id}', [DoctorController::class, 'update']);
Route::patch('/doctors/{id}/toggle', [DoctorController::class, 'toggleStatus']);
Route::delete('/doctors/{id}', [DoctorController::class, 'destroy']);

Route::get('/dashboard-data', [Admindashboardcontroller::class, 'data']);

//mobile profile
Route::middleware('auth:sanctum')->get('/me', [ApiProfileController::class, 'show']);
Route::middleware('auth:sanctum')->post('/profile/photo', [ApiProfileController::class, 'updatePhoto']);

//feedback list
Route::get('/feedback', [FeedbackController::class, 'apiIndex']);

//schedule visit list
Route::get('/appointments', [VisitController::class, 'apiIndex']);

//Patient list

Route::get('/patients', [PatientListController::class, 'apiIndex']);
Route::post('/patients', [PatientListController::class, 'store']);
Route::put('/patients/{id}', [PatientListController::class, 'update']);



Route::get('/notifications', [NotificationController::class, 'apiIndex']);
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
});
