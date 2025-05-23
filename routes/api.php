<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\CheckClockSettingController;
use App\Http\Controllers\CheckClockController;
use App\Http\Controllers\EmployeeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/admin/register', [AuthController::class, 'register']);
Route::post('admin/login', [AuthController::class, 'loginAdmin']);
Route::post('employee/login', [AuthController::class, 'loginEmployee']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

// Check Clock
Route::post('/add/check-clock-settings', [CheckClockSettingController::class, 'store']);
Route::get('/check-clock-settings/{id}/edit', [CheckClockSettingController::class, 'edit']);
Route::get('/check-clock-settings/{id}', [CheckClockSettingController::class, 'show']);

// Employee

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/company', [CompanyController::class, 'store']);
    Route::post('admin/logout', [AuthController::class, 'logout']);
    Route::get('admin/user', [AuthController::class, 'user']);

    // Employee
    Route::post('/add-employees', [EmployeeController::class, 'store']);

    // checkclock
    Route::put('/check-clock-settings/{id}', [CheckClockSettingController::class, 'update']);

    // Attendance
    Route::post('/check-clocks', [CheckClockController::class, 'store']);
    Route::get('/check-clocks/records', [CheckClockController::class, 'records']);
});
