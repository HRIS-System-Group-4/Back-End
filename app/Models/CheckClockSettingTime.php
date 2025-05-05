<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckClockSettingTime extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'ck_settings_id',
        'day',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end'
    ];

    public function setting()
    {
        return $this->belongsTo(CheckClockSetting::class, 'ck_settings_id');
    }
}
