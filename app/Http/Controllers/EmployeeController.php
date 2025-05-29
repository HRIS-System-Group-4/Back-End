<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Branch;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeDetailResource;
use Illuminate\Http\Request;


class EmployeeController extends Controller
{
    /**
     * POST /api/employees
     * Simpan karyawan baru plus user-login, avatar, bank info, check-clock setting & surat.
     */


    public function store(StoreEmployeeRequest $request)
    {
        DB::beginTransaction();

        try {
            $employeeId = (string) Str::uuid();

            // Ambil company_id dari branch_id
            $branch = Branch::findOrFail($request->branch_id);
            $companyId = $branch->company_id;

            $user = User::create([
                'id'          => Str::uuid(),
                'email'       => $request->email,
                'password'    => Hash::make($request->password ?? Str::random(10)),
                'is_admin'    => false,
                'employee_id' => $employeeId,
            ]);

            $avatarPath = $request->file('avatar')
                ? $request->file('avatar')->store('avatars', 'public')
                : null;

            $employee = Employee::create([
                'id'              => $employeeId,
                'user_id'         => $user->id,
                'company_id'      => $companyId,   // otomatis dari branch
                'first_name'      => $request->first_name,
                'last_name'       => $request->last_name,
                'gender'          => $request->gender,
                'nik'             => $request->nik,
                'phone_number'    => $request->phone_number,
                'birth_place'     => $request->birth_place,
                'birth_date'      => $request->birth_date,
                'branch_id'       => $request->branch_id,
                'job_title'       => $request->job_title,
                'grade'           => $request->grade,
                'employment_type' => $request->contract_type,
                'sp_type'         => $request->sp_type,
                'bank_name'       => $request->bank,
                'bank_account_no' => $request->bank_account_number,
                'bank_account_owner' => $request->account_holder_name,
                'ck_settings_id'  => $request->check_clock_setting_id,
                'avatar_path'     => $avatarPath,
            ]);

            // kode upload surat dst...

            DB::commit();

            return response()->json([
                'message' => 'Employee has been successfully created and saved',
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

    public function show()
    {
        $employees = Employee::with('branch')->paginate(10); // Menampilkan 10 data per halaman
        return EmployeeResource::collection($employees);
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
                'employment_type' => $request->contract_type ?? $employee->employment_type,
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