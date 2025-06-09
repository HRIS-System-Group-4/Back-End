<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

class ForgotPasswordController extends Controller
{

    /**
     * @OA\Post(
     *     path="/forgot-password",
     *     summary="Mengirimkan link reset password ke email pengguna",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Link reset password berhasil dikirim"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Gagal mengirim email reset password"
     *     )
     * )
     */
    // Mengirim email reset password
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Link reset password telah dikirim ke email.'])
            : response()->json(['message' => 'Gagal mengirim link reset password.'], 500);
    }


    /**
     * @OA\Post(
     *     path="/reset-password",
     *     summary="Mengatur ulang password menggunakan token reset",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "email", "password", "password_confirmation"},
     *             @OA\Property(property="token", type="string", example="reset-token-here"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newsecurepassword"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newsecurepassword")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password berhasil direset"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token tidak valid atau data salah"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    // Menangani reset password (POST)
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password berhasil diubah.'])
            : response()->json(['message' => __($status)], 400);
    }


    /**
     * @OA\Get(
     *     path="/reset-password/{token}",
     *     summary="Menampilkan token reset password",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Token reset password dari email",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token berhasil ditampilkan"
     *     )
     * )
     */
    // Menampilkan token reset password
    public function showResetForm($token)
    {
        return response()->json([
            'message' => 'kirim token ini ke endpoint /api/reset-password',
            'token' => $token,
        ]);
    }
}
