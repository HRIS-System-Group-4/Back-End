<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckClockRequest extends FormRequest
{
    // public function rules()
    // {
    //     return [
    //         'name' => 'required|string|max:50',
    //         'type' => 'required|integer', // Misalnya: 1 = WFO, 2 = WFH
    //         'days' => 'required|array',
    //         'days.*.day' => 'required|date',
    //         'days.*.clock_in' => 'required|date_format:H:i',
    //         'days.*.clock_out' => 'required|date_format:H:i',
    //         'days.*.break_start' => 'nullable|date_format:H:i',
    //         'days.*.break_end' => 'nullable|date_format:H:i',
    //     ];
    // }
    public function rules()
    {
        return [
            'name' => 'required|string|max:50',
            'type' => 'required|integer',
            'days' => 'required|array|min:1',
            'days.*.day' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'days.*.clock_in' => 'required|date_format:H:i',
            'days.*.clock_out' => 'required|date_format:H:i|after:days.*.clock_in',
            'days.*.break_start' => 'nullable|date_format:H:i',
            'days.*.break_end' => 'nullable|date_format:H:i|after:days.*.break_start',
            'days.*.late_tolerance' => 'nullable|integer|min:0',
        ];
    }
}
