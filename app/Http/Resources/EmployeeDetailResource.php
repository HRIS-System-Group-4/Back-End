<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nik' => $this->nik,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => ($this->user)->email,
            // 'employee_id' => ($this->user)->employee_id,
            'employee_id' => $this->user?->employee_id,
            'employment_type' => $this->employment_type,
            'phone_number' => $this->phone_number,
            'birth_date' => $this->birth_date,
            'birth_place' => $this->birth_place,
            'grade' => $this->grade,
            'avatar_path' => $this->avatar_path,
            'sp_type' => $this->sp_type,
            'job_title' => $this->job_title,
            'bank_name' => $this->bank_name,
            'bank_account_no' => $this->bank_account_no,
            'bank_account_owner' => $this->bank_account_owner,
            'gender' => $this->gender,
            'address' => $this->address,
            'branch_name' => ($this->branch)->branch_name,
            'check_clock_settings_name' => $this->checkClockSetting?->name,
            'ck_settings_id'            => $this->check_clock_setting_id,
        ];
    }
}