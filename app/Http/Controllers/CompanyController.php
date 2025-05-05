<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_username' => 'required|string|max:255|unique:company,company_username',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $company = Company::create([
            'id' => Str::uuid()->toString(),
            'company_name' => $request->company_name,
            'company_username' => $request->company_username,
        ]);

        $admin = Auth::user()->admin;
        $admin->company_id = $company->id;
        $admin->save();

        return response()->json([
            'message' => 'Company berhasil dibuat.',
            'data' => $company
        ], 201);
    }
}
