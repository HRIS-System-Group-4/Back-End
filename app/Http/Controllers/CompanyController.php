<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CompanyController extends Controller
{
    /**
     * Membuat company baru dan menyimpan lokasi kantor
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_username' => 'required|string|max:255|unique:company,company_username',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_radius' => 'sometimes|integer|min:50|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = Auth::user();
        if (!$user->is_admin || !$user->admin) {
            return response()->json(['message' => 'Hanya admin yang dapat membuat perusahaan.'], 403);
        }

        $company = Company::create([
            'id' => Str::uuid()->toString(),
            'company_name' => $request->company_name,
            'company_username' => $request->company_username,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_radius' => $request->input('location_radius', 200),
            'subscription_active' => false,
            'subscription_expires_at' => null,
        ]);

        $user->admin->company_id = $company->id;
        $user->admin->save();

        return response()->json([
            'message' => 'Company berhasil dibuat dengan lokasi kantor.',
            'data' => $company,
        ], 201);
    }

    /**
     * Mengupdate lokasi kantor perusahaan
     */
    public function updateLocation(Request $request, $companyId)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_radius' => 'sometimes|integer|min:50|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = Auth::user();
        $company = Company::findOrFail($companyId);

        if (!$user->is_admin || !$user->admin || $user->admin->company_id !== $company->id) {
            return response()->json(['message' => 'Tidak memiliki izin untuk mengubah lokasi perusahaan ini.'], 403);
        }

        $company->latitude = $request->latitude;
        $company->longitude = $request->longitude;
        $company->location_radius = $request->input('location_radius', $company->location_radius ?? 200);
        $company->save();

        return response()->json([
            'message' => 'Lokasi kantor berhasil diperbarui.',
            'data' => $company,
        ]);
    }
}
