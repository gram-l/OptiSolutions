<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sample', function () {
    return view('sample');
});

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
