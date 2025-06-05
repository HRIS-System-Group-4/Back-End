<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\EmployeeResource;
use App\Models\User;
use App\Models\Employee;
use App\Models\Users;
use App\Models\Branch;
use App\Models\CheckClockSetting;
use App\Models\Letter;
use App\Models\Admin;

class ProfileController extends Controller
{
    public function profile()
    {
        $user = Auth::user();

        $role = $user->is_admin ? 'Admin' : 'Employee';

        $employee = null;
        $letters = [];

        if (!$user->is_admin) {
            $employee = Employee::with(['branch', 'checkClockSetting', 'letters'])
                ->where('user_id', $user->id)
                ->first();

            if ($employee) {
                $letters = $employee->letters;
            }
        }

        return response()->json([
            'role' => $role,
            'avatar' => $employee?->avatar_path,
            'nik' => $employee?->nik,
            'first_name' => $employee?->first_name,
            'last_name' => $employee?->last_name,
            'gender' => $employee?->gender,
            'phone_number' => $employee?->phone_number,
            'birth_place' => $employee?->birth_place,
            'birth_date' => $employee?->birth_date,
            'branch' => $employee?->branch?->name,
            'job_title' => $employee?->job_title,
            'contract_type' => $employee?->employment_type,
            'grade' => $employee?->grade,
            'sp_type' => $employee?->sp_type,
            'bank_name' => $employee?->bank_name,
            'bank_account_no' => $employee?->bank_account_no,
            'bank_account_owner' => $employee?->bank_account_owner,
            'check_clock_setting' => $employee?->checkClockSetting?->type,
            'letters' => $letters,
        ]);
    }

    public function profileAdmin()
    {
        $user = Auth::user();

        $role = $user->is_admin ? 'Admin' : 'Employee';

        $employee = null;
        $letters = [];

        if (!$user->is_admin) {
            $employee = Employee::with(['branch', 'checkClockSetting', 'letters'])
                ->where('user_id', $user->id)
                ->first();

            if ($employee) {
                $letters = $employee->letters;
            }
        }

        return response()->json([
            'role' => $role,
            'avatar' => $employee?->avatar_path,
            'nik' => $employee?->nik,
            'first_name' => $employee?->first_name,
            'last_name' => $employee?->last_name,
            'gender' => $employee?->gender,
            'phone_number' => $employee?->phone_number,
            'birth_place' => $employee?->birth_place,
            'birth_date' => $employee?->birth_date,
            'branch' => $employee?->branch?->name,
            'job_title' => $employee?->job_title,
            'contract_type' => $employee?->employment_type,
            'grade' => $employee?->grade,
            'sp_type' => $employee?->sp_type,
            'bank_name' => $employee?->bank_name,
            'bank_account_no' => $employee?->bank_account_no,
            'bank_account_owner' => $employee?->bank_account_owner,
            'check_clock_setting' => $employee?->checkClockSetting?->type,
            'letters' => $letters,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if ($user->is_admin) {
            // ADMIN
            $admin = Admin::where('user_id', $user->id)->firstOrFail();

            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:255',
                'gender' => 'nullable|in:L,P',
                'address' => 'nullable|string',
            ]);

            $admin->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
                'address' => $request->address,
            ]);

            return response()->json(['message' => 'Admin profile updated successfully.']);
        } else {
            // EMPLOYEE
            $employee = Employee::where('user_id', $user->id)->firstOrFail();

            $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'phone_number' => 'nullable|string|max:20',
                'gender' => 'nullable|in:L,P',
                'birth_place' => 'nullable|string|max:100',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string',
                'job_title' => 'nullable|string|max:100',
                'employment_type' => 'nullable|string',
                'grade' => 'nullable|string|max:20',
                'sp_type' => 'nullable|string|max:50',
                'bank_name' => 'nullable|string|max:100',
                'bank_account_no' => 'nullable|string|max:30',
                'bank_account_owner' => 'nullable|string|max:100',
            ]);

            $employee->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
                'birth_place' => $request->birth_place,
                'birth_date' => $request->birth_date,
                'address' => $request->address,
                'job_title' => $request->job_title,
                'employment_type' => $request->employment_type,
                'grade' => $request->grade,
                'sp_type' => $request->sp_type,
                'bank_name' => $request->bank_name,
                'bank_account_no' => $request->bank_account_no,
                'bank_account_owner' => $request->bank_account_owner,
            ]);

            return response()->json(['message' => 'Employee profile updated successfully.']);
        }
    }
}
