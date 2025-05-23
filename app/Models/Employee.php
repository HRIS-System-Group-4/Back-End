<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'nik',
        'phone_number',
        'birth_place',
        'birth_date',
        'branch_id',
        'job_title',
        'grade',
        'employment_type',
        'sp_type',
        'bank_name',
        'bank_account_no',
        'bank_account_owner',
        'ck_settings_id',
        'avatar_path',
        'company_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function letters()
    {
        return $this->hasMany(Letter::class, 'employee_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
