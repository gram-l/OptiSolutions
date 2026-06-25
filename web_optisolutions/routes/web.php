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
