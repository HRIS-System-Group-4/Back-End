<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function overview()
    {
        $user = auth()->user();

        $admin = $user->admin;
        if (!$admin || !$admin->company_id) {
            return response()->json(['message' => 'Company tidak ditemukan.'], 404);
        }

        $branches = \App\Models\Branch::where('company_id', $admin->company_id)
            ->select('branch_name', 'address', 'city', 'country')
            ->paginate(10);

        return response()->json([
            'message' => 'Branch overview',
            'data' => $branches
        ]);
    }


    public function store(Request $request)
    {
        $user = Auth::user();

        $admin = $user->admin;
        if (!$admin || !$admin->company_id) {
            return response()->json(['message' => 'Company tidak ditemukan untuk admin ini.'], 404);
        }

        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'address'     => 'required|string|max:255',
            'city'        => 'required|string|max:50',
            'country'     => 'required|string|max:50',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'status'      => 'required|in:Active,Inactive',
        ]);

        $branch = Branch::create([
            'id'           => Str::uuid()->toString(),
            'company_id'   => $admin->company_id,
            'branch_name'  => $validated['branch_name'],
            'address'      => $validated['address'],
            'city'         => $validated['city'],
            'country'      => $validated['country'],
            'latitude'     => $validated['latitude'],
            'longitude'    => $validated['longitude'],
            'status'       => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Branch Berhasil Ditambahkan.',
            'data'    => $branch
        ], 201);
    }

    public function show($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        return response()->json([
            'message' => 'Branch detail',
            'data' => [
                'branch_name' => $branch->branch_name,
                'address'     => $branch->address,
                'city'        => $branch->city,
                'country'     => $branch->country,
                'status'      => $branch->status,
                'latitude'    => $branch->latitude,
                'longitude'   => $branch->longitude,
                'google_maps_url' => $branch->latitude && $branch->longitude
                    ? "https://www.google.com/maps?q={$branch->latitude},{$branch->longitude}"
                    : null,
            ]
        ]);
    }
}
