<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ======================
    // === ADMIN SECTION ====
    // ======================

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $user = User::create([
            'id' => Str::uuid(),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => true,
        ]);

        Admin::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function loginAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $loginInput = $request->login;
        $password = $request->password;

        $user = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $loginInput)->where('is_admin', true)->first()
            : User::where('id', $loginInput)->where('is_admin', true)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['message' => 'Login gagal.'], 422);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // ============================
    // === EMPLOYEE LOGIN ONLY ====
    // ============================

    public function loginEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company'      => 'required|string',
            'employee_id'  => 'required|string',
            'password'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $company = Company::where('company_username', $request->company)->first();
        if (!$company) {
            return response()->json(['message' => 'Perusahaan tidak ditemukan.'], 404);
        }

        // Cari user berdasarkan employee_id
        $user = User::where('employee_id', $request->employee_id)->where('is_admin', false)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'ID atau password salah.'], 401);
        }

        // Hapus token lama, buat token baru
        $user->tokens()->delete();
        $token = $user->createToken('employee_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login berhasil',
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }


    // ======================
    // === COMMON METHOD ====
    // ======================

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout.',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'name' => $user->is_admin
                ? $user->admin->first_name . ' ' . $user->admin->last_name
                : ($user->employee->first_name ?? 'Employee'),
        ]);
    }
}
