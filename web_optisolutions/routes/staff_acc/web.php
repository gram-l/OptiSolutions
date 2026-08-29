<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Staff\AppointmentController;
use App\Http\Controllers\Staff\InquiryController;
use App\Http\Controllers\Staff\DoctorController;
use App\Http\Controllers\Staff\PatientController;
use App\Http\Controllers\Staff\NotificationController;

Route::middleware(['auth', 'staff'])->group(function () {

    Route::post('/staff/profile/photo', [StaffController::class, 'updatePhoto'])->name('staff.profile.photo');
    Route::put('/staff/profile/password', [StaffController::class, 'updatePassword'])->name('staff.profile.password');
    Route::post('/staff/profile/session', [StaffController::class, 'updateSession'])
    ->name('staff.profile.session');
    
    // DASHBOARD
    Route::get('/staff/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');
    Route::get('/staff/dashboard/data', [StaffController::class, 'data'])->name('staff.dashboard.data');

    // NOTIFICATIONS
    Route::get('/staff/notifications', [NotificationController::class, 'index'])->name('staff.notifications.index');
    Route::put('/staff/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('staff.notifications.read');
    Route::put('/staff/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('staff.notifications.readAll');

    // APPOINTMENTS
    Route::get('/staff/appointments', [AppointmentController::class, 'index'])->name('staff.appointments');
    Route::get('visits/export/{format}', [AppointmentController::class, 'export'])->name('staff.visits.export');

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
    Route::get('doctors/export/{format}', [DoctorController::class, 'export'])->name('staff.doctors.export');

    // PATIENTS
    Route::get('/staff/patients', [PatientController::class, 'index'])->name('staff.patients');
    Route::get('/staff/patients/{id}', [PatientController::class, 'show'])->name('staff.patients.show');
    Route::get('/staff/patients/{id}/edit', [PatientController::class, 'edit'])->name('staff.patients.edit');
    Route::put('/staff/patients/{id}', [PatientController::class, 'update'])->name('staff.patients.update');

});
