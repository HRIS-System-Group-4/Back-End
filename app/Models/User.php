<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


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
    ];

    protected $hidden = ['password'];

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function checkClocks()
    {
        return $this->hasMany(CheckClock::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
}
