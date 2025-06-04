<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class CompanyController extends Controller
{

    /**
     * @OA\Post(
     *     path="/company",
     *     tags={"Company"},
     *     summary="Membuat data perusahaan baru",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"company_name", "company_username"},
     *             @OA\Property(property="company_name", type="string", example="PT Maju Mundur"),
     *             @OA\Property(property="company_username", type="string", example="majumundur123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Company berhasil dibuat.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company berhasil dibuat."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="c2c1a7d0-3b8d-4e3d-a7e2-123456789abc"),
     *                 @OA\Property(property="company_name", type="string", example="PT Maju Mundur"),
     *                 @OA\Property(property="company_username", type="string", example="majumundur123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The company_username has already been taken.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
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
