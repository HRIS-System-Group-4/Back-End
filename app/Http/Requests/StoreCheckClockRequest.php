<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckClockRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:50',
            'type' => 'required|integer', // Misalnya: 1 = WFO, 2 = WFH
            'days' => 'required|array',
            'days.*.day' => 'required|date',
            'days.*.clock_in' => 'required|date_format:H:i',
            'days.*.clock_out' => 'required|date_format:H:i',
            'days.*.break_start' => 'nullable|date_format:H:i',
            'days.*.break_end' => 'nullable|date_format:H:i',
        ];
    }
}
