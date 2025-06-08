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
            'type' => 'required|integer|in:1,2,3,4,5',
            'proof'            => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // ≤2 MB
        ];
    }
}
