<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    // ======================
    // === ADMIN SECTION ====
    // ======================

    /**
     * @OA\Post(
     *     path="/admin/register",
     *     summary="Register admin baru",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","email","password","password_confirmation"},
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Registrasi berhasil"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $firstInitial = strtolower(substr($request->first_name, 0, 1));
        $secondInitial = strtolower(substr($request->first_name, -1, 1));
        $thirdInitial = strtolower(substr($request->last_name, 0, 1));
        $fourthInitial = strtolower(substr($request->last_name, -1, 1));

        $prefix = $firstInitial . $secondInitial . $thirdInitial . $fourthInitial;

        $existingCount = User::where('employee_id', 'like', $prefix . '%')->count();
        $number = str_pad($existingCount + 1, 3, '0', STR_PAD_LEFT);

        $employeeId = $prefix . $number;

        $user = User::create([
            'id' => Str::uuid(),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => true,
            'employee_id' => $employeeId,
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

    public function fetchingAdmin(Request $request)
    {
        $user = $request->user();

        if (!$user->is_admin) {
            return response()->json(['message' => 'Anda Bukan Admin.'], 403);
        }

        $admin = $user->admin;

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'full_name' => $admin->first_name . ' ' . $admin->last_name,
            'is_admin' => true,
        ]);
    }


    // ============================
    // === EMPLOYEE LOGIN ONLY ====
    // ============================

        /**
     * @OA\Post(
     *     path="/employee/login",
     *     summary="Login karyawan",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"company", "employee_id", "password"},
     *             @OA\Property(property="company", type="string"),
     *             @OA\Property(property="employee_id", type="string"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login berhasil"),
     *     @OA\Response(response=401, description="Login gagal"),
     *     @OA\Response(response=404, description="Perusahaan tidak ditemukan")
     * )
     */
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

        $user = User::where('employee_id', $request->employee_id)->where('is_admin', false)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'ID atau password salah.'], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('employee_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login berhasil',
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/logout",
     *     summary="Logout user (admin atau employee)",
     *     description="Logout untuk user yang sudah login, baik admin maupun employee, menggunakan token sanctum.",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Berhasil logout")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout.',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/user",
     *     summary="Ambil data user yang sedang login (admin atau employee)",
     *     description="Mengambil data user yang sedang login, baik admin maupun employee, berdasarkan token sanctum.",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Data user berhasil diambil")
     * )
     */
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
