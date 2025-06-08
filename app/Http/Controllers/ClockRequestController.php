<?php

namespace App\Http\Controllers;

use App\Models\ClockRequest;
use App\Models\CheckClock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClockRequestController extends Controller
{

    public function index()
    {
        $clockRequests = ClockRequest::with('user')->get();
        $checkClocks = CheckClock::with('user')->get();

        $clockRequestData = $clockRequests->map(function ($req) {
            $user = $req->user;

            $clockIn = CheckClock::where('user_id', $req->user_id)
                ->where('date', $req->date)
                ->where('check_clock_type', 1)
                ->first();

            $clockOut = CheckClock::where('user_id', $req->user_id)
                ->where('date', $req->date)
                ->where('check_clock_type', 2)
                ->first();

            $workHours = null;
            if ($clockIn && $clockOut) {
                $in = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                $out = Carbon::createFromFormat('H:i:s', $clockOut->check_clock_time);
                $workHours = gmdate('H:i:s', $in->diffInSeconds($out));
            }

            $attendanceType = 'Unknown';
            if (in_array($req->check_clock_type, [3, 4])) {
                $attendanceType = $req->check_clock_type == 3 ? 'Sick Leave' : 'Annual Leave';
            } elseif ($clockIn) {
                $setting = $user?->checkClockSettingTimeForDay($req->date);
                if ($setting) {
                    $clockInLimit = Carbon::createFromFormat('H:i:s', $setting->clock_in)
                        ->addMinutes($setting->late_tolerance);
                    $clockInTime = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                    $attendanceType = $clockInTime->lte($clockInLimit) ? 'On Time' : 'Late';
                } else {
                    $attendanceType = 'On Time';
                }
            }

            return [
                'id' => $req->id,
                'employee_name' => $user?->name,
                'avatar' => $user?->avatar ?? null,
                'date' => $req->date,
                'clock_in' => $clockIn?->check_clock_time,
                'clock_out' => $clockOut?->check_clock_time,
                'work_hours' => $workHours,
                'attendance_type' => $attendanceType,
                'status' => $req->status,
            ];
        });

        $usedDates = $clockRequests
            ->map(fn($r) => $r->user_id . '|' . $r->date)
            ->toArray();

        $checkClockData = $checkClocks->groupBy(fn($cc) => $cc->user_id . '|' . $cc->date)
            ->reject(fn($_, $key) => in_array($key, $usedDates)) // hanya yang tidak diajukan lewat request
            ->map(function ($clocks, $key) {
                [$userId, $date] = explode('|', $key);
                $clockIn = $clocks->firstWhere('check_clock_type', 1);
                $clockOut = $clocks->firstWhere('check_clock_type', 2);

                $user = $clockIn?->user ?? $clockOut?->user;

                $workHours = null;
                if ($clockIn && $clockOut) {
                    $in = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                    $out = Carbon::createFromFormat('H:i:s', $clockOut->check_clock_time);
                    $workHours = gmdate('H:i:s', $in->diffInSeconds($out));
                }

                $attendanceType = 'Unknown';
                if ($clockIn) {
                    $setting = $user?->checkClockSettingTimeForDay($clockIn->date);
                    if ($setting) {
                        $clockInLimit = Carbon::createFromFormat('H:i:s', $setting->clock_in)
                            ->addMinutes($setting->late_tolerance);
                        $clockInTime = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                        $attendanceType = $clockInTime->lte($clockInLimit) ? 'On Time' : 'Late';
                    } else {
                        $attendanceType = 'On Time';
                    }
                }

                return [
                    'id' => null,
                    'employee_name' => $user?->name,
                    'avatar' => $user?->avatar ?? null,
                    'date' => $date,
                    'clock_in' => $clockIn?->check_clock_time,
                    'clock_out' => $clockOut?->check_clock_time,
                    'work_hours' => $workHours,
                    'attendance_type' => $attendanceType,
                    'status' => null,
                ];
            })->values();

        $final = $clockRequestData->merge($checkClockData)->sortByDesc('date')->values();

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paged = new LengthAwarePaginator(
            $final->forPage($currentPage, $perPage),
            $final->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return response()->json([
            'message' => 'Daftar gabungan request dan clock in/out',
            'data' => $paged,
        ]);
    }

    public function approve($id)
    {
        $request = ClockRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return response()->json(['message' => 'Request sudah diproses.'], 400);
        }

        CheckClock::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $request->user_id,
            'check_clock_type' => $request->check_clock_type,
            'check_clock_time' => $request->check_clock_time,
            'proof_path' => $request->proof_path,
            'date' => $request->date,
        ]);

        $request->update(['status' => 'approved']);

        return response()->json(['message' => 'Request berhasil disetujui.']);
    }

    public function decline(Request $req, $id)
    {
        $request = ClockRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return response()->json(['message' => 'Request sudah diproses.'], 400);
        }

        $request->update([
            'status' => 'declined',
            'admin_note' => $req->input('admin_note'),
        ]);

        return response()->json(['message' => 'Request berhasil ditolak.']);
    }

    public function detail($id)
    {
        $request = ClockRequest::with(['user.employee.branch'])->findOrFail($id);

        $employee = $request->user->employee;
        $branch = $employee?->branch;

        $clockIn = CheckClock::where('user_id', $request->user_id)
            ->where('date', $request->date)
            ->where('check_clock_type', 1)
            ->first();

        $clockOut = CheckClock::where('user_id', $request->user_id)
            ->where('date', $request->date)
            ->where('check_clock_type', 2)
            ->first();

        return response()->json([
            'message' => 'Detail kehadiran',
            'data' => [
                'tanggal' => $request->date,
                'status' => $request->status,
                'branch' => $branch?->branch_name,
                'jalan' => $branch?->address,
                'clock_in' => $clockIn?->check_clock_time,
                'clock_out' => $clockOut?->check_clock_time,
            ],
        ]);
    }
}
