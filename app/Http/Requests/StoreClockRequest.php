<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClockRequest extends FormRequest
{
    /**
     * Tentukan apakah user berhak melakukan request ini.
     * Kalau semua user ter-autentikasi boleh, kembalikan true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi.
     */
    public function rules(): array
    {
        return [
            'check_clock_type' => 'required|integer|in:1,2',   // 1 = clock-in, 2 = clock-out
            'proof'            => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // ≤2 MB
        ];
    }
}
