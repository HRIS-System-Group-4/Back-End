<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class BranchController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/branches",
     *     summary="Menampilkan daftar cabang perusahaan",
     *     tags={"Branch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil menampilkan daftar cabang",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Branch overview"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company tidak ditemukan")
     * )
     */
    public function overview()
    {
        $user = auth()->user();

        $admin = $user->admin;
        if (!$admin || !$admin->company_id) {
            return response()->json(['message' => 'Company tidak ditemukan.'], 404);
        }

        $branches = \App\Models\Branch::where('company_id', $admin->company_id)
            ->select('id', 'branch_name as name', 'address', 'city', 'country', 'status',)
            ->get();

        return response()->json([
            'message' => 'Branch overview',
            'data' => $branches
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/add-branch",
     *     summary="Menambahkan cabang baru",
     *     tags={"Branch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"branch_name", "address", "city", "country", "status"},
     *             @OA\Property(property="branch_name", type="string", example="Cabang Surabaya"),
     *             @OA\Property(property="address", type="string", example="Jl. Raya No. 123"),
     *             @OA\Property(property="city", type="string", example="Surabaya"),
     *             @OA\Property(property="country", type="string", example="Indonesia"),
     *             @OA\Property(property="latitude", type="number", format="float", example="-7.250445"),
     *             @OA\Property(property="longitude", type="number", format="float", example="112.768845"),
     *             @OA\Property(property="status", type="string", enum={"Active", "Inactive"}, example="Active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cabang berhasil ditambahkan"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company tidak ditemukan untuk admin ini"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $admin = $user->admin;
        if (!$admin || !$admin->company_id) {
            return response()->json(['message' => 'Company tidak ditemukan untuk admin ini.'], 404);
        }

        $branchCount = Branch::where('company_id', $admin->company_id)->count();

        $subscription = \App\Models\Subscription::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->orderBy('end_date', 'desc')
            ->first();

        $plan = null;

        if ($subscription) {
            $pricing = \App\Models\SubscriptionPricing::find($subscription->subscription_pricing_id ?? null);
            $plan = $pricing ? strtolower($pricing->name) : null;
        }

        if ($plan === 'basic plan' && $branchCount >= 5) {
            return response()->json([
                'message' => 'Maksimal 5 cabang untuk Basic Plan telah tercapai.'
            ], 403);
        }

        if (!$plan) {
            return response()->json([
                'message' => 'Tidak ada paket langganan aktif.'
            ], 403);
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


    /**
     * @OA\Get(
     *     path="/api/branches/{id}",
     *     summary="Menampilkan detail cabang berdasarkan ID",
     *     tags={"Branch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID UUID dari cabang",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail cabang berhasil ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Branch detail"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Branch tidak ditemukan"
     *     )
     * )
     */
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


    /**
     * @OA\Put(
     *     path="/api/branches/{id}",
     *     operationId="updateBranch",
     *     tags={"Branches"},
     *     summary="Update branch data",
     *     description="Mengedit data branch berdasarkan ID. Hanya dapat diakses oleh admin yang memiliki company_id.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID dari branch yang akan diupdate",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"branch_name", "address", "city", "country", "status"},
     *             @OA\Property(property="branch_name", type="string", example="Cabang Surabaya"),
     *             @OA\Property(property="address", type="string", example="Jl. Merdeka No.1"),
     *             @OA\Property(property="city", type="string", example="Surabaya"),
     *             @OA\Property(property="country", type="string", example="Indonesia"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-7.250445),
     *             @OA\Property(property="longitude", type="number", format="float", example=112.768845),
     *             @OA\Property(property="status", type="string", enum={"Active", "Inactive"}, example="Active")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengupdate branch",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Branch berhasil diperbarui."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="branch_name", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="city", type="string"),
     *                 @OA\Property(property="country", type="string"),
     *                 @OA\Property(property="latitude", type="number", format="float"),
     *                 @OA\Property(property="longitude", type="number", format="float"),
     *                 @OA\Property(property="status", type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Branch atau Company tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Branch tidak ditemukan.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="branch_name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The branch name field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $admin = $user->admin;
        if (!$admin || !$admin->company_id) {
            return response()->json(['message' => 'Company not found for this admin.'], 404);
        }

        $branch = Branch::where('id', $id)->where('company_id', $admin->company_id)->first();

        if (!$branch) {
            return response()->json(['message' => 'Branch not found.'], 404);
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

        $branch->update([
            'branch_name' => $validated['branch_name'],
            'address'     => $validated['address'],
            'city'        => $validated['city'],
            'country'     => $validated['country'],
            'latitude'    => $validated['latitude'],
            'longitude'   => $validated['longitude'],
            'status'      => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Branch berhasil diperbarui.',
            'data'    => $branch
        ]);
    }
}
