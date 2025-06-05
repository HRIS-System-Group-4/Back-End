<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\CheckClockSettingController;
use App\Http\Controllers\CheckClockController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClockRequestController;

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

Route::get('/test-cors', function () {
    return response()->json(['message' => 'CORS is working']);
});

Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');


Route::post('/admin/register', [AuthController::class, 'register']);
Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
Route::post('employee/login', [AuthController::class, 'loginEmployee']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

// Check Clock
Route::get('/branches/index', [BranchController::class, 'index']);

// Route::post('/add-employees', [EmployeeController::class, 'store']);
// Employee
// Route::get('/employees', [EmployeeController::class, 'show']);
// Route::get('/employees/{id}', [EmployeeController::class, 'detailEmployee']);
// Route::put('/employees/{id}', [EmployeeController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    // Sementara api dibuat public buat pengujian, kalo sudah fix baru dimasukkan sanctum


});

// Hanya bisa diakses oleh Admin
Route::middleware(['auth:sanctum', 'admin.only'])->group(function () {
    // Company
    Route::post('/company', [CompanyController::class, 'store']);
    Route::put('/company/{company}/location', [CompanyController::class, 'updateLocation']);

    // checkclock
    Route::put('/check-clock-settings/{id}', [CheckClockSettingController::class, 'update']);
    Route::post('/add/check-clock-settings', [CheckClockSettingController::class, 'store']);
    Route::get('/check-clock-settings/{id}/edit', [CheckClockSettingController::class, 'edit']);
    Route::get('/check-clock-settings/{id}', [CheckClockSettingController::class, 'show']);
    Route::get('/check-clock-settings/', [CheckClockController::class, 'index']);

    // Subscription
    Route::post('/subscription/activate', [SubscriptionController::class, 'activate']);
    Route::get('/subscription/status', [SubscriptionController::class, 'status']);

    // Detail Admin
    Route::post('admin/logout', [AuthController::class, 'logout']);
    Route::get('admin/user', [AuthController::class, 'user']);
    Route::get('admin/profile', [AuthController::class, 'fetchAdmin']);

    // Branch
    // Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches', [BranchController::class, 'overview']);
    Route::post('/add-branch', [BranchController::class, 'store']);
    Route::get('/branches/{id}', [BranchController::class, 'show']);

    // Employee
    Route::post('/add-employees', [EmployeeController::class, 'store']);
    Route::get('/employees', [EmployeeController::class, 'show']);

    // Attendance Check-Clock
    Route::get('/clock-requests', [ClockRequestController::class, 'index']);
    Route::post('/clock-requests/{id}/approve', [ClockRequestController::class, 'approve']);
    Route::post('/clock-requests/{id}/decline', [ClockRequestController::class, 'decline']);
});

// Hanya bisa diakses oleh Employee (bukan admin)
Route::middleware(['auth:sanctum', 'employee.only'])->group(function () {
    // Route::get('/employees', [EmployeeController::class, 'show']);
    Route::get('/employees/{id}', [EmployeeController::class, 'detailEmployee']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);

    // Attendance
    Route::post('/clock-in', [CheckClockController::class, 'store']);
    Route::post('/clock-out', [CheckClockController::class, 'clockOut']);
    Route::get('/check-clocks/records', [CheckClockController::class, 'records']);
});
