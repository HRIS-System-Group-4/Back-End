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

    public function employeeDashboard()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['error' => 'User is not associated with an employee record'], 400);
        }

        $userId = $user->id;

        // Ambil semua clock-in
        $clockIns = CheckClock::where('user_id', $userId)
            ->where('check_clock_type', CheckClockSetting::TYPE_CLOCK_IN)
            ->orderBy('date')
            ->get();

        $workDays = $clockIns->pluck('date')->unique()->count();

        // Hitung total jam kerja per hari
        $clockData = [];
        foreach ($clockIns as $clockIn) {
            $clockOut = CheckClock::where('user_id', $userId)
                ->where('check_clock_type', CheckClockSetting::TYPE_CLOCK_OUT)
                ->where('date', $clockIn->date)
                ->first();

            if ($clockOut) {
                $start = Carbon::parse($clockIn->check_clock_time);
                $end = Carbon::parse($clockOut->check_clock_time);

                $diffInHours = $end->diffInMinutes($start) / 60;
                $clockData[] = $diffInHours;
            }
        }

        // Total Overtime (jam kerja > 8 jam)
        $overtimeHours = collect($clockData)
            ->filter(fn($hours) => $hours > 8)
            ->map(fn($hours) => $hours - 8)
            ->sum();

        // Rata-rata jam kerja harian
        $avgWorkingTime = count($clockData) > 0
            ? round(array_sum($clockData) / count($clockData), 2)
            : 0;

        // Cuti
        $approvedLeaves = ClockRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->get();

        $totalLeaveDays = $approvedLeaves->count();
        $totalSickLeaveDays = $approvedLeaves->where('check_clock_type', CheckClockSetting::TYPE_SICK_LEAVE)->count();
        $totalAnnualLeaveDays = $approvedLeaves->where('check_clock_type', CheckClockSetting::TYPE_ANNUAL_LEAVE)->count();

        return response()->json([
            'total_working_days' => $workDays,
            'total_overtime_hours' => round($overtimeHours, 2),
            'average_daily_working_hours' => $avgWorkingTime,
            'total_leave_days' => $totalLeaveDays,
            'total_sick_leave_days' => $totalSickLeaveDays,
            'total_annual_leave_days' => $totalAnnualLeaveDays,
        ]);
    }
}
