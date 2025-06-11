<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClockRequest;
use App\Models\CheckClock;
use App\Models\CheckClockSetting;
use App\Models\Employee;
use App\Models\ClockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CheckClockController extends Controller
{
        public function index()
    {
        $settings = CheckClockSetting::all(['id', 'name', 'type']);
        return response()->json($settings);
    }
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

        $checkClocks = CheckClock::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('date');

        $leaveRequests = ClockRequest::where('user_id', $user->id)
            ->whereIn('check_clock_type', [3, 4])
            ->where('status', 'approved')
            ->get()
            ->keyBy('date');

        $days = [];

        foreach ($checkClocks as $date => $records) {
            $clockIn = $records->firstWhere('check_clock_type', 1);
            $clockOut = $records->firstWhere('check_clock_type', 2);
            $leaveRequest = $leaveRequests[$date] ?? null;

            // Ambil pengaturan hari sesuai hari
            $dayName = Carbon::parse($date)->format('l');
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

            // Attendance type
            $attendanceType = 'Absent';
            if ($clockIn && $clockInLimit) {
                $clockInTime = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                $attendanceType = $clockInTime->lte($clockInLimit) ? 'On Time' : 'Late';
            } elseif ($leaveRequest) {
                $attendanceType = $leaveRequest->check_clock_type == 3 ? 'Sick Leave' : 'Annual Leave';
            } elseif (!$clockIn && !$clockOut && !$leaveRequest) {
                $attendanceType = 'Not Yet Clocked In';
            } elseif ($clockOutTimeSetting && Carbon::parse($date)->gt($clockOutTimeSetting)) {
                $attendanceType = 'Absent';
            } else {
                $attendanceType = 'Late';
            }

            // Work hours
            $workHours = null;
            if ($clockIn && $clockOut) {
                $in = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
                $out = Carbon::createFromFormat('H:i:s', $clockOut->check_clock_time);
                $diffInSeconds = $in->diffInSeconds($out);
                $workHours = gmdate('H:i:s', $diffInSeconds);
            }

            $days[] = [
                'date' => $date,
                'attendance_type' => $attendanceType,
                'clock_in_time' => $clockIn?->check_clock_time,
                'clock_out_time' => $clockOut?->check_clock_time,
                'work_hours' => $workHours,
            ];
        }

        return response()->json([
            'message' => 'Attendance History',
            'data' => $days,
        ]);
    }


    public function leave(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'check_clock_type' => 'required|in:3,4',
            'reason' => 'nullable|string',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $type = (int) $validated['check_clock_type'];
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Upload proof file if available
        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Cek apakah sudah clock in atau sudah request pada tanggal itu
            $hasClockedIn = CheckClock::where('user_id', $user->id)
                ->where('date', $date->format('Y-m-d'))
                ->where('check_clock_type', 1)
                ->exists();

            $hasRequested = ClockRequest::where('user_id', $user->id)
                ->where('date', $date->format('Y-m-d'))
                ->exists();

            if ($hasClockedIn || $hasRequested) {
                // Lewati tanggal ini jika sudah clock in atau sudah request
                continue;
            }

            $dates[] = ClockRequest::create([
                'id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'check_clock_type' => $type,
                'check_clock_time' => now()->format('H:i:s'),
                'date' => $date->format('Y-m-d'),
                'proof_path' => $path,
                'reason' => $validated['reason'] ?? null,
                'status' => 'pending',
            ]);
        }

        if (empty($dates)) {
            return response()->json([
                'message' => 'Tidak ada tanggal yang valid untuk pengajuan cuti (mungkin sudah clock in atau request sebelumnya).'
            ], 400);
        }

        return response()->json([
            'message' => 'Permintaan cuti berhasil dikirim untuk tanggal terpilih.',
            'data' => $dates,
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
