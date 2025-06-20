<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckClockRequest;
use App\Models\CheckClockSetting;
use App\Models\CheckClockSettingTime;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CheckClockSettingController extends Controller
{
    public function store(StoreCheckClockRequest $request)
    {
        $validated = $request->validated();
        DB::beginTransaction();

        try {
            // $setting = CheckClockSetting::create([
            //     'id' => Str::uuid()->toString(),
            //     'name' => $request->name,
            //     'type' => $request->type,
            // ]);
            $setting = CheckClockSetting::create([

                'id'   => Str::uuid(),
                'name' => $validated['name'],
                'type' => $validated['type'],
            ]);

            // foreach ($request->days as $day) {
            //     CheckClockSettingTime::create([
            //         'id' => Str::uuid()->toString(),
            //         'ck_settings_id' => $setting->id,
            //         'day' => $day['day'],
            //         'clock_in' => $day['clock_in'],
            //         'clock_out' => $day['clock_out'],
            //         'break_start' => $day['break_start'] ?? null,
            //         'break_end' => $day['break_end'] ?? null,
            //         'late_tolerance' => $day['late_tolerance'] ?? 0,
            //     ]);
            // }
            foreach ($validated['days'] as $day) {
                $setting->times()->create([
                    'id'             => Str::uuid(),

                    'id'   => Str::uuid()->toString(),
                    'name' => $request->name,
                    'type' => $request->type,
                ]);

                return response()->json([
                    'message' => 'Check Clock Setting created successfully.',
                    'data'    => [
                        'id'          => $setting->id,
                        'name'        => $setting->name,
                        'type'        => $setting->type,
                        'type_label'  => $setting->type_label,
                    ]
                ], 201);
            }
        } catch (\Exception $e) {
            foreach ($request->days as $day) {
                CheckClockSettingTime::create([
                    'id'             => Str::uuid()->toString(),
                    'ck_settings_id' => $setting->id,
                    'day'            => $day['day'],
                    'clock_in'       => $day['clock_in'],
                    'clock_out'      => $day['clock_out'],
                    'break_start'    => $day['break_start'] ?? null,
                    'break_end'      => $day['break_end'] ?? null,
                    'late_tolerance' => $day['late_tolerance'] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Check Clock Setting created successfully.',
                'data'    => [
                    'id'          => $setting->id,
                    'name'        => $setting->name,
                    'type'        => $setting->type,
                    'type_label'  => $setting->type_label,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $setting = CheckClockSetting::with('times')->findOrFail($id);

        $days = $setting->times
            ->sortBy('day')
            ->map(function ($row) {
                return [
                    'day'            => $row->day,
                    'clock_in'       => $row->clock_in,
                    'clock_out'      => $row->clock_out,
                    'break_start'    => $row->break_start,
                    'break_end'      => $row->break_end,
                    'late_tolerance' => $row->late_tolerance,
                ];
            })
            ->values();

        return response()->json([
            'id'   => $setting->id,
            'name' => $setting->name,
            'type' => $setting->type,
            'days' => $days,
        ]);
    }

    public function update(Request $request, $id)
    {
        $setting = CheckClockSetting::findOrFail($id);

        $setting->update([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
        ]);

        foreach ($request->input('days') as $day => $data) {
            CheckClockSettingTime::updateOrCreate(
                ['ck_settings_id' => $setting->id, 'day' => $data['day']],
                [
                    'id'             => Str::uuid()->toString(),
                    'clock_in'       => $data['clock_in'],
                    'clock_out'      => $data['clock_out'],
                    'break_start'    => $data['break_start'],
                    'break_end'      => $data['break_end'],
                    'late_tolerance' => $data['late_tolerance'],
                ]
            );
        }

        return response()->json([
            'message' => 'Check Clock Setting updated successfully.',
        ]);
    }

    public function show($id)
    {
        $setting = CheckClockSetting::with('times')->findOrFail($id);

        $setting->times = $setting->times
            ->sortBy('day')
            ->mapWithKeys(fn($row) => [
                $row->day => [
                    'type'       => $setting->type,
                    'clock_in'       => $row->clock_in,
                    'clock_out'      => $row->clock_out,
                    'break_start'    => $row->break_start,
                    'break_end'      => $row->break_end,
                    'late_tolerance' => $row->late_tolerance,
                ]
            ]);

        return response()->json([
            'id'         => $setting->id,
            'name'       => $setting->name,
            'type'       => $setting->type,
            // 'type_label' => $setting->type_label,
            'days'       => $setting->times,
        ]);
    }

    public function index()
    {
        $settings = CheckClockSetting::with(['times', 'employees'])->get()->map(function ($setting) {
            return [
                'id'              => $setting->id,
                'name'            => $setting->name,
                'type'            => $setting->type,
                'total_employees' => $setting->employees->count(),
            ];
        });

        return response()->json([
            'message' => 'List of Check Clock Settings',
            'data'    => $settings,
        ]);
    }
}
