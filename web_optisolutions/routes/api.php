<?php
 
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ApiAuthController;
 
// ── Auth ──
Route::post('/login', [ApiAuthController::class, 'login']);
 
// ── User management ──
Route::get('/users',                    [UserManagementController::class, 'getUsers']);
Route::post('/users',                   [UserManagementController::class, 'store']);
Route::put('/users/{id}',               [UserManagementController::class, 'update']);
Route::patch('/users/{id}/toggle',      [UserManagementController::class, 'toggleStatus']);
Route::delete('/users/{id}',            [UserManagementController::class, 'destroy']);