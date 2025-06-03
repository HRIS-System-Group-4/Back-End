<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClockRequest;
use App\Models\CheckClock;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CheckClockController extends Controller
{
    public function store(StoreClockRequest $request)
    {
        $user = $request->user();
        $today = Carbon::now()->format('Y-m-d');

        $alreadyClockedIn = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 1)
            ->whereDate('created_at', $today)
            ->exists();

        if ($alreadyClockedIn) {
            return response()->json([
                'message' => 'Anda sudah melakukan clock in hari ini.',
            ], 400);
        }

        $employee = Employee::where('user_id', $user->id)->with('company')->first();
        if (!$employee || !$employee->company) {
            return response()->json(['message' => 'Data perusahaan tidak ditemukan.'], 404);
        }

        $company = $employee->company;

        $subscriptionValid = $company->subscription_active
            && $company->subscription_expires_at
            && Carbon::parse($company->subscription_expires_at)->isFuture();

        if ($subscriptionValid && $request->check_clock_type == 1) {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $distance = $this->calculateDistance(
                $company->latitude,
                $company->longitude,
                $request->latitude,
                $request->longitude
            );

            if ($distance > $company->location_radius) {
                return response()->json([
                    'message' => 'Anda berada di luar area kantor. Absen ditolak.',
                    'distance_m' => $distance,
                ], 403);
            }
        }

        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $uuid = Str::uuid()->toString();

        $clock = CheckClock::create([
            'id'               => $uuid,
            'user_id'          => $user->id,
            'check_clock_type' => $request->check_clock_type,
            'check_clock_time' => $request->input('check_clock_time', now()->format('H:i:s')),
            'proof_path'       => $path,
        ]);

        return response()->json([
            'message'   => 'Clock recorded',
            'data'      => $clock,
            'proof_url' => $path ? asset('storage/' . $path) : null,
        ], 201);
    }

    public function clockOut(Request $request)
    {
        $user = $request->user();
        $today = Carbon::now()->format('Y-m-d');

        // Cek apakah sudah clock out hari ini
        $alreadyClockedOut = CheckClock::where('user_id', $user->id)
            ->where('check_clock_type', 2) // 2 = clock out
            ->whereDate('created_at', $today)
            ->exists();

        if ($alreadyClockedOut) {
            return response()->json([
                'message' => 'Anda sudah melakukan clock out hari ini.',
            ], 400);
        }

        $employee = Employee::where('user_id', $user->id)->with('company')->first();
        if (!$employee || !$employee->company) {
            return response()->json(['message' => 'Data perusahaan tidak ditemukan.'], 404);
        }

        $company = $employee->company;

        // Cek subscription
        $subscriptionValid = $company->subscription_active
            && $company->subscription_expires_at
            && Carbon::parse($company->subscription_expires_at)->isFuture();

        // Jika aktif, cek lokasi
        $locationStatus = null;
        $distance = null;

        if ($subscriptionValid) {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $distance = $this->calculateDistance(
                $company->latitude,
                $company->longitude,
                $request->latitude,
                $request->longitude
            );

            $locationStatus = $distance <= $company->location_radius ? 'inside' : 'outside';
        }

        // Simpan bukti foto jika ada
        $path = $request->file('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $uuid = Str::uuid()->toString();

        $clock = CheckClock::create([
            'id'               => $uuid,
            'user_id'          => $user->id,
            'check_clock_type' => 2, // clock out
            'check_clock_time' => $request->input('check_clock_time', now()->format('H:i:s')),
            'proof_path'       => $path,
        ]);

        return response()->json([
            'message' => 'Clock out recorded',
            'data' => $clock,
            'proof_url' => $path ? asset('storage/' . $path) : null,
            'location_check' => $subscriptionValid ? [
                'status' => $locationStatus,
                'distance_m' => $distance,
            ] : null,
        ]);
    }


    // Fungsi untuk menghitung jarak 2 titik lat/lng (meter)
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meter

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
