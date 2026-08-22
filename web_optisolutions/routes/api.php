<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin_acc\UserManagementController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiForgotPasswordController;
use App\Http\Controllers\admin_acc\DoctorController;
use App\Http\Controllers\PatientDoctorController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ClinicInfoController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\FeedbackController;
//use App\Http\Controllers\ChatbotLogController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Api\TranslationController;

require __DIR__.'/admin_acc/api.php';
require __DIR__.'/staff_acc/api.php';

// ── Auth ──
Route::post('/login', [ApiAuthController::class, 'login']);

// ── User management ──
/*Route::get('/users',                    [UserManagementController::class, 'getUsers']);
Route::post('/users',                   [UserManagementController::class, 'store']);
Route::put('/users/{id}',               [UserManagementController::class, 'update']);
Route::patch('/users/{id}/toggle',      [UserManagementController::class, 'toggleStatus']);
Route::delete('/users/{id}',            [UserManagementController::class, 'destroy']);*/

// ── Forgot password (mobile/dart) ──
Route::post('/forgot-password/send-otp',   [ApiForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [ApiForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/resend-otp', [ApiForgotPasswordController::class, 'resendOtp']);
Route::post('/forgot-password/reset',      [ApiForgotPasswordController::class, 'resetPassword']);

// ── Google login ──
Route::post('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'googleLogin']);

// ── Patient side (PUBLIC — no auth required) ──
Route::get('/doctors', [PatientDoctorController::class, 'index']);
//(mika) tinanggal q kasi wala na patient side sa mobile 
// ── Patient side ──
Route::get('/doctors', [PatientDoctorController::class, 'index']); // ⚠️ conflict with line above — same URI, dalawang beses defined
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/clinic-info', [ClinicInfoController::class, 'index']);
Route::post('/schedule-visit', [AppointmentController::class, 'store']);
Route::post('/complaints', [ComplaintController::class, 'store']);
Route::post('/feedback', [FeedbackController::class, 'store']);
//Route::post('/chatbot-logs', [ChatbotLogController::class, 'store']);

Route::prefix('charts')->group(function () {
    Route::get('/service-distribution', [ChartController::class, 'serviceDistribution']);
    Route::get('/weekly-visits', [ChartController::class, 'weeklyVisits']);
    Route::get('/sentiment-analysis', [ChartController::class, 'sentimentAnalysis']);
    Route::get('/inquiry-volume', [ChartController::class, 'inquiryVolume']);
});

// ── Public: patient-facing chatbot widget polls this (no login) to see
//    if Admin/Staff has replied to an inquiry raised in the same chat
//    session. Keyed by the BotMan conversation/client id. ──
Route::get('/chat/{conversationId}/updates', [\App\Http\Controllers\Api\InquiryController::class, 'publicUpdates']);

// ── Public: translation proxy used by the patient-facing chatbot widget
//    to translate patient messages and bot/staff replies both ways. ──
Route::post('/translate', [TranslationController::class, 'translate']);