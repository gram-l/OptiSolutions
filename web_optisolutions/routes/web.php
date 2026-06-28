<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\EmailController;

Route::get('/', function () {
    return view('welcome');
});

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

Route::get('/admin_acc/doctors', function () {
    return view('admin_acc.doctors');
});

Route::get('/admin_acc/patients', function () {
    return view('admin_acc.patients');
});

Route::get('/admin_acc/feedback', function () {
    return view('admin_acc.feedback');
});

Route::get('/admin_acc/user_management', function () {
    return view('admin_acc.user_management');
});

Route::get('/admin_acc/sidebar1', function () {
    return view('admin_acc.sidebar1');
});

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