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
    // private function isWeekend(): bool
    // {
    //     if (app()->environment('local')) {
    //         return false;
    //     }

    //     $day = now()->format('l');
    //     return in_array($day, ['Saturday', 'Sunday']);
    // }

    public function store(StoreClockRequest $request)
    {
        // if ($this->isWeekend()) {
        //     return response()->json(['message' => 'Tidak bisa melakukan clock in di hari libur (Sabtu/Minggu).'], 403);
        // }
        $user = $request->user();
        $today = now()->format('Y-m-d');

        $alreadyRequested = ClockRequest::where('user_id', $user->id)
            ->where('date', $today)
            ->exists();

        if ($alreadyRequested) {
            return response()->json(['message' => 'Anda sudah mengajukan permintaan check clock hari ini.'], 400);
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $clockRequest = ClockRequest::create([
            'id'               => Str::uuid()->toString(),
            'user_id'          => $user->id,
            'check_clock_type' => 1,
            'check_clock_time' => $request->input('check_clock_time', now()->format('H:i:s')),
            'date'             => $today,
            'proof_path'       => $path,
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
            'status'           => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan clock in telah dikirim dan menunggu persetujuan admin.',
            'data'    => $clockRequest,
        ]);
    }


    public function clockOut(Request $request)
    {
        // if ($this->isWeekend()) {
        //     return response()->json(['message' => 'Tidak bisa melakukan clock out di hari libur (Sabtu/Minggu).'], 403);
        // }

        $user = $request->user();
        $today = now()->format('Y-m-d');

        $alreadyClockedOut = ClockRequest::where('user_id', $user->id)
            ->where('check_clock_type', 2)
            ->where('date', $today)
            ->exists();

        if ($alreadyClockedOut) {
            return response()->json(['message' => 'Anda sudah mengirim permintaan clock out hari ini.'], 400);
        }

        $hasClockedIn = ClockRequest::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->where('date', $today)
            ->exists();

        if (!$hasClockedIn) {
            return response()->json(['message' => 'Anda belum melakukan clock in hari ini.'], 400);
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $clockRequest = ClockRequest::create([
            'id'               => Str::uuid()->toString(),
            'user_id'          => $user->id,
            'check_clock_type' => 2,
            'check_clock_time' => $request->input('check_clock_time', now()->format('H:i:s')),
            'date'             => $today,
            'proof_path'       => $path,
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
            'status'           => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan clock out telah dikirim dan menunggu persetujuan admin.',
            'data'    => $clockRequest,
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

        // Ambil setting waktu sesuai hari
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

        // Ambil clock in dan clock out dari CheckClock
        $clockIn = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->whereDate('date', $today->toDateString())
            ->first();

        $clockOut = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 2)
            ->whereDate('date', $today->toDateString())
            ->first();

        // Leave (Sick/Annual)
        $leaveRequest = ClockRequest::where('user_id', $user->id)
            ->whereIn('check_clock_type', [3, 4])
            ->where('status', 'approved')
            ->whereDate('date', $today->toDateString())
            ->first();

        // Attendance type logic
        $attendanceType = 'Absent';

        if ($clockIn && $clockInLimit) {
            $clockInTime = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
            $attendanceType = $clockInTime->lte($clockInLimit) ? 'On Time' : 'Late';
        } elseif ($leaveRequest) {
            $attendanceType = $leaveRequest->check_clock_type == 3 ? 'Sick Leave' : 'Annual Leave';
        } elseif ($clockOutTimeSetting && $today->gt($clockOutTimeSetting)) {
            $attendanceType = 'Absent';
        } else {
            $attendanceType = 'Late';
        }

        // Clock in & out approval requests
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
                'date' => $today->toDateString(),
                'attendance_type' => $attendanceType,
                'clock_in_time' => $clockIn?->check_clock_time,
                'clock_out_time' => $clockOut?->check_clock_time,
                'approval' => $approvalStatus,
                'work_hours' => $workHours,
            ],
        ]);
    }

    public function leave(Request $request)
    {
        if ($this->isWeekend()) {
            return response()->json(['message' => 'Tidak bisa mengajukan izin di hari libur (Sabtu/Minggu).'], 403);
        }

        $user = $request->user();

        $validated = $request->validate([
            'check_clock_type' => 'required|in:3,4',
            'reason' => 'nullable|string',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        $today = now()->format('Y-m-d');
        $type = (int)$validated['check_clock_type'];

        $alreadyRequested = ClockRequest::where('user_id', $user->id)
            ->where('date', $today)
            ->exists();

        if ($alreadyRequested) {
            return response()->json(['message' => 'Anda sudah mengajukan permintaan check clock hari ini.'], 400);
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $clockRequest = ClockRequest::create([
            'id'               => Str::uuid()->toString(),
            'user_id'          => $user->id,
            'check_clock_type' => $type,
            'check_clock_time' => now()->format('H:i:s'),
            'date'             => $today,
            'proof_path'       => $path,
            'reason'           => $validated['reason'] ?? null,
            'status'           => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan izin telah dikirim dan menunggu persetujuan.',
            'data' => $clockRequest,
        ]);
    }


    public function absent(Request $request)
    {
        if ($this->isWeekend()) {
            return response()->json(['message' => 'Tidak bisa mengajukan absen di hari libur (Sabtu/Minggu).'], 403);
        }

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
            'id'               => Str::uuid()->toString(),
            'user_id'          => $user->id,
            'check_clock_type' => 5,
            'check_clock_time' => now()->format('H:i:s'),
            'date'             => now()->format('Y-m-d'),
            'reason'           => $request->input('reason', 'Tanpa Keterangan'),
            'status'           => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan absen telah dikirim.',
            'data'    => $clockRequest,
        ]);
    }

    public function detailCheckClock($id)
    {
        $user = auth()->user();

        $checkClock = CheckClock::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$checkClock) {
            return response()->json([
                'message' => 'Data tidak ditemukan atau bukan milik Anda.'
            ], 404);
        }

        $clockIn = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->where('date', $checkClock->date)
            ->first();

        $clockOut = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 2)
            ->where('date', $checkClock->date)
            ->first();

        // Hitung jam kerja
        $workHours = null;
        if ($clockIn && $clockOut) {
            $in = Carbon::createFromFormat('H:i:s', $clockIn->check_clock_time);
            $out = Carbon::createFromFormat('H:i:s', $clockOut->check_clock_time);
            $workHours = gmdate('H:i:s', $in->diffInSeconds($out));
        }

        return response()->json([
            'message' => 'Detail Check Clock',
            'data' => [
                'id' => $checkClock->id,
                'date' => $checkClock->date,
                'check_clock_type' => $checkClock->check_clock_type == 1 ? 'Clock In' : 'Clock Out',
                'check_clock_time' => $checkClock->check_clock_time,
                'proof_path' => $checkClock->proof_path,
                'work_hours' => $workHours,
            ],
        ]);
    }
}
