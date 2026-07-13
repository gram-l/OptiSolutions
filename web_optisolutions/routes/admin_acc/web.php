<?php

//admin_acc/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\admin_acc\DoctorController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\admin_acc\UserManagementController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\admin_acc\ProfileController;
use App\Http\Controllers\admin_acc\SystemSettingsController;
use App\Http\Controllers\admin_acc\ServiceController;
use App\Http\Controllers\admin_acc\FeedbackController;
use App\Http\Controllers\Auth\GoogleAuthController;


Route::get('/sample', function () {
    return view('sample');
});
Route::get('/auth/login', function () {
    return view('auth.login');
});
Route::post('/auth/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/admin_acc/dashboard', function () {
    return view('admin_acc.dashboard');
});

Route::get('/admin_acc/chatbot_logs', function () {
    return view('admin_acc.chatbot_logs');
});

Route::get('/admin_acc/sidebar', function () {
    return view('admin_acc.sidebar');
});

Route::get('/admin_acc/appointments', function () {
    return view('admin_acc.appointments');
});

Route::get('/admin_acc/doctors', [DoctorController::class, 'index']);

Route::get('/admin_acc/patients', function () {
    return view('admin_acc.patients');
});

Route::get('/admin_acc/user_management', [UserManagementController::class, 'index']);


Route::get('/admin_acc/sidebar1', function () {
    return view('admin_acc.sidebar1');
});

//sample email route
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmation;

Route::get('/test-email', function () {
    Mail::to('mikaelaloyola1020@gmail.com')->send(
        new AppointmentConfirmation(
            'John Doe',
            'July 20, 2026',
            'Dr. Lara'
        )
    );

    return 'Email Sent!';
});

//forgot password route
//step 1 – Email entry
Route::get('/auth/forgot-password',  [ForgotPasswordController::class, 'showEmailForm'])->name('password.forgot');
Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendOtp'])->name('password.otp.send');
 
//step 2 – OTP verification
Route::get('/auth/verify-otp',  [ForgotPasswordController::class, 'showOtpForm'])->name('password.otp.form');
Route::post('/auth/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('password.otp.verify');
Route::post('/auth/resend-otp', [ForgotPasswordController::class, 'resendOtp'])->name('password.otp.resend');
 
//Step 3 – New password
Route::get('/auth/reset-password',  [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/auth/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset.update');

//user management routes
Route::post('/admin_acc/user_management',              [UserManagementController::class, 'store']);
Route::put('/admin_acc/user_management/{id}',          [UserManagementController::class, 'update']);
Route::patch('/admin_acc/user_management/{id}/toggle', [UserManagementController::class, 'toggleStatus']);
Route::delete('/admin_acc/user_management/{id}',       [UserManagementController::class, 'destroy']);

//google login route


Route::post('/auth/google', [GoogleAuthController::class, 'googleLogin'])->name('auth.google');

//admin profile routes
Route::get('/admin_acc/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::post('/admin_acc/profile', [ProfileController::class, 'update'])->name('profile.update');

// system settings routes

Route::prefix('admin_acc/system_settings')->name('system_settings.')->group(function () {
    Route::get('/', [SystemSettingsController::class, 'index'])->name('index');

    Route::post('/{group}', [SystemSettingsController::class, 'updateGroup'])
        ->whereIn('group', ['hours', 'about', 'contact'])
        ->name('update');

    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
});


Route::middleware('auth')->group(function () {
    Route::get('/admin_acc/feedback', [FeedbackController::class, 'index'])->name('admin_acc.feedback.index');
    Route::post('/admin_acc/feedback', [FeedbackController::class, 'store'])->name('admin_acc.feedback.store');
    
});

Route::get('/feedback', [FeedbackController::class, 'apiIndex']);