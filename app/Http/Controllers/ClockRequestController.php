<?php

namespace App\Http\Controllers;

use App\Models\ClockRequest;
use App\Models\CheckClock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClockRequestController extends Controller
{
    public function index()
    {
        $requests = ClockRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar request clock in/out',
            'data' => $requests,
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
}
