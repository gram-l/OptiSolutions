<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Staff\AppointmentController;
use App\Http\Controllers\Staff\InquiryController;
use App\Http\Controllers\Staff\DoctorController;
use App\Http\Controllers\Staff\PatientController;

    // Staff Login Routes
    Route::get('/staff/login', [StaffController::class, 'login'])->name('staff.login');
    Route::post('/staff/login', [StaffController::class, 'authenticate']);

    // Staff Dashboard Routes (Protected by middleware)
    Route::middleware(['auth'])->group(function () {

    // DASHBOARD & LOGOUT
    Route::get('/staff/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');

    // NEW: JSON endpoint for live chart polling (dashboard.blade.php fetches this every 30s)
    // FIX: previously pointed to DashboardController, which was never imported/used here.
    // The dashboard logic actually lives in StaffController, so this now calls
    // StaffController::data() — the same class already used for ->dashboard().
    Route::get('/staff/dashboard/data', [StaffController::class, 'data'])->name('staff.dashboard.data');

    Route::post('/staff/logout', [StaffController::class, 'logout'])->name('staff.logout');

    // APPOINTMENTS - VIEW ONLY
    Route::get('/staff/appointments', [AppointmentController::class, 'index'])->name('staff.appointments');

    // INQUIRIES - VIEW, REPLY, RESOLVE
    Route::get('/staff/inquiries', [InquiryController::class, 'index'])->name('staff.inquiries');
    Route::get('/staff/inquiries/{id}', [InquiryController::class, 'show'])->name('staff.inquiries.show');
    Route::post('/staff/inquiries/{id}/reply', [InquiryController::class, 'reply'])->name('staff.inquiries.reply');
    Route::put('/staff/inquiries/{id}/resolve', [InquiryController::class, 'resolve'])->name('staff.inquiries.resolve');

    // DOCTORS - VIEW, EDIT, TOGGLE (NO ADD, NO DELETE)
    Route::get('/staff/doctors', [DoctorController::class, 'index'])->name('staff.doctors');
    Route::get('/staff/doctors/{id}/edit', [DoctorController::class, 'edit'])->name('staff.doctors.edit');
    Route::put('/staff/doctors/{id}', [DoctorController::class, 'update'])->name('staff.doctors.update');
    Route::put('/staff/doctors/{id}/toggle', [DoctorController::class, 'toggleStatus'])->name('staff.doctors.toggle');

    // PATIENTS - VIEW, SHOW, EDIT, UPDATE (NO ADD, NO DELETE)
    Route::get('/staff/patients', [PatientController::class, 'index'])->name('staff.patients');
    Route::get('/staff/patients/{id}', [PatientController::class, 'show'])->name('staff.patients.show');
    Route::get('/staff/patients/{id}/edit', [PatientController::class, 'edit'])->name('staff.patients.edit');
    Route::put('/staff/patients/{id}', [PatientController::class, 'update'])->name('staff.patients.update');
});