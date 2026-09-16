<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\admin_acc\DoctorController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\admin_acc\UserManagementController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\PolyclinicController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ClinicInfoController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\BotManController;


require __DIR__.'/admin_acc/web.php';
require __DIR__.'/staff_acc/web.php';


Route::get('/', [PolyclinicController::class, 'home']);

Route::get('/sample', function () {
    return view('sample');
});

// Login form (named 'login' so auth middleware knows where to redirect)
Route::get('/auth/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/auth/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ===== Protected admin routes =====
Route::middleware('auth')->group(function () {
    Route::get('/admin_acc/dashboard', function () {
        return view('admin_acc.dashboard');
    });

    // NOTE: Ang /admin_acc/chatbot_logs ay INALIS na dito — dating static
    // closure ito (return view() lang, walang data), at dahil dalawang
    // beses na-register ang parehong URL, ito (bilang huling na-load) ang
    // laging nananalo kaysa sa tamang controller-backed na route sa
    // routes/admin_acc/web.php. Ang route na iyon na lang (na tumatawag sa
    // ChatbotInquiryController) ang gumagana ngayon para sa page na ito.

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

    //user management routes
    Route::post('/admin_acc/user_management',              [UserManagementController::class, 'store']);
    Route::put('/admin_acc/user_management/{id}',          [UserManagementController::class, 'update']);
    Route::patch('/admin_acc/user_management/{id}/toggle', [UserManagementController::class, 'toggleStatus']);
    Route::delete('/admin_acc/user_management/{id}',       [UserManagementController::class, 'destroy']);
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

// Polyclinic Routes
Route::get('/home',     [PolyclinicController::class, 'home'])->name('home');
Route::get('/about',    [PolyclinicController::class, 'about'])->name('about');
Route::get('/services', [PolyclinicController::class, 'services'])->name('services');
Route::get('/doctors',  [PolyclinicController::class, 'doctors'])->name('doctors');
Route::get('/contact',  [PolyclinicController::class, 'contact'])->name('contact');
Route::get('/chatbot',  [PolyclinicController::class, 'chatbot'])->name('chatbot');

// General Privacy Notice page (linked from the privacy consent modal)
Route::get('/privacy-notice', function () {
    return view('patient.privacy-notice');
})->name('privacy-notice');

// ===== BotMan route =====
// Receives messages from the chat widget (script.js) and returns BotMan's reply
// Throttled per IP (40 messages/minute is generous for a real back-and-forth
// conversation but blocks scripted flooding of this public, unauthenticated
// endpoint). Laravel returns a 429 automatically once exceeded.
Route::post('/botman', [BotManController::class, 'handle'])
    ->middleware('throttle:40,1')
    ->name('botman.handle');

// Receives a file the patient attaches in the chat widget and links it
// into their inquiry thread so Admin/Staff can see it too.
Route::post('/chatbot/attachment', [BotManController::class, 'attachment'])
    ->middleware('throttle:40,1')
    ->name('chatbot.attachment');

// Serve CSS files directly from resources/css/{folder} (no Vite needed)
Route::get('/patient-css/{file}', function ($file) {
    $path = resource_path('css/patient_css/' . $file);

    if (!file_exists($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'css') {
        abort(404);
    }

    return response(file_get_contents($path), 200)
        ->header('Content-Type', 'text/css');
})->where('file', '.*\.css');

Route::get('/admin-css/{file}', function ($file) {
    $path = resource_path('css/admin_css/' . $file);

    if (!file_exists($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'css') {
        abort(404);
    }

    return response(file_get_contents($path), 200)
        ->header('Content-Type', 'text/css');
})->where('file', '.*\.css');


Route::get('/debug-cert', function () {
    $path = env('MYSQL_ATTR_SSL_CA');
    return response()->json([
        'env_value' => $path,
        'exists' => $path ? file_exists($path) : null,
        'readable' => $path ? is_readable($path) : null,
    ]);
});