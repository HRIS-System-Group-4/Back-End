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
Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
Route::post('employee/login', [AuthController::class, 'loginEmployee']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

// Check Clock
Route::post('/add/check-clock-settings', [CheckClockSettingController::class, 'store']);
Route::get('/check-clock-settings/{id}/edit', [CheckClockSettingController::class, 'edit']);
Route::get('/check-clock-settings/{id}', [CheckClockSettingController::class, 'show']);

// Employee
// Route::get('/employees', [EmployeeController::class, 'show']);
// Route::get('/employees/{id}', [EmployeeController::class, 'detailEmployee']);
// Route::put('/employees/{id}', [EmployeeController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    // Sementara api dibuat public buat pengujian, kalo sudah fix baru dimasukkan sanctum
    Route::post('/add-employees', [EmployeeController::class, 'store']);

    // checkclock
    Route::put('/check-clock-settings/{id}', [CheckClockSettingController::class, 'update']);
});

// Hanya bisa diakses oleh Admin
Route::middleware(['auth:sanctum', 'admin.only'])->group(function () {
    // Company
    Route::post('/company', [CompanyController::class, 'store']);
    Route::put('/company/{company}/location', [CompanyController::class, 'updateLocation']);

    // Subscription
    Route::post('/subscription/activate', [SubscriptionController::class, 'activate']);
    Route::get('/subscription/status', [SubscriptionController::class, 'status']);

    // Detail Admin
    Route::post('admin/logout', [AuthController::class, 'logout']);
    Route::get('admin/user', [AuthController::class, 'user']);
    Route::get('admin/profile', [AuthController::class, 'fetchAdmin']);

    // Branch
    Route::get('/branches', [BranchController::class, 'overview']);
    Route::post('/add-branch', [BranchController::class, 'store']);
    Route::get('/branches/{id}', [BranchController::class, 'show']);
});

// Hanya bisa diakses oleh Employee (bukan admin)
Route::middleware(['auth:sanctum', 'employee.only'])->group(function () {
    Route::get('/employees', [EmployeeController::class, 'show']);
    Route::get('/employees/{id}', [EmployeeController::class, 'detailEmployee']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);

    // Attendance
    Route::post('/clock-in', [CheckClockController::class, 'store']);
    Route::post('/clock-out', [CheckClockController::class, 'clockOut']);
    Route::get('/check-clocks/records', [CheckClockController::class, 'records']);
});
