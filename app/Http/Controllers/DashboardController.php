<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\CheckClock;
use App\Models\Letter;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $totalEmployees = Employee::count();

        $employeesClockedInToday = CheckClock::whereDate('created_at', $today)
            ->distinct('user_id')
            ->count('user_id');

        $totalCheckClocks = CheckClock::count();

        // $totalLeaveRequests = Letter::where('type', 'cuti')->count();

        return response()->json([
            'total_employees' => $totalEmployees,
            'employees_clocked_in_today' => $employeesClockedInToday,
            'total_check_clocks' => $totalCheckClocks,
            // 'total_leave_requests' => $totalLeaveRequests,
        ]);
    }
}
