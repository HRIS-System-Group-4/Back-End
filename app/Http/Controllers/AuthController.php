<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Employee;

class AuthController extends Controller
{
    public function loginEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company' => ['required'],
            'login' => ['required'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $loginInput = $request->login;
        $password = $request->password;
        $company = $request->company;

        $validCompanies = ['hris', 'jti'];

        if (!in_array($company, $validCompanies)) {
            return response()->json([
                'message' => 'Company tidak terdaftar.',
            ], 422);
        }

        $user = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $loginInput)->where('company', $company)->first()
            : User::where('id', $loginInput)->where('company', $company)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email/User ID tidak terdaftar.',
            ], 422);
        }

        if ($user->is_admin) {
            return response()->json([
                'message' => 'Anda bukan employee.',
            ], 422);
        }

        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Password salah.',
            ], 422);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'company' => $user->company,
                'name' => trim(($user->employee->first_name ?? '') . ' ' . ($user->employee->last_name ?? '')),
            ],
        ]);
    }

    public function loginAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company' => ['required'],
            'login' => ['required'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $loginInput = $request->login;
        $password = $request->password;
        $company = $request->company;

        if ($company !== 'hris') {
            return response()->json([
                'message' => 'Company tidak terdaftar.',
            ], 422);
        }

        $user = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $loginInput)->where('company', $company)->first()
            : User::where('id', $loginInput)->where('company', $company)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email/User ID tidak terdaftar.',
            ], 422);
        }

        if (!$user->is_admin) {
            return response()->json([
                'message' => 'Anda bukan admin.',
            ], 422);
        }

        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Password salah.',
            ], 422);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'company' => $user->company,
                'name' => 'Admin',
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'id' => $request->user()->id,
            'email' => $request->user()->email,
            'is_admin' => $request->user()->is_admin,
            'company' => $request->user()->company,
            'name' => $request->user()->is_admin
                ? 'Admin'
                : trim(($request->user()->employee->first_name ?? '') . ' ' . ($request->user()->employee->last_name ?? '')),
        ]);
    }
}
