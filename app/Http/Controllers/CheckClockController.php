<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClockRequest;
use App\Models\CheckClock;
use App\Models\Employee;
use App\Models\ClockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CheckClockController extends Controller
{
    public function store(StoreClockRequest $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $alreadyClockedIn = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->where('date', $today)
            ->exists();

        if ($alreadyClockedIn) {
            return response()->json(['message' => 'Anda sudah melakukan clock in hari ini.'], 400);
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $clockIn = CheckClock::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'check_clock_type' => 1,
            'check_clock_time' => $request->input('check_clock_time', now()->format('H:i:s')),
            'date' => $today,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'proof_path' => $path,
        ]);

        return response()->json([
            'message' => 'Clock in success.',
            'data' => $clockIn,
        ]);
    }

    public function clockOut(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $alreadyClockedOut = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 2)
            ->where('date', $today)
            ->exists();

        if ($alreadyClockedOut) {
            return response()->json(['message' => 'Anda sudah melakukan clock out hari ini.'], 400);
        }

        $hasClockedIn = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->where('date', $today)
            ->exists();

        if (!$hasClockedIn) {
            return response()->json(['message' => 'Anda belum melakukan clock in hari ini.'], 400);
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $clockOut = CheckClock::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'check_clock_type' => 2,
            'check_clock_time' => $request->input('check_clock_time', now()->format('H:i:s')),
            'date' => $today,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'proof_path' => $path,
        ]);

        return response()->json([
            'message' => 'Clock out success.',
            'data' => $clockOut,
        ]);
    }


    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function records()
    {
        $user = auth()->user();
        $today = Carbon::now();
        $dayName = $today->format('l');

        $settingTime = DB::table('check_clock_setting_times')
            ->join('check_clock_settings', 'check_clock_settings.id', '=', 'check_clock_setting_times.ck_settings_id')
            ->where('check_clock_settings.id', $user->ck_settings_id)
            ->where('day', $dayName)
            ->first();

        $clockInLimit = $settingTime
            ? Carbon::createFromFormat('H:i:s', $settingTime->clock_in)->addMinutes($settingTime->late_tolerance)
            : null;

        $clockOutTimeSetting = $settingTime
            ? Carbon::createFromFormat('H:i:s', $settingTime->clock_out)
            : null;

        $clockIn = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->whereDate('date', $today->toDateString())
            ->first();

        $clockOut = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 2)
            ->whereDate('date', $today->toDateString())
            ->first();

        $leaveRequest = ClockRequest::where('user_id', $user->id)
            ->whereIn('check_clock_type', [3, 4])
            ->where('status', 'approved')
            ->whereDate('date', $today->toDateString())
            ->first();

        $attendanceType = 'Absent';

        // if ($clockIn && $clockInLimit) {
        //     $clockInTime = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
        //     $attendanceType = $clockInTime->lte($clockInLimit) ? 'On Time' : 'Late';
        // } elseif ($leaveRequest) {
        //     $attendanceType = $leaveRequest->check_clock_type == 3 ? 'Sick Leave' : 'Annual Leave';
        // } elseif ($clockOutTimeSetting && $today->gt($clockOutTimeSetting)) {
        //     $attendanceType = 'Absent';
        // } else {
        //     $attendanceType = 'Late';
        // }
        if ($clockIn && $clockInLimit) {
            $clockInTime = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
            $attendanceType = $clockInTime->lte($clockInLimit) ? 'On Time' : 'Late';
        } elseif ($leaveRequest) {
            $attendanceType = $leaveRequest->check_clock_type == 3 ? 'Sick Leave' : 'Annual Leave';
        } elseif (!$clockIn && !$clockOut && !$leaveRequest) {
            $attendanceType = 'Not Yet Clocked In';
        } elseif ($clockOutTimeSetting && $today->gt($clockOutTimeSetting)) {
            $attendanceType = 'Absent';
        } else {
            $attendanceType = 'Late';
        }

        $clockInRequest = ClockRequest::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->whereDate('date', $today->toDateString())
            ->latest()
            ->first();

        $clockOutRequest = ClockRequest::where('user_id', $user->id)
            ->where('check_clock_type', 2)
            ->whereDate('date', $today->toDateString())
            ->latest()
            ->first();

        $approvalStatus = [
            'clock_in' => $clockInRequest?->status,
            'clock_out' => $clockOutRequest?->status,
        ];

        // Work hours calculation
        $workHours = null;
        if ($clockIn && $clockOut) {
            $in = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
            $out = Carbon::createFromFormat('H:i:s', $clockOut->check_clock_time);
            $diffInSeconds = $in->diffInSeconds($out);
            $workHours = gmdate('H:i:s', $diffInSeconds);
        }

        return response()->json([
            'message' => 'Attendance Record',
            'data' => [
                'id' => $clockIn?->id ?? $clockOut?->id,
                'date' => $today->toDateString(),
                'attendance_type' => $attendanceType,
                'clock_in_time' => $clockIn?->check_clock_time,
                'clock_out_time' => $clockOut?->check_clock_time,
                'work_hours' => $workHours,
            ],
        ]);
    }

    public function leave(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'check_clock_type' => 'required|in:3,4',
            'reason' => 'nullable|string',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        $today = now()->format('Y-m-d');
        $type = (int) $validated['check_clock_type'];

        $alreadyClockedIn = CheckClock::where('user_id', $user->id)
            ->where('date', $today)
            ->where('check_clock_type', 1)
            ->exists();

        if ($alreadyClockedIn) {
            return response()->json([
                'message' => 'Anda sudah melakukan clock in hari ini dan tidak bisa mengajukan cuti.',
            ], 400);
        }

        $alreadyRequested = ClockRequest::where('user_id', $user->id)
            ->where('date', $today)
            ->exists();

        if ($alreadyRequested) {
            return response()->json([
                'message' => 'Anda sudah mengajukan permintaan check clock hari ini.',
            ], 400);
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $clockRequest = ClockRequest::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'check_clock_type' => $type,
            'check_clock_time' => now()->format('H:i:s'),
            'date' => $today,
            'proof_path' => $path,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan izin telah dikirim dan menunggu persetujuan.',
            'data' => $clockRequest,
        ]);
    }

    public function absent(Request $request)
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');

        $alreadyRequested = ClockRequest::where('user_id', $user->id)
            ->where('check_clock_type', 5)
            ->where('date', $today)
            ->exists();

        if ($alreadyRequested) {
            return response()->json(['message' => 'Anda sudah mengirim permintaan absen hari ini.'], 400);
        }

        $clockRequest = ClockRequest::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'check_clock_type' => 5,
            'check_clock_time' => now()->format('H:i:s'),
            'date' => now()->format('Y-m-d'),
            'reason' => $request->input('reason', 'Tanpa Keterangan'),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan absen telah dikirim.',
            'data' => $clockRequest,
        ]);
    }

    public function detailCheckClock(Request $request)
    {
        $user = auth()->user();
        $date = $request->query('date');

        if (!$date) {
            return response()->json([
                'message' => 'Tanggal diperlukan.'
            ], 422);
        }

        $employee = $user->employee;
        $branch = $employee?->branch;

        $clockIn = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->whereDate('date', $date)
            ->first();

        $clockOut = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 2)
            ->whereDate('date', $date)
            ->first();

        $workHours = null;
        if ($clockIn && $clockOut) {
            $in = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
            $out = Carbon::createFromFormat('H:i:s', $clockOut->check_clock_time);
            $workHours = gmdate('H:i:s', $in->diffInSeconds($out));
        }

        $attendanceType = null;
        if ($clockIn) {
            $threshold = Carbon::createFromTimeString('08:00:00');
            $inTime = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
            $attendanceType = $inTime->lessThanOrEqualTo($threshold) ? 'On Time' : 'Late';
        }

        return response()->json([
            'message' => 'Detail Check Clock',
            'data' => [
                'date' => $date,
                'attendance_type' => $attendanceType,
                'branch_name' => $branch?->branch_name,
                'branch_address' => $branch?->address,
                'clock_in' => $clockIn ? [
                    'id' => $clockIn->id,
                    'time' => $clockIn->check_clock_time,
                    'proof_path' => $clockIn->proof_path,
                ] : null,
                'clock_out' => $clockOut ? [
                    'id' => $clockOut->id,
                    'time' => $clockOut->check_clock_time,
                    'proof_path' => $clockOut->proof_path,
                ] : null,
                'work_hours' => $workHours,
            ],
        ]);
    }
}
