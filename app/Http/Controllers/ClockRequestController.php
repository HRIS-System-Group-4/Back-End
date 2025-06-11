<?php

namespace App\Http\Controllers;

use App\Models\ClockRequest;
use App\Models\CheckClock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClockRequestController extends Controller
{

    public function index(Request $request)
    {
        $clockRequests = ClockRequest::with('user.employee')->get();
        $checkClocks = CheckClock::with('user.employee')->get();

        $usedDates = $clockRequests
            ->filter(fn($r) => $r->status === 'approved')
            ->map(fn($r) => $r->user_id . '|' . $r->date)
            ->toArray();

        $clockRequestData = $clockRequests->map(function ($req) {
            $user = $req->user;
            $employee = $user?->employee;

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
                'employee_name' => $employee ? $employee->first_name . ' ' . $employee->last_name : null,
                'avatar' => $employee?->avatar_path,
                'date' => $req->date,
                'clock_in' => $clockIn?->check_clock_time,
                'clock_out' => $clockOut?->check_clock_time,
                'work_hours' => $workHours,
                'attendance_type' => $attendanceType,
                'status' => $req->status,
            ];
        });

        $checkClockData = $checkClocks
            ->groupBy(fn($cc) => $cc->user_id . '|' . $cc->date)
            ->reject(fn($_, $key) => in_array($key, $usedDates))
            ->map(function ($clocks, $key) {
                [$userId, $date] = explode('|', $key);
                $clockIn = $clocks->firstWhere('check_clock_type', 1);
                $clockOut = $clocks->firstWhere('check_clock_type', 2);

                $user = $clockIn?->user ?? $clockOut?->user;
                $employee = $user?->employee;

                $workHours = null;
                if ($clockIn && $clockOut) {
                    $in = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                    $out = Carbon::createFromFormat('H:i:s', $clockOut->check_clock_time);
                    $workHours = gmdate('H:i:s', $in->diffInSeconds($out));
                }

                $attendanceType = 'Unknown';
                if ($clockIn) {
                    $setting = $user?->checkClockSettingTimeForDay($date);
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
                    'id' => $clockIn?->id ?? $clockOut?->id,
                    'employee_name' => $employee ? $employee->first_name . ' ' . $employee->last_name : null,
                    'avatar' => $employee?->avatar_path,
                    'date' => $date,
                    'clock_in' => $clockIn?->check_clock_time,
                    'clock_out' => $clockOut?->check_clock_time,
                    'work_hours' => $workHours,
                    'attendance_type' => $attendanceType,
                    'status' => null,
                ];
            })->values();

        $final = $clockRequestData->merge($checkClockData)
            ->sort(function ($a, $b) {
                // Urutkan berdasarkan tanggal terbaru
                $dateComparison = strcmp($b['date'], $a['date']); // DESC
                if ($dateComparison === 0) {
                    // Jika tanggal sama, urutkan clock_in dari yang terbaru
                    return strcmp($b['clock_in'] ?? '00:00:00', $a['clock_in'] ?? '00:00:00'); // DESC
                }
                return $dateComparison;
            })
            ->values();


        $perPage = 10;
        $page = request()->get('page', 1);
        $paged = $final->forPage($page, $perPage)->values();

        return response()->json([
            'message' => 'Admin Check Clock Overview',
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
            'status' => 'rejected',
            'admin_note' => $req->input('admin_note'),
        ]);

        return response()->json(['message' => 'Request berhasil ditolak.']);
    }

    public function detail($id)
    {
        $source = request()->get('source');

        if ($source === 'check_clock') {
            $clockIn = CheckClock::with(['user.employee.branch'])
                ->where('id', $id)
                ->where('check_clock_type', 1)
                ->firstOrFail();

            $clockOut = CheckClock::where('user_id', $clockIn->user_id)
                ->where('date', $clockIn->date)
                ->where('check_clock_type', 2)
                ->first();

            $employee = $clockIn->user->employee;
            $branch = $employee?->branch;

            // Attendance Type Logic
            $attendanceType = 'Unknown';
            $setting = $clockIn->user?->checkClockSettingTimeForDay($clockIn->date);
            if ($setting) {
                $clockInLimit = \Carbon\Carbon::createFromFormat('H:i:s', $setting->clock_in)
                    ->addMinutes($setting->late_tolerance);
                $clockInTime = \Carbon\Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                $attendanceType = $clockInTime->lte($clockInLimit) ? 'On Time' : 'Late';
            } else {
                $attendanceType = 'On Time';
            }

            return response()->json([
                'message' => 'Detail kehadiran dari Check Clock',
                'data' => [
                    'date' => $clockIn->date,
                    'status' => null,
                    'branch' => $branch?->branch_name,
                    'address' => $branch?->address,
                    'clock_in' => $clockIn?->check_clock_time,
                    'clock_out' => $clockOut?->check_clock_time,
                    'proof' => $clockIn->proof_path ? Storage::url($clockIn->proof_path) : null,
                    'employee_name' => $employee ? $employee->first_name . ' ' . $employee->last_name : null,
                    'email' => $clockIn->user?->email,
                    'job_title' => $employee?->job_title,
                    'grade' => $employee?->grade,
                    'avatar' => $employee?->avatar_path,
                    'attendance_type' => $attendanceType,
                ],
            ]);
        }

        // Default: dari ClockRequest
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

        $attendanceType = 'Unknown';
        if (in_array($request->check_clock_type, [3, 4])) {
            $attendanceType = $request->check_clock_type == 3 ? 'Sick Leave' : 'Annual Leave';
        } elseif ($clockIn) {
            $setting = $request->user?->checkClockSettingTimeForDay($request->date);
            if ($setting) {
                $clockInLimit = \Carbon\Carbon::createFromFormat('H:i:s', $setting->clock_in)
                    ->addMinutes($setting->late_tolerance);
                $clockInTime = \Carbon\Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                $attendanceType = $clockInTime->lte($clockInLimit) ? 'On Time' : 'Late';
            } else {
                $attendanceType = 'On Time';
            }
        }

        return response()->json([
            'message' => 'Detail kehadiran dari Clock Request',
            'data' => [
                'date' => $request->date,
                'status' => $request->status,
                'branch' => $branch?->branch_name,
                'address' => $branch?->address,
                'clock_in' => $clockIn?->check_clock_time,
                'clock_out' => $clockOut?->check_clock_time,
                'proof' => $request->proof_path ? Storage::url($request->proof_path) : null,
                'employee_name' => $employee ? $employee->first_name . ' ' . $employee->last_name : null,
                'email' => $request->user?->email,
                'job_title' => $employee?->job_title,
                'grade' => $employee?->grade,
                'avatar' => $employee?->avatar_path,
                'attendance_type' => $attendanceType,
            ],
        ]);
    }
}
