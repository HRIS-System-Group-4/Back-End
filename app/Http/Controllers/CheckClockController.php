<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClockRequest;
use App\Models\CheckClock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


// app/Http/Controllers/CheckClockController.php
class CheckClockController extends Controller
{
    public function store(StoreClockRequest $request)
    {
        $user = $request->user();

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
            return 'Time to Clock Out not Clock In';
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
