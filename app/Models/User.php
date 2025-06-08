<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'email',
        'password',
        'is_admin',
        'employee_id',
    ];

    protected $hidden = ['password'];

    public function admin()
    {
        return $this->hasOne(Admin::class, 'user_id');
    }


    public function checkClocks()
    {
        return $this->hasMany(CheckClock::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function letters()
    {
        return $this->hasMany(Letter::class);
    }

    public function checkClockSettingTimeForDay($date)
    {
        $day = Carbon::parse($date)->format('l');

        return DB::table('check_clock_setting_times')
            ->join('check_clock_settings', 'check_clock_settings.id', '=', 'check_clock_setting_times.ck_settings_id')
            ->where('check_clock_settings.id', $this->ck_settings_id)
            ->where('day', $day)
            ->first();
    }
}
