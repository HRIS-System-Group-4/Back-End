<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Models\User;
use App\Models\Employee;
use App\Mail\SendEmployeeCredentialMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Branch;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeDetailResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class EmployeeController extends Controller
{
    /**
     * POST /api/employees
     * Simpan karyawan baru plus user-login, avatar, bank info, check-clock setting & surat.
     */


    public function store(StoreEmployeeRequest $request)
    {
        dd($request->all());
        Log::info('Employment type dari request: ' . $request->employment_type);


        DB::beginTransaction();

        try {
            // Cari branch dan company
            $branch = Branch::findOrFail($request->branch_id);
            $company = $branch->company;

            // Buat employee_id berdasarkan prefix company dan nomor urut
            $companyName = $company->company_username; // Misalnya: "OpenAI"
            $prefix = strtoupper(substr($companyName, 0, 3)); // "OPE"
            $employeeCount = Employee::where('company_id', $company->id)->count();
            $nextNumber = str_pad($employeeCount + 1, 3, '0', STR_PAD_LEFT); // "001"
            $employeeShortId = "{$prefix}-{$nextNumber}"; // contoh: "OPE-001"

            // Generate password acak
            $plainPassword = Str::random(8);

            // Buat user
            $user = User::create([
                'id'          => Str::uuid(),
                'email'       => $request->email,
                'password'    => Hash::make($plainPassword),
                'is_admin'    => false,
                'employee_id' => $employeeShortId,
            ]);

            // Simpan avatar jika ada
            $avatarPath = $request->file('avatar')
                ? $request->file('avatar')->store('avatars', 'public')
                : null;

            // Buat employee
            $employee = Employee::create([
                'id'                 => $employeeShortId,
                'user_id'            => $user->id,
                'company_id'         => $company->id,
                'first_name'         => $request->first_name,
                'last_name'          => $request->last_name,
                'gender'             => $request->gender,
                'nik'                => $request->nik,
                'phone_number'       => $request->phone_number,
                'birth_place'        => $request->birth_place,
                'birth_date'         => $request->birth_date,
                'branch_id'          => $request->branch_id,
                'job_title'          => $request->job_title,
                'grade'              => $request->grade,
                'employment_type'    => $request->employment_type,
                'sp_type'            => $request->sp_type ?? null, // Optional
                'bank_name'          => $request->bank,
                'bank_account_no'    => $request->bank_account_number,
                'bank_account_owner' => $request->account_holder_name,
                'ck_settings_id'     => $request->check_clock_setting_id,
                'avatar_path'        => $avatarPath,
            ]);

            // Ambil nama company
            $companyName = $company->company_username;

            // Ambil avatar URL kalau ada, misal menggunakan Storage facade
            $avatarUrl = $avatarPath ? asset('storage/' . $avatarPath) : null;

            // Kirim email ke employee
            Mail::to($request->email)->send(
                new SendEmployeeCredentialMail($employeeShortId, $plainPassword, $request->email, $companyName, $avatarUrl)
            );

            DB::commit();

            return response()->json([
                'message' => 'Employee has been successfully created and email sent',
                'data'    => $employee->load('user', 'branch', 'letters'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to Add Employee',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    // public function show()
    // {
    //     $employees = Employee::with('branch')->paginate(10); // Menampilkan 10 data per halaman
    //     return EmployeeResource::collection($employees);
    // }
    public function show(Request $request)
{
    $perPage = request()->get('per_page', 10);
    $employees = Employee::with('branch')->paginate($perPage);

    return response()->json([
    'data' => EmployeeResource::collection($employees),
    'meta' => [
        'current_page' => $employees->currentPage(),
        'last_page' => $employees->lastPage(),
        'per_page' => $employees->perPage(),
        'total' => $employees->total(),
    ],
    'links' => [
        'first' => $employees->url(1),
        'last' => $employees->url($employees->lastPage()),
        'prev' => $employees->previousPageUrl(),
        'next' => $employees->nextPageUrl(),
    ],
]);

}

    public function detailEmployee($id)
    {
        $employee = Employee::with(['branch', 'user'])->findOrFail($id);
        return new EmployeeDetailResource($employee);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $employee = Employee::findOrFail($id);

            // Jika branch diubah, ambil company_id baru
            if ($request->has('branch_id')) {
                $branch = Branch::findOrFail($request->branch_id);
                $employee->company_id = $branch->company_id;
                $employee->branch_id = $request->branch_id;
            }

            // Update avatar
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $employee->avatar_path = $avatarPath;
            }

            // Update data Employee
            $employee->update([
                'first_name'      => $request->first_name ?? $employee->first_name,
                'last_name'       => $request->last_name ?? $employee->last_name,
                'gender'          => $request->gender ?? $employee->gender,
                'nik'             => $request->nik ?? $employee->nik,
                'phone_number'    => $request->phone_number ?? $employee->phone_number,
                'birth_place'     => $request->birth_place ?? $employee->birth_place,
                'birth_date'      => $request->birth_date ?? $employee->birth_date,
                'job_title'       => $request->job_title ?? $employee->job_title,
                'grade'           => $request->grade ?? $employee->grade,
                'employment_type' => $request->employment_type ?? $employee->employment_type,
                'sp_type'         => $request->sp_type ?? $employee->sp_type,
                'bank_name'       => $request->bank ?? $employee->bank_name,
                'bank_account_no' => $request->bank_account_number ?? $employee->bank_account_no,
                'bank_account_owner' => $request->account_holder_name ?? $employee->bank_account_owner,
                'ck_settings_id'  => $request->check_clock_setting_id ?? $employee->ck_settings_id,
                'address'         => $request->address ?? $employee->address,
            ]);

            // Buat Update Email
            if ($request->has('email')) {
                $employee->user->email = $request->email;
                $employee->user->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Employee updated successfully',
                'data' => new EmployeeDetailResource($employee->load(['branch', 'user']))
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update employee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
