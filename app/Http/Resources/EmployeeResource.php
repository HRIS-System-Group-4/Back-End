<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //aww
        return [
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
            'job_title' => $this->job_title,
            'grade' => $this->grade,
            'branch' => $this->branch?->branch_name,
        ];
    }
}

// <?php

// namespace App\Http\Resources;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class EmployeeResource extends JsonResource
// {
//     /**
//      * Transform the resource into an array.
//      *
//      * @return array<string, mixed>
//      */
//     public function toArray(Request $request): array
//     {
//         return [
//             'name' => $this->first_name . ' ' . $this->last_name,
//             'job_title' => $this->job_title,
//             'grade' => $this->grade,
//             'branch' => $this->branch?->branch_name,
//         ];
//     }
// }