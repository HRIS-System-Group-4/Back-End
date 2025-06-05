<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\CheckClock;
use App\Models\CheckClockSetting;
use App\Models\ClockRequest;
use App\Models\Branch;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $admin = $user->admin;
        if (!$admin || !$admin->company_id) {
            return response()->json(['error' => 'Admin does not have a company ID'], 400);
        }

        $companyId = $admin->company_id;
        $today = Carbon::today();

        $employees = Employee::where('company_id', $companyId)->get();
        $totalEmployees = $employees->count();
        $employeeUserIds = $employees->pluck('user_id');

        $clockedInUserIds = CheckClock::whereDate('created_at', $today)
            ->where('check_clock_type', CheckClockSetting::TYPE_CLOCK_IN)
            ->whereIn('user_id', $employeeUserIds)
            ->pluck('user_id')
            ->unique();

        $employeesClockedInToday = $clockedInUserIds->count();

        $percentageClockedIn = $totalEmployees > 0
            ? round(($employeesClockedInToday / $totalEmployees) * 100, 2)
            : 0;

        $leaveTypes = [
            CheckClockSetting::TYPE_SICK_LEAVE,
            CheckClockSetting::TYPE_ANNUAL_LEAVE,
        ];

        $approvedLeaveUserIds = ClockRequest::whereIn('check_clock_type', $leaveTypes)
            ->where('status', 'approved')
            ->whereDate('created_at', $today)
            ->whereIn('user_id', $employeeUserIds)
            ->pluck('user_id')
            ->unique();

        $totalApprovedLeave = $approvedLeaveUserIds->count();

        $absentUserIds = $employeeUserIds
            ->diff($clockedInUserIds)
            ->diff($approvedLeaveUserIds);

        $totalAbsent = $absentUserIds->count();

        $employmentTypeCounts = Employee::where('company_id', $companyId)
            ->selectRaw('employment_type, COUNT(*) as total')
            ->groupBy('employment_type')
            ->pluck('total', 'employment_type');

        $totalBranches = Branch::where('company_id', $companyId)->count();

        return response()->json([
            'total_employees' => $totalEmployees,
            'employees_clocked_in_today' => $employeesClockedInToday,
            'percentage_clocked_in' => $percentageClockedIn,
            'total_leave_&_absent' => $totalApprovedLeave + $totalAbsent,
            'employment_types' => $employmentTypeCounts,
            'total_branches' => $totalBranches,
        ]);
    }
}
