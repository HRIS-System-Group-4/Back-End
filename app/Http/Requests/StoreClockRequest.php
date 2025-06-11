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
        $rules = [
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];

        if ($this->is('api/leave') || $this->input('check_clock_type') >= 3) {
            $rules['type'] = 'required|integer|in:3,4';
        }

        return $rules;
    }
}
