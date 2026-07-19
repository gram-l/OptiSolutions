<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Staff\AppointmentController;
use App\Http\Controllers\Staff\InquiryController;
use App\Http\Controllers\Staff\DoctorController;
use App\Http\Controllers\Staff\PatientController;

Route::middleware(['auth', 'staff'])->group(function () {

    // DASHBOARD
    Route::get('/staff/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');
    Route::get('/staff/dashboard/data', [StaffController::class, 'data'])->name('staff.dashboard.data');

    // APPOINTMENTS
    Route::get('/staff/appointments', [AppointmentController::class, 'index'])->name('staff.appointments');

    // INQUIRIES
    Route::get('/staff/inquiries', [InquiryController::class, 'index'])->name('staff.inquiries');
    Route::get('/staff/inquiries/{id}', [InquiryController::class, 'show'])->name('staff.inquiries.show');
    Route::post('/staff/inquiries/{id}/reply', [InquiryController::class, 'reply'])->name('staff.inquiries.reply');
    Route::put('/staff/inquiries/{id}/resolve', [InquiryController::class, 'resolve'])->name('staff.inquiries.resolve');

    // DOCTORS
    Route::get('/staff/doctors', [DoctorController::class, 'index'])->name('staff.doctors');
    Route::get('/staff/doctors/{id}/edit', [DoctorController::class, 'edit'])->name('staff.doctors.edit');
    Route::put('/staff/doctors/{id}', [DoctorController::class, 'update'])->name('staff.doctors.update');
    Route::put('/staff/doctors/{id}/toggle', [DoctorController::class, 'toggleStatus'])->name('staff.doctors.toggle');

    // PATIENTS
    Route::get('/staff/patients', [PatientController::class, 'index'])->name('staff.patients');
    Route::get('/staff/patients/{id}', [PatientController::class, 'show'])->name('staff.patients.show');
    Route::get('/staff/patients/{id}/edit', [PatientController::class, 'edit'])->name('staff.patients.edit');
    Route::put('/staff/patients/{id}', [PatientController::class, 'update'])->name('staff.patients.update');

});
