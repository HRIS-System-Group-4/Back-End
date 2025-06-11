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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Xendit\Xendit;
use App\Http\Controllers\PaymentController;

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
Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
});

// Route::post('/add-employees', [EmployeeController::class, 'store']);

Route::get('/test-cors', function () {
    return response()->json(['message' => 'CORS is working']);
});

Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');

// Route::get('/check-clock-settings/{id}', [CheckClockSettingController::class, 'show']);
// Route::get('/employees/{id}', [EmployeeController::class, 'detailEmployee']);

Route::post('/admin/register', [AuthController::class, 'register']);
Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
Route::post('employee/login', [AuthController::class, 'loginEmployee']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

Route::get('/xendit-test', function () {
    Xendit::setApiKey(env('xnd_development_OyncgJmTdMtJX1QKAfcp0ZOiUo9KA9UGWdeKXn2o2QUwdmXjzCEJJZjMdebxkmQ'));
    return response()->json(['message' => 'Xendit SDK bekerja!']);
});
Route::post('/payments', [PaymentController::class, 'create']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::post('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/index', [DashboardController::class, 'index']);
});

// Hanya bisa diakses oleh Admin
Route::middleware(['auth:sanctum', 'admin.only'])->group(function () {
    // Company
    Route::post('/company', [CompanyController::class, 'store']);
    Route::put('/company/{company}/location', [CompanyController::class, 'updateLocation']);

    // checkclock
    Route::get('/check-clock-settings', [CheckClockSettingController::class, 'index']);
    Route::put('/check-clock-settings/{id}', [CheckClockSettingController::class, 'update']);
    Route::post('/add/check-clock-settings', [CheckClockSettingController::class, 'store']);
    Route::get('/check-clock-settings/{id}/edit', [CheckClockSettingController::class, 'edit']);
    Route::get('/check-clock-settings/{id}', [CheckClockSettingController::class, 'show']);

    // Subscription
    Route::post('/subscription/activate', [SubscriptionController::class, 'activate']);
    Route::get('/subscription/status', [SubscriptionController::class, 'status']);
    Route::post('/subscription/invoice', [SubscriptionController::class, 'createInvoice']);
    Route::post('/subscription/callback', [SubscriptionController::class, 'callback']);
    Route::get('/subscription/billing', [SubscriptionController::class, 'billing']);
    Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);

    // Detail Admin
    Route::get('admin/profile', [AuthController::class, 'fetchAdmin']);

    // Branch
    Route::get('/branches', [BranchController::class, 'overview']);
    Route::post('/add-branch', [BranchController::class, 'store']);
    Route::get('/branches/{id}', [BranchController::class, 'show']);
    Route::put('/branches/{id}', [BranchController::class, 'update']);

    // Employee
    Route::post('/add-employees', [EmployeeController::class, 'store']);
    Route::get('/employees', [EmployeeController::class, 'show']);
    Route::get('/employees/{id}', [EmployeeController::class, 'detailEmployee']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);

    // Attendance Check-Clock
    Route::get('/clock-requests', [ClockRequestController::class, 'index']);
    Route::post('/clock-requests/{id}/approve', [ClockRequestController::class, 'approve']);
    Route::post('/clock-requests/{id}/decline', [ClockRequestController::class, 'decline']);
    Route::get('/clock-requests/{id}/detail', [ClockRequestController::class, 'detail']);

    // Profile
    Route::get('/profile-admin', [ProfileController::class, 'profileAdmin']);
});

// Hanya bisa diakses oleh Employee (bukan admin)
Route::middleware(['auth:sanctum', 'employee.only'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'employeeDashboard']);

    // Attendance
    Route::post('/clock-in', [CheckClockController::class, 'store']);
    Route::post('/clock-out', [CheckClockController::class, 'clockOut']);
    Route::post('/leave', [CheckClockController::class, 'leave']);
    Route::post('/absent', [CheckClockController::class, 'absent']);
    Route::get('/check-clocks/records', [CheckClockController::class, 'records']);
    Route::get('/detail-check-clock', [CheckClockController::class, 'detailCheckClock']);

    // Profile
    Route::get('/profile-employee', [ProfileController::class, 'profile']);
});
