<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckClockSetting extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'type'];

    public function times()
    {
        return $this->hasMany(CheckClockSettingTime::class, 'ck_settings_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'ck_settings_id', 'id');
    }

}
