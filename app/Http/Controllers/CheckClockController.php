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
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
        $today = now()->format('Y-m-d');

        $alreadyRequested = ClockRequest::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->whereDate('created_at', $today)
            ->exists();

        if ($alreadyRequested) {
            return response()->json(['message' => 'Anda sudah mengirim permintaan clock in hari ini.'], 400);
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $clockRequest = ClockRequest::create([
            'id'               => Str::uuid()->toString(),
            'user_id'          => $user->id,
            'check_clock_type' => 1,
            'check_clock_time' => $request->input('check_clock_time', now()->format('H:i:s')),
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
        $user = $request->user();
        $today = now()->format('Y-m-d');

        $alreadyRequested = ClockRequest::where('user_id', $user->id)
            ->where('check_clock_type', 2)
            ->whereDate('created_at', $today)
            ->exists();

        if ($alreadyRequested) {
            return response()->json(['message' => 'Anda sudah mengirim permintaan clock out hari ini.'], 400);
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $clockRequest = ClockRequest::create([
            'id'               => Str::uuid()->toString(),
            'user_id'          => $user->id,
            'check_clock_type' => 2,
            'check_clock_time' => $request->input('check_clock_time', now()->format('H:i:s')),
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
        $today = Carbon::now()->format('Y-m-d');

        $clockIns = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->whereDate('created_at', $today)
            ->get();

        $getStatus = function ($clockInTime) {
            $time = Carbon::createFromFormat('H:i:s', $clockInTime);
            $onTimeStart = Carbon::createFromTime(8, 0, 0);
            $onTimeEnd = Carbon::createFromTime(8, 15, 0);
            $lateEnd = Carbon::createFromTime(12, 0, 0);

            if ($time->between($onTimeStart, $onTimeEnd)) {
                return 'On Time';
            } elseif ($time->gt($onTimeEnd) && $time->lte($lateEnd)) {
                return 'Late';
            } elseif ($time->gt($lateEnd)) {
                return 'Late for Too Long';
            }
            return 'Waktu untuk absen masuk';
        };

        $clockInsWithStatus = $clockIns->map(function ($clock) use ($getStatus) {
            return [
                'id' => $clock->id,
                'check_clock_time' => $clock->check_clock_time,
                'status' => $getStatus($clock->check_clock_time),
                'proof_path' => $clock->proof_path,
                'created_at' => $clock->created_at,
            ];
        });

        return response()->json([
            'message' => 'Data Record Check Clock',
            'data' => $clockInsWithStatus,
        ]);
    }
}
