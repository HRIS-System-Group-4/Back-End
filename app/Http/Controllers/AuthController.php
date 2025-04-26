<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Employee;

class AuthController extends Controller
{
    public function loginEmployee(Request $request)
    {
        $request->validate([
            'login' => ['required'],
            'password' => ['required'],
        ]);

        $loginInput = $request->login;
        $password = $request->password;

        $user = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $loginInput)->first()
            : User::where('id', $loginInput)->first();

        if (! $user || $user->is_admin || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Email/User ID atau password salah, atau anda bukan employee.'],
            ]);
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
                'name' => trim(($user->employee->first_name ?? '') . ' ' . ($user->employee->last_name ?? '')),
            ],
        ]);
    }

    public function loginAdmin(Request $request)
    {
        $request->validate([
            'login' => ['required'],
            'password' => ['required'],
        ]);

        $loginInput = $request->login;
        $password = $request->password;

        $user = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $loginInput)->first()
            : User::where('id', $loginInput)->first();

        if (! $user || ! $user->is_admin || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Email/User ID atau password salah, atau anda bukan admin.'],
            ]);
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
            'name' => $request->user()->is_admin
                ? 'Admin'
                : trim(($request->user()->employee->first_name ?? '') . ' ' . ($request->user()->employee->last_name ?? '')),
        ]);
    }
}
